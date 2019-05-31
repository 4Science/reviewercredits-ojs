<?php

/**
	* @file plugins/generic/reviewerCredits/ReviewerCreditsSettingsForm.inc.php
	*
	* Copyright (c) 2015-2018 University of Pittsburgh
	* Copyright (c) 2014-2018 Simon Fraser University
	* Copyright (c) 2003-2018 John Willinsky
	* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
	*
	* @class ReviewerCreditsSettingsForm
	* @ingroup plugins_generic_reviewerCredits
	*
	* @brief Form for site admins to modify ReviewerCredits plugin settings
*/


import('lib.pkp.classes.form.Form');

class ReviewerCreditsSettingsForm extends Form {

	/** @var $contextId int */
	var $contextId;

	/** @var $plugin object */
	var $plugin;

	/**
	* Constructor
	* @param $plugin object
	* @param $contextId int
	*/
	public function __construct($plugin, $contextId) {
		$this->contextId = $contextId;
		$this->plugin = $plugin;

		if(method_exists($this->plugin, 'getTemplateResource')){
			$constructArgument = $this->plugin->getTemplateResource('settingsForm.tpl');
		}else{
			$constructArgument = $this->plugin->getTemplateResourceName().':templates/settingsForm.tpl';
		}
		parent::__construct($constructArgument);

		$this->addCheck(new FormValidator($this, 'reviewerCreditsJournalLogin', 'required', 'plugins.generic.reviewerCredits.manager.settings.rcLoginRequired'));
		$this->addCheck(new FormValidator($this, 'reviewerCreditsJournalPassword', 'required', 'plugins.generic.reviewerCredits.manager.settings.rcPasswordRequired'));

		$this->addCheck(new FormValidatorCustom(
			$this, 'reviewerCreditsJournalLogin',
			'required',
			'plugins.generic.reviewerCredits.manager.settings.invalid',
			Array($this, 'customValidator')
		));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	* Initialize form data.
	*/
	public function initData() {
		$contextId = $this->contextId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'reviewerCreditsJournalLogin' => $plugin->getSetting($contextId, 'reviewerCreditsJournalLogin'),
			'reviewerCreditsJournalPassword' => $plugin->getSetting($contextId, 'reviewerCreditsJournalPassword'),
		);
	}

	/**
	* Assign form data to user-submitted data.
	*/
	public function readInputData() {
		$this->readUserVars(array('reviewerCreditsJournalLogin'));
		$this->readUserVars(array('reviewerCreditsJournalPassword'));
	}

	/**
	* Fetch the form.
	* @copydoc Form::fetch()
	*/
	public function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());
		return parent::fetch($request);
	}

	/**
	* Save settings.
	*/
	public function execute() {
		$plugin =& $this->plugin;
		$contextId = $this->contextId;

		$plugin->updateSetting($contextId, 'reviewerCreditsJournalLogin', $this->getData('reviewerCreditsJournalLogin'), 'string');
		$plugin->updateSetting($contextId, 'reviewerCreditsJournalPassword', $this->getData('reviewerCreditsJournalPassword'), 'string');
	}

	/**
	* Check credentials.
	*/
	public function customValidator($args){
		$username = trim($args);
		$password = $this->getData('reviewerCreditsJournalPassword');
		if(empty($username) || empty($password)){
			$output = FALSE;
		}else{
			$output = $this->plugin->verifyCredentials($username, $password);
		}
		return $output;
	}
}