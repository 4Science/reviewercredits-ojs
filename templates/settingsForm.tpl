{**
 * plugins/generic/reviewerCredits/settingsForm.tpl
 *
 * Copyright (c) 2015-2018 University of Pittsburgh
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ReviewerCredits plugin settings
 *
 *}
<script>
        $(function() {ldelim}
                // Attach the form handler.
                $('#reviewerCreditsSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim});
</script>

<form class="pkp_form" id="reviewerCreditsSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
<div id="description">{translate key="plugins.generic.reviewerCredits.manager.settings.description"}</div>
<h3>{translate key="plugins.generic.webfeed.settings"}</h3>
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="reviewerCreditsSettingsFormNotification"}

        {fbvFormArea id="reviewerCreditsSettingsFormArea"}
<table width="100%" class="data">
        <tr valign="top">
                <td class="label">{fieldLabel name="reviewerCreditsJournalLogin" required="true" key="plugins.generic.reviewerCredits.manager.settings.reviewerCreditsJournalLogin"}</td>
                <td class="label"><input type="text" name="reviewerCreditsJournalLogin" id="reviewerCreditsJournalLogin" value="{$reviewerCreditsJournalLogin|escape}" size="40" class="textField" /></td>
        </tr>
        <tr valign="top">
                <td class="label">{fieldLabel name="reviewerCreditsJournalPassword" required="true" key="plugins.generic.reviewerCredits.manager.settings.reviewerCreditsJournalPassword"}</td>
                <td class="label"><input type="password" name="reviewerCreditsJournalPassword" id="reviewerCreditsJournalPassword" value="{$reviewerCreditsJournalPassword|escape}" size="40" class="textField" /></td>
        </tr>
</table>

        {/fbvFormArea}

        {fbvFormButtons}
        
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
