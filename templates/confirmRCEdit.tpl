{**
 * plugins/generic/reviewerCredits/confirmRCEdit.tpl
 *
 * Copyright (c) 2015-2018 University of Pittsburgh
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit ReviewerCredits data
 *
 *}
 <div class="section">
    <span class="label">{translate key="plugins.generic.reviewerCredits.form.label"}</span>
    <label class="description">{translate key="plugins.generic.reviewerCredits.form.label.description" linkOpen="<a href=\"https://www.reviewercredits.com\" target=\"_blank\">" linkClose="</a>" linkSignUpOpen="<a href=\"https://www.reviewercredits.com\signup\" target=\"_blank\">" linkSignUpClose="</a>"}</label>
    <ul class="checkbox_and_radiobutton">
        <li>
            <label>
                <input type="checkbox" id="confirmSendRC" value="1" name="confirmSendRC" class="field checkbox" aria-required="true">
                        {translate key="plugins.generic.reviewerCredits.form.consent"}
            </label>
        </li>
    </ul>
</div>
