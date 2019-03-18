<?php
  
/**
 * @file plugins/generic/reviewerCredits/ReviewerCredits.inc.php
 *
 * Copyright (c) 2015-2018 University of Pittsburgh
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerCreditsPlugin
 * @ingroup plugins_generic_reviewerCredits
 *
 * @brief ReviewerCredits plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

define('REVIEWER_CREDITS_URL', 'https://www.reviewercredits.com/wp-json');
define('REVIEWER_CREDITS_AUTH_ENDPOINT'  , '/jwt-auth/v1/token');
define('REVIEWER_CREDITS_CLAIM_ENDPOINT' , '/reviewer-credits/v10/journal/claim');
//define('REVIEWER_CREDITS_BASIC_AUTH_CRED', 'user:passwd');

class ReviewerCreditsPlugin extends GenericPlugin {
        
        protected $_apiFlag = FALSE;
        protected $_consentFlag = TRUE;
    
        /**
         * @copydoc Plugin::register()
         */
        public function register($category, $path, $mainContextId = null) {
            $success = parent::register($category, $path, $mainContextId);
            if(!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
            if($success && $this->getEnabled($mainContextId)){
                HookRegistry::register('LoadHandler', Array($this, 'callbackGetInfo'));
                //HookRegistry::register('reviewerreviewstep3form::initData', Array($this, 'metadataInitData'));
                //HookRegistry::register('reviewerreviewstep3form::readInputData', Array($this, 'metadataReadInputData'));
                HookRegistry::register('TemplateManager::fetch', Array($this, 'handleTemplateDisplay'));
                //HookRegistry::register('reviewerreviewstep3form::execute', Array($this, 'metadataExecute'));
                HookRegistry::register('reviewerreviewstep3form::execute', Array($this, 'callbackSendClaim'));
                $this->_registerTemplateResource();
            }
            return $success;
        }
    
        /**
         * @copydoc Plugin::getDisplayName()
         */
        public function getDisplayName() {
                return __('plugins.generic.reviewerCredits.displayName');
        }

        /**
         * @copydoc Plugin::getDescription()
         */
        public function getDescription() {
                return __('plugins.generic.reviewerCredits.description');
        }

        /**
         * @copydoc PKPPlugin::getTemplatePath
         */
        public function getTemplatePath($inCore = false) {
            if(method_exists($this, 'getTemplateResourceName')){
                //##--> OJS 3.1.1
                return $this->getTemplateResourceName() . ':templates/';
            }else{
                //##--> OJS 3.1.2 onward
                return ((!$inCore)?'../':'') . parent::getTemplatePath($inCore) . '/';
            }
        }
        
        /**
         * @copydoc Plugin::getActions()
         */
        public function getActions($request, $verb) {
                $router = $request->getRouter();
                import('lib.pkp.classes.linkAction.request.AjaxModal');
                return array_merge(
                        $this->getEnabled()?array(
                                new LinkAction(
                                        'settings',
                                        new AjaxModal(
                                                $router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
                                                $this->getDisplayName()
                                        ),
                                        __('manager.plugins.settings'),
                                        null
                                ),
                        ):array(),
                        parent::getActions($request, $verb)
                );
        }

        /**
         * @see Plugin::manage()
         */
        public function manage($args, $request) {
                switch ($request->getUserVar('verb')) {
                        case 'settings':
                                $context = $request->getContext();
                                $contextId = ($context == null) ? 0 : $context->getId();

                                $templateMgr = TemplateManager::getManager();
                                $templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
                                /*$apiOptions = array(
                                        RC_API_REV_ID_TYPE_EMAIL => 'plugins.generic.reviewerCredits.manager.settings.rcReviewerIdType.email',
                                        RC_API_REV_ID_TYPE_ORCID => 'plugins.generic.reviewerCredits.manager.settings.rcReviewerIdType.orcid',
                                );

                                $templateMgr->assign('rcReviewerIdType', $apiOptions);*/

                                $this->import('ReviewerCreditsSettingsForm');
                                $form = new ReviewerCreditsSettingsForm($this, $contextId);
                                if ($request->getUserVar('save')) {
                                        $form->readInputData();
                                        if ($form->validate()) {
                                                $form->execute();
                                                return new JSONMessage(true);
                                        }
                                } else {
                                        $form->initData();
                                }
                                return new JSONMessage(true, $form->fetch($request));
                }
                return parent::manage($args, $request);
        }
        
        /**
         * Check the correct operation to trigger the API Call
         */
        public function callbackGetInfo($hookName, $op) {
            if($op[0] == 'reviewer' && $op[1] == 'saveStep'){
                $request    = Application::getRequest();
                $args       = $request->getUserVars();
                if($args['step'] == 3){
                    $this->_apiFlag = TRUE;
                }
            }
        }
        
        /**
         * Method to manage and send the Peer Review Claim information to ReviewerCredits
         */
        public function callbackSendClaim($hookName, $args) {
            if($this->_apiFlag && isset($_POST['confirmSendRC']) && $_POST['confirmSendRC'] == 1){
                $request   = Application::getRequest();
                $context   = $request->getContext();
                $userDao   = DAORegistry::getDAO('UserDAO');
                $reviewerSubmission = $args[0]->getReviewerSubmission();
                $reviewer  = $userDao->getById($reviewerSubmission->getReviewerId());
                $tryArray  = Array('orcid', 'email');
                $claimPayload = new stdClass();
                $authPayload  = new stdClass();
                $notificationManager = new NotificationManager();
                $authPayload->username = $this->getSetting($context->getId(), 'reviewerCreditsJournalLogin');
                $authPayload->password = $this->getSetting($context->getId(), 'reviewerCreditsJournalPassword');
                if(!empty($authPayload->username) && !empty($authPayload->password)){
                    $apiAuthToken = $this->_getToken($authPayload);
                    if(!$apiAuthToken->error){
                        $credentialMessages = Array();
                        while(count($tryArray) > 0){
                            if(in_array('orcid', $tryArray)){
                                $claimPayload->reviewerIdentifier = $reviewer->getOrcid();
                                if(empty($claimPayload->reviewerIdentifier)){
                                    $claimPayload->reviewerIdentifier     = $reviewer->getEmail();
                                    $claimPayload->reviewerIdentifierType = 'email';
                                    $tryArray = Array();
                                }else{
                                    $claimPayload->reviewerIdentifierType = 'orcid';
                                    $rawOrcid = preg_replace('{[^0-9X]}', '', $claimPayload->reviewerIdentifier);
                                    $hrOrcid  = chunk_split($rawOrcid, 4, '-');
                                    $hrOrcid  = substr($hrOrcid, 0, -1);
                                    $claimPayload->reviewerIdentifier = $hrOrcid;
                                    unset($tryArray['0']);
                                }
                            }else{
                                $claimPayload->reviewerIdentifier     = $reviewer->getEmail();
                                $claimPayload->reviewerIdentifierType = 'email';
                                $tryArray = Array();
                            }
                            $credentialMessages[] = strtoupper($claimPayload->reviewerIdentifierType).': "'.$claimPayload->reviewerIdentifier.'"';
                            $claimPayload->dateCompleted = $reviewerSubmission->getDateCompleted();
                            //##--> if dateCompleted is empty the submission is a new submission
                            if(empty($claimPayload->dateCompleted)){
                                $claimPayload->dateCompleted = date('Y/m/d');
                                $claimPayload->manuscriptID  = 'PR'.$reviewerSubmission->getReviewId().'-R'.$reviewerSubmission->getRound();
                                //##--> Not used
                                // $claimPayload->editorName    = '';
                                $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $reviewerSubmission->getDateDue());
                                $claimPayload->dateDue = $dateTime->format('Y/m/d');
                                usleep(50000);
                                $apiClaimResponse = $this->_insertClaim($claimPayload, $apiAuthToken->data);
                                if($apiClaimResponse->error){
                                    if($apiClaimResponse->noUser && count($tryArray) == 0){
                                        if(count($credentialMessages) > 1){
                                            $credentialString = join(' or ', $credentialMessages);
                                        }else{
                                            $credentialString = $credentialMessages[0];
                                        }
                                        $notificationManager->createTrivialNotification($reviewer->getId(), NOTIFICATION_TYPE_WARNING, Array('contents' => __('plugins.generic.reviewerCredits.notification.noUser', Array('crMessage' => $credentialString))));
                                    }elseif($apiClaimResponse->noUser && count($tryArray) > 0){
                                        continue;
                                    }else{
                                        $notificationManager->createTrivialNotification($reviewer->getId(), NOTIFICATION_TYPE_ERROR, Array('contents' => __('plugins.generic.reviewerCredits.notification.failed')));
                                        $tryArray = Array();
                                    }
                                }else{
                                    $notificationManager->createTrivialNotification($reviewer->getId(), NOTIFICATION_TYPE_SUCCESS, Array('contents' => __('plugins.generic.reviewerCredits.notification.success')));
                                    $tryArray = Array();
                                }
                            }
                        }
                    }else{
                        $notificationManager->createTrivialNotification($reviewer->getId(), NOTIFICATION_TYPE_ERROR, Array('contents' => __('plugins.generic.reviewerCredits.notification.failedAuth')));
                    }
                }
            }
        }
        
        /**
         * Hook callback: register output filter.
         * @see TemplateManager::display()
         */
        public function handleTemplateDisplay($hookName, $args) {
            $templateMgr = $args[0];
            $template    = $args[1];
            $request     = PKPApplication::getRequest();
            if($template == 'reviewer/review/step3.tpl'){
                if(method_exists($templateMgr, 'register_outputfilter')){
                    //##--> OJS 3.1.1
                    $templateMgr->register_outputfilter(Array($this, 'profileFilter'));
                }else{
                    //##--> OJS 3.1.2 onward
                    $templateMgr->registerFilter('output', Array($this, 'profileFilter'));
                }
            }
            return FALSE;
        }
        
        /**
         * Output filter adds ReviewerCredits checkbox to reviewer submission form.
         * @param $output string
         * @param $templateMgr TemplateManager
         * @return $string
         */
        public function profileFilter($output, $templateMgr) {
            if(preg_match('|div.*formButtons|', $output) && $this->_consentFlag == TRUE){
                if(!preg_match('|disabled|', $output)){
                    $this->_consentFlag = FALSE;
                    $output = $templateMgr->fetch($this->getTemplatePath() . 'confirmRCEdit.tpl') . $output;
                }
            }
            return $output;
        }

        /**
         * This method call the ReviewerCredits API to obtain an authorization token
         */
        protected function _getToken($authPayload) {
            $output   = new stdClass();
            $response = $this->_makeApiCall(REVIEWER_CREDITS_AUTH_ENDPOINT, $authPayload);
            $outputMessage = '';
            if($response->status != 200){
                if(property_exists($response, 'payload')){
                    $outputMessage = $response->payload->message;
                }
                if(property_exists($response->payload->data, 'json_error_message')){
                    if(!empty($outputMessage)){
                        $outputMessage .= ' - ';
                    }
                    $outputMessage .= strip_tags($response->payload->data->json_error_message);
                }
                $output->error = TRUE;
            }else{
                $outputMessage = $response->payload->token;
                $output->error = FALSE;
            }
            $output->data = $outputMessage;
            return $output;
        }
        
        /**
         * This method call the ReviewerCredits API to create a new Peer Review Claim
         */
        protected function _insertClaim($claimPayload, $token) {
            $output   = new stdClass();
            $response = $this->_makeApiCall(REVIEWER_CREDITS_CLAIM_ENDPOINT, $claimPayload, $token);
            if($response->status != 200 && $response->status != 422){
                if(property_exists($response, 'payload')){
                    $outputMessage = $response->payload->message;
                }
                if(property_exists($response->payload, 'payload')){
                    $arrayMessage = Array();
                    foreach($response->payload->payload as $key => $element){
                        $arrayMessage[] = $key.': '.$element;
                    }
                    if(count($arrayMessage) > 0){
                        if(!empty($outputMessage)){
                            $outputMessage .= ' - ';
                        }
                        if(count($arrayMessage) > 1){
                            $outputMessage .= join('; ', $arrayMessage);
                        }else{
                            $outputMessage .= $arrayMessage[0];
                        }
                    }
                }
                $output->noUser = FALSE;
                $output->error  = TRUE;
            }else{
                if($response->status != 200){
                    $outputMessage  = 0;
                    $output->noUser = TRUE;
                    $output->error  = TRUE;
                }else{
                    $outputMessage  = $response->payload->claimId;
                    $output->noUser = FALSE;
                    $output->error  = FALSE;
                }
            }
            $output->data = $outputMessage;
            return $output;
        }
        
        /**
         * This method make the call to the ReviewerCredits API via cURL
         */
        protected function _makeApiCall($endpoint, $rawPayload, $token = null) {
            $output  = new stdClass();
            $payload = json_encode($rawPayload);
            $headers = Array('Content-Type:application/json',
                             'Content-Length: ' . strlen($payload)
                       );
            if(defined('REVIEWER_CREDITS_BASIC_AUTH_CRED')){
                $headerAuth = 'Authorization: Basic '.base64_encode(REVIEWER_CREDITS_BASIC_AUTH_CRED);
                if(!is_null($token)){
                    $headerAuth .= ', Bearer '.$token;
                }
                $headers[] = $headerAuth;
            }else{
                if(!is_null($token)){
                    $headers[] = 'Authorization: Bearer '.$token;
                }
            }
            $ch      = curl_init();
            curl_setopt($ch, CURLOPT_URL, REVIEWER_CREDITS_URL.$endpoint);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $rawOutput  = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($rawOutput === FALSE){
                $output->status  = 0;
                $output->message = curl_error($ch);
            }elseif($httpStatus != 200){
                $output->status  = $httpStatus;
                $output->message = 'HTTP Status '.$httpStatus;
                $output->payload = json_decode($rawOutput);
            }else{
                $output->status  = 200;
                $output->message = 'OK';
                $output->payload = json_decode($rawOutput);
            }
            curl_close($ch);
            return $output;
        }
}