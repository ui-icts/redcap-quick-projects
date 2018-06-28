<?php
namespace UIOWA\QuickProjects;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;

class QuickProjects extends AbstractExternalModule {
    public static $apiUrl = APP_PATH_WEBROOT_FULL . 'api/';

    public function getPermissionsPresets() {
        $sql = "
              SELECT
                external_module_id
              FROM redcap_external_modules
              WHERE directory_prefix='quick_permissions'
          ";

        $quickPermissionsID = db_result(db_query($sql),0);

        $sql = "
              SELECT
                value
              FROM redcap_external_module_settings
              WHERE external_module_id=$quickPermissionsID and `key`='presets'
          ";

        $result = db_query($sql);

        if ($result -> num_rows > 0) {
            $permissionsPresets = json_decode(db_fetch_assoc($result)['value'], true);
        }
        else {
            $defaultPresetsJson = "{\"none\":{\"title\":\"None\",\"data\":{\"design\":\"0\",\"user_rights\":\"0\",\"data_access_groups\":\"0\",\"data_export\":\"0\",\"reports\":\"0\",\"stats_and_charts\":\"0\",\"calendar\":\"0\",\"data_import_tool\":\"0\",\"data_comparison_tool\":\"0\",\"logging\":\"0\",\"file_repository\":\"0\",\"data_quality_create\":\"0\",\"data_quality_execute\":\"0\",\"record_create\":\"0\",\"record_rename\":\"0\",\"record_delete\":\"0\",\"lock_records_all_forms\":\"0\",\"lock_records\":\"0\",\"lock_records_customization\":\"0\"}},\"all\":{\"title\":\"All (No Mobile App/API)\",\"data\":{\"design\":\"1\",\"user_rights\":\"1\",\"data_access_groups\":\"1\",\"data_export\":\"1\",\"reports\":\"1\",\"stats_and_charts\":\"1\",\"calendar\":\"1\",\"data_import_tool\":\"1\",\"data_comparison_tool\":\"1\",\"logging\":\"1\",\"file_repository\":\"1\",\"data_quality_create\":\"1\",\"data_quality_execute\":\"1\",\"record_create\":\"1\",\"record_rename\":\"1\",\"record_delete\":\"1\",\"lock_records_all_forms\":\"1\",\"lock_records\":\"2\",\"lock_records_customization\":\"1\"}},\"minimal-data-entry\":{\"title\":\"Minimal Data Entry\",\"data\":{\"design\":\"0\",\"user_rights\":\"0\",\"data_access_groups\":\"0\",\"data_export\":\"0\",\"reports\":\"0\",\"stats_and_charts\":\"0\",\"calendar\":\"0\",\"data_import_tool\":\"0\",\"data_comparison_tool\":\"0\",\"logging\":\"0\",\"file_repository\":\"0\",\"data_quality_create\":\"0\",\"data_quality_execute\":\"0\",\"record_create\":\"1\",\"record_rename\":\"0\",\"record_delete\":\"0\",\"lock_records_all_forms\":\"0\",\"lock_records\":\"0\",\"lock_records_customization\":\"0\"}}}";

            $permissionsPresets = json_decode($defaultPresetsJson, true);
        }

        return $permissionsPresets;
    }

    public function generateProject() {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            self::returnResultMessage(["ERROR: The requested method is not implemented."], null);
        }

        if ($this->getSystemSetting('restrict-ip') && !in_array($_SERVER['REMOTE_ADDR'], $this->getSystemSetting('whitelisted-ip'))) {
            self::returnResultMessage(["ERROR: This IP (" . $_SERVER['REMOTE_ADDR'] . ") is not on the whitelist."], null);
        }

        $permissionsPresets = self::getPermissionsPresets();
        $successMsg = [];

        $sql = "
            SELECT
              redcap_user_information.api_token
            FROM redcap_user_information
            WHERE api_token = '" . $_REQUEST['token'] . "'";

        $result = db_query($sql);

        $apiRequired = true;

        if ($_REQUEST['method'] == 'copy' && !$this->getSystemSetting('require-super-token')[0]) {
            $apiRequired = false;
        }

        if (db_num_rows($result) == 0 && $apiRequired) {
            self::returnResultMessage(["ERROR: Invalid API Super Token"], null);
        }

        if ($_REQUEST['unique'] == 'true') {
            // Check for duplicate IRB number
            $sql = "
            SELECT
              redcap_projects.project_irb_number
            FROM redcap_projects
            WHERE project_irb_number = '" . $_REQUEST['irb'] . "'";

            $result = db_query($sql);

            if ($result -> num_rows > 0) {
                self::returnResultMessage(["ERROR: Project with provided IRB number already exists."], null);

            }
        }

        if ($_REQUEST['method'] == "create" && ($_REQUEST['title'] == '' || $_REQUEST['purpose'] == '')) {
            self::returnResultMessage(["ERROR: Project Title and/or Purpose are missing. These parameters are required for new project creation."], null);
        }

        $supertoken = $_REQUEST['token'];

        $projectTitle = htmlentities($_REQUEST['title']);
        $projectNote = htmlentities($_REQUEST['note']);
        $projectPiFname = htmlentities($_REQUEST['pi_fname']);
        $projectPiLname = htmlentities($_REQUEST['pi_lname']);
        $projectIrb = htmlentities($_REQUEST['irb']);


        $createProject = array(
            'token' => $supertoken,
            'content' => 'project',
            'format' => 'json',
            'returnFormat' => 'json',
            'data' => '[' . json_encode(array(
                'project_title' => html_entity_decode($projectTitle),
                'purpose' => $_REQUEST['purpose'],
                'purpose_other' => $_REQUEST['purpose_other'],
                'project_notes' => html_entity_decode($projectNote),
                'is_longitudinal' => $_REQUEST['longitudinal'],
                'surveys_enabled' => $_REQUEST['surveys'],
                'record_autonumbering_enabled' => $_REQUEST['autonumber']
            )) . ']'
        );

        $projectInfo = array(
            'token' => '',
            'content' => 'project_settings',
            'format' => 'json',
            'returnFormat' => 'json',
            'data' => json_encode(array(
                'project_title' => html_entity_decode($projectTitle),
                'project_pi_firstname' => html_entity_decode($projectPiFname),
                'project_pi_lastname' => html_entity_decode($projectPiLname),
                'project_irb_number' => html_entity_decode($projectIrb),
                'purpose' => $_REQUEST['purpose'],
                'purpose_other' => $_REQUEST['purpose_other'],
                'project_notes' => html_entity_decode((isset($_POST['note']) ? $projectNote : '')),
                'is_longitudinal' => $_REQUEST['longitudinal'],
                'surveys_enabled' => $_REQUEST['surveys'],
                'record_autonumbering_enabled' => $_REQUEST['autonumber']
            ))
        );

        $importUsers = array(
            'token' => '',
            'content' => 'user',
            'format' => 'json',
            'returnFormat' => 'json',
            'data' => ''
        );

        if ($_REQUEST['method'] == 'create') {
            if ($_REQUEST['return'] == 'publicSurveyLink') {
                self::returnResultMessage(["ERROR: New projects cannot return public survey links. Project was not created."], null);
            }

            $token = $this->redcapApiCall($createProject, false);

            array_push($successMsg, "Project successfully created!");
        }
        else if ($_REQUEST['method'] == 'copy') {
            $projectInfo['content'] = 'project_settings';

            $reservedProjectFlag = self::getSystemSetting('reserved-project-flag')[0];

            if (!$reservedProjectFlag || !($reservedProjectFlag[0] == '[' && $reservedProjectFlag[strlen($reservedProjectFlag) - 1] == ']')) {
                self::returnResultMessage(["ERROR: Reserved project flag is invalid or blank. Please check your configuration in the Control Center."], null);
            }

            $sql = "
                SELECT
                  redcap_projects.project_id,
                  redcap_user_rights.api_token
                FROM redcap_projects
                INNER JOIN redcap_user_rights on redcap_user_rights.project_id = redcap_projects.project_id
                WHERE project_note = '" . $reservedProjectFlag . "' and api_token is not null
            ";

            $result = db_query($sql);
            $reservedPID = null;
            $projectCount = 0;
            $isFirstRow = TRUE;

            $toEmails = implode(',', self::getSystemSetting('alert-emails')[0]);
            $fromEmail = self::getSystemSetting('alert-email-from')[0];

            if ($result->num_rows == 0) {
                REDCap::email($toEmails, $fromEmail, 'CRITICAL: Quick Projects Reserve Empty', 'No reserved projects found. Last request parameters: ' . var_export($_REQUEST, true));
                self::returnResultMessage(["ERROR: No reserved projects found. Sending email to administrator with study information for manual creation. "], null);
            } elseif ($result->num_rows < self::getSystemSetting('reserve-low-threshold')) {
                REDCap::email($toEmails, $fromEmail, 'Warning: Quick Projects Reserve Low', $result->num_rows . ' reserved projects remaining. Please create more reserved projects.');
            }

            while ($row = db_fetch_assoc($result)) {
                if ($isFirstRow) {
                    $reservedPID = $row['project_id'];
                    $token = $row['api_token'];

                    $isFirstRow = FALSE;
                }

                $projectCount += 1;
            }

            array_push($successMsg, "Project template successfully copied!");
        }

        $projectInfo['token'] = $token;
        $importUsers['token'] = $token;

        $this->redcapApiCall($projectInfo, false);

        $users = $_REQUEST['user'];
        $rights = $_REQUEST['rights'];

        $allUserData = [];

        foreach ($users as $index=>$user) {
            $rightsLookup = $rights[$index];

            $currRights = $permissionsPresets[$rightsLookup];

            $userData = $currRights['data'];
            $userData['username'] = $user;
            array_push($allUserData, $userData);
        }

        $importUsers['data'] = json_encode($allUserData);

        $this->redcapApiCall($importUsers, false);

        if ($_REQUEST['return'] == 'publicSurveyLink') {
                $sql = "
            SELECT s.project_id,s.form_name,s.title as survey_title
            ,pr.app_title, p.hash
            FROM redcap_surveys s
            INNER JOIN redcap_projects pr ON s.project_id = pr.project_id
            INNER JOIN redcap_surveys_participants p ON s.survey_id = p.survey_id
            INNER JOIN redcap_events_metadata em ON em.event_id = p.event_id
            INNER JOIN redcap_events_arms ea ON ea.arm_id = em.arm_id
            INNER JOIN (SELECT project_id, COUNT(arm_id) as num_project_arms FROM redcap_events_arms GROUP BY project_id) proj_ea ON proj_ea.project_id = pr.project_id
            LEFT OUTER JOIN redcap_surveys_response r ON p.participant_id = r.participant_id
            WHERE s.project_id = " . $reservedPID . " AND p.participant_email IS NULL LIMIT 1
        ";

            $result = db_query($sql);
            $row = db_fetch_assoc($result);

            if ($row['hash']) {
                $urlString = APP_PATH_WEBROOT_FULL . 'surveys/?s=' . $row['hash'];

                array_push($successMsg, "Click OK to visit the public survey or Cancel to stay on this page.");

                self::returnResultMessage($successMsg, $urlString);
            }
            else {
                self::returnResultMessage(['No public survey link found. Is the template project configured properly? NOTE: The reserved project (PID: ' . $reservedPID . ') was still updated.'], null);
            }

        }
        else if ($_REQUEST['return'] == 'projectLink') {
            $createProject['token'] = $token;
            unset($createProject['data']);

            $exportedProjectInfo = $this->redcapApiCall($createProject, false);
            $exportedProjectInfo = json_decode($exportedProjectInfo, true);

            $urlString =
                sprintf("https://%s%sProjectSetup/index.php?pid=%d",  // Project Setup page
                    SERVER_NAME,
                    APP_PATH_WEBROOT,
                    $exportedProjectInfo['project_id']);

            array_push($successMsg, "Click OK to visit the project setup page or Cancel to stay on this page.");

            self::returnResultMessage($successMsg, $urlString);
        }
        else {
            self::returnResultMessage(["ERROR: Unknown return type."], null);
        }
    }

    public function displayRequestBuilderPage() {
        session_start();

        $permissionsPresets = self::getPermissionsPresets();

        $superApiToken = $this->getSystemSetting('super-api-token');
        $prepopulateUserToken = $this->getSystemSetting('prepopulate-token');
        $reqCopyToken = json_encode($this->getSystemSetting('require-super-token'));

        if ($superApiToken) {
            $superApiToken = $this->getSystemSetting('super-api-token');
        }
        else if ($prepopulateUserToken) {
            $sql = "
            SELECT
              redcap_user_information.api_token
            FROM redcap_user_information
            WHERE username = '" . USERID . "'";

            $superApiToken = db_result(db_query($sql), 0);
        }

        ?>
        <script src="<?= $this->getUrl("resources/clipboard.js") ?>" xmlns="http://www.w3.org/1999/html"
                xmlns="http://www.w3.org/1999/html"></script>
        <script src="<?= $this->getUrl("QuickProjects.js") ?>"></script>

        <h4>Quick Projects - Request Builder</h4>
        <br/>

        <table style="width:100%;font-size:12px;" cellpadding="0" cellspacing="0">
            <!-- Table rows for Create/Edit Project form -->
            <tbody id="projectInfo" oninput="UIOWA_QuickProjects.updateUrlText()">
                <tr valign="top">
                    <td style="padding-right:20px;width:200px;">
                        <b>Setup method:</b>
                    </td>
                    <td>
                        <input type="radio" id="create" name="setupMethod" onclick="UIOWA_QuickProjects.updateFields(<?=$reqCopyToken?>); UIOWA_QuickProjects.updateUrlText();" value="create" checked>
                        <label for="create"> Create</label>
                        <input type="radio" id="copy" name="setupMethod" onclick="UIOWA_QuickProjects.updateFields(<?=$reqCopyToken?>); UIOWA_QuickProjects.updateUrlText();" value="copy">
                        <label for="copy"> Copy</label>
                    </td>
                </tr>
                <tr valign="top">
                    <td style="padding-right:20px;width:200px;">
                        <b>Return value:</b>
                    </td>
                    <td>
                        <input type="radio" id="projectLink" name="returnValue" onclick="UIOWA_QuickProjects.updateUrlText()" value="projectLink" checked>
                        <label for="projectLink"> Project Link</label>
                        <input type="radio" id="publicSurveyLink" name="returnValue" onclick="UIOWA_QuickProjects.updateUrlText()" value="publicSurveyLink">
                        <label for="publicSurveyLink"> Public Survey Link</label>
                    </td>
                </tr>
                <tr valign="top" id="superToken">
                    <td style="padding-right:20px;width:200px;">
                        <label for="token">Super API token: </label>
                    </td>
                    <td>
                        <input id="token" type="text" name="token" style="width:70%;max-width:450px;" required value="<?= $superApiToken ?>">
                    </td>
                </tr>
                <tr>
                    <td><br/><br/></td>
                </tr>
                <tr valign="top">
                    <td style="padding-right:20px;width:200px;">
                        <b>Project title:</b>
                    </td>
                    <td>
                        <input name="title" id="app_title" autocomplete="new-password" type="text" style="width:95%;max-width:450px;" class="x-form-text x-form-field" onkeydown="if(event.keyCode==13){return false;}" value="<?= $_REQUEST['title'] ?>" required>
                    </td>
                </tr>

                <tr id="row_purpose" valign="top">
                    <td style="padding-top:10px;" id="row_purpose1">
                        <b>Project purpose:</b>
                    </td>
                    <td style="padding-top:10px;" id="row_purpose2">
                        <select id="purpose" name="purpose" class="x-form-text x-form-field" style="" onchange="
if (this.value == &quot;1&quot; || this.value == &quot;2&quot;) {
    $(&quot;#purpose_other_span&quot;).css(&quot;visibility&quot;,&quot;visible&quot;);
} else{
    $(&quot;#purpose_other_span&quot;).css(&quot;visibility&quot;,&quot;hidden&quot;);
}
$(&quot;#project_pi_irb_div&quot;).hide();
if (this.value == &quot;1&quot;) {
    $(&quot;#purpose_other_text&quot;).show();
} else {
    $(&quot;#purpose_other_text, #project_pi_firstname, #project_pi_mi, #project_pi_lastname, #project_pi_email, #project_pi_alias, #project_irb_number, #project_grant_number, #project_pi_username&quot;).val(&quot;&quot;);
    $(&quot;#vunetid-namecheck&quot;).html(&quot;&quot;);
    $(&quot;#purpose_other_text&quot;).hide();
}
if (this.value == &quot;2&quot;) {
    $(&quot;#purpose_other_select, #project_pi_irb_div, #purpose_other&quot;).show();
} else {
    $(&quot;#purpose_other_select&quot;).val(&quot;&quot;);
    $(&quot;#purpose_other_select, #purpose_other&quot;).hide();
}
UIOWA_QuickProjects.updateUrlText();
">
                        <option value=""> ---- Select One ---- </option>
                        <option value="0">Practice / Just for fun</option>
                        <option value="4">Operational Support</option>
                        <option value="2">Research</option>
                        <option value="3">Quality Improvement</option>
                        <option value="1">Other</option>
                    </select>&nbsp;&nbsp;&nbsp;
                    <div id="purpose_other_span" style="visibility: hidden; padding-top: 5px;">
                        <div id="project_pi_irb_div" style="padding: 0px 0px 5px; display: none;">
                            <!-- Project PI -->
                            <div style="padding:3px 0;">
                                <div style="float:left;"><b>Name of P.I. (if applicable):</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                                <div style="float:left;color:#555;">
                                    <input type="text" maxlength="100" name="pi_fname" id="project_pi_firstname" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" style="width:100px;">
                                    <br>First name					</div>
                                <div style="float:left;color:#555;">
                                    <input type="text" maxlength="100" name="pi_lname" id="project_pi_lastname" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" style="width:110px;">
                                    <br>Last name					</div>
                                <div style="clear:both;"></div>
                            </div>
                            <div style="padding:3px 0;">
                                <b>IRB number (if applicable):</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <input type="text" maxlength="100" size="15" name="irb" id="project_irb_number" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field">
                                <!-- "Must be unique" option hidden -->
                                <input style="display:none;" type="checkbox" name="irb_unique" id="irb_unique" onclick="UIOWA_QuickProjects.updateUrlText()">
                                <label style="display:none;" for="surveys">Must be unique</label><br/>
                            </div>
                        </div>
                        <b>Please specify:</b>&nbsp;&nbsp;&nbsp;
                        <input type="text" maxlength="100" size="40" name="purpose_other_text" id="purpose_other_text" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" style="display:none;">
                        <div id="purpose_other" name="purpose_other" onclick="UIOWA_QuickProjects.updateUrlText()" style="display: none;">
                            <div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[0]" id="purpose_other[0]" value="0"> Basic or bench research </div>
                            <div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[1]" id="purpose_other[1]" value="1"> Clinical research study or trial </div>
                            <div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[2]" id="purpose_other[2]" value="2"> Translational research 1 (applying discoveries to the development of trials and studies in humans) </div>
                            <div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[3]" id="purpose_other[3]" value="3"> Translational research 2 (enhancing adoption of research findings and best practices into the community) </div>
                            <div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[4]" id="purpose_other[4]" value="4"> Behavioral or psychosocial research study </div>
                            <div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[5]" id="purpose_other[5]" value="5"> Epidemiology </div>
                            <div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[6]" id="purpose_other[6]" value="6"> Repository (developing a data or specimen repository for future use by investigators) </div>
                            <div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[7]" id="purpose_other[7]" value="7"> Other </div>
                        </div>
                        <br/>
                    </div>
                </td>
            </tr>
            <tr id="row_project_note">
                <td style="padding-top:5px;padding-right:10px;" valign="top">
                    <b>Project notes:</b>
                </td>
                <td style="padding-top:5px;" valign="top">
                    <textarea class="x-form-textarea x-form-field" id="note" name="note" style="height:34px;width:95%;max-width:450px;"></textarea>
                </td>
            </tr>
            <tr>
                <td><br/></td>
            </tr>
            <tr valign="top" id="row_projecttype_title" style="display:none;">
                <td colspan="2" valign="top" style="padding-top:10px;">
                    <div id="primary_use_disable" class="yellow" style="display:none;font-family:tahoma;font-size:10px;margin-bottom:10px;" valign="top">
                        <b>NOTE:</b> The settings below cannot be modified once the project is in production status.		</div>
                    <b>Design your project:</b>
                </td>
            </tr>
            <tr>
                <td>
                </td>
                <td style="padding-right:20px;width:200px;">
                    <input type="checkbox" name="surveys" id="surveys" onclick="UIOWA_QuickProjects.updateUrlText()">
                    <label for="surveys">Surveys enabled</label><br/>
                    <input type="checkbox" name="longitudinal" id="longitudinal" onclick="UIOWA_QuickProjects.updateUrlText()">
                    <label for="longitudinal">Longitudinal study</label><br/>
                    <input type="checkbox" name="autonumber" id="autonumber" onclick="UIOWA_QuickProjects.updateUrlText()">
                    <label for="autonumber">Record autonumbering</label>
                </td>
            </tr>
            <tr>
                <td>
                    <b>Users: </b><br/>
                    <button id='addUser' onclick="UIOWA_QuickProjects.addElement()">+</button><button id='removeUser' disabled onclick="UIOWA_QuickProjects.removeElement();">-</button>
                </td>
                <td>
                </td>
            </tr>
            <tr id="originalUserItem" name="userItem">
                <td></td>
                <td>
                    <input type="text" name="user" id="user">
                    <select name="rights" id="rights">
                        <?php
                        foreach($permissionsPresets as $key => $value):
                            echo '<option value="' . $key . '">' . $value['title'] . '</option>';
                        endforeach;
                        ?>
                    </select>
                </td>
            </tr>
        </tbody>
    </table>

    <br/>
    <br/>
    <div style="padding-right:20px;width:200px;">
        <b>Request URL: </b>
        <button id='copy_btn' data-clipboard-target="#url">Copy</button>
    </div>
    <div>
        <textarea id="url" class="x-form-textarea x-form-field" name="url" readonly style="height:100px;width:95%;max-width:450px;"><?= $this->getUrl("requestHandler.php", false, true) ?></textarea>
    </div>

        <br/>
        <br/>

        <form id="submit" action="" method="post">
        <input type="hidden" id="redirect" name="redirect" value=" ">
        <button type="submit" id="submit" name="submit">Execute Now</button>
    </form>

    <script>
        var urlElm = document.getElementById("url");
        var baseUrl = urlElm.value.split('&method')[0];

        var submitElm = document.getElementById("submit");

        var userItem = document.getElementById("originalUserItem");

        newUserItem = userItem.cloneNode(true);
        newUserItem.id = ' ';

        var btn = document.getElementById('copy_btn');
        var clipboard = new ClipboardJS(btn);

        document.getElementById("redirect").value = window.location.href;

        UIOWA_QuickProjects.updateUrlText();

        <?php
        if(isset($_SESSION['result'])){ //check if form was submitted
            $returnValue = $_SESSION['result'];
            $redirectUrl = $_SESSION['redirectUrl'];
            unset($_SESSION['result']);
            unset($_SESSION['redirectUrl']);

            echo "UIOWA_QuickProjects.confirmRedirect(" . json_encode($returnValue) . ",\"$redirectUrl\");";
        }
        ?>
    </script>
    <?php
    }

    public function returnResultMessage($message, $url) {

        if ($url == null) {
            http_response_code(400);
            echo $message[0];
            exit;
        }

        if ($_POST['redirect']) {
            $_SESSION['result'] = $message;
            $_SESSION['redirectUrl'] = $url;
            $redirect = $_POST['redirect'];

            header("Location: $redirect");
        }
        else {
            echo $url;
        }
    }

    public function redcapApiCall($data, $outputFlag) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        $output = curl_exec($ch);

        curl_close($ch);

        if ($outputFlag) {
            echo $output;
        }
        else {
            return $output;
        }
    }
}
?>
