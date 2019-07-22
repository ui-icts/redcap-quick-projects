$(document).ready(function () {
    $('#user').autocomplete({
        source: app_path_webroot + "UserRights/search_user.php",
        minLength: 2,
        delay: 150,
        html: true,
        select: function (event, ui) {
            $(this).val(ui.item.value);
            return false;
        }
    });

    $[ "ui" ][ "autocomplete" ].prototype["_renderItem"] = function( ul, item) {
        return $( "<li></li>" )
            .data( "item.autocomplete", item )
            .append( $( "<a></a>" ).html( item.label ) )
            .appendTo( ul );
    };

    $('#storedXml').change(function () {
        var projectToken = $('#templateProjectToken');

        if (this.checked) {
            projectToken.hide();
        }
        else {
            projectToken.show();
        }
    });
});

var UIOWA_QuickProjects = {};

UIOWA_QuickProjects.addService = function() {
    var clonedUserItem = newUserItem.cloneNode(true);
    document.getElementById("projectInfo").appendChild(clonedUserItem);
    clonedUserItem.firstChild.value = '';
    var userInputs = document.getElementsByName("userItem");

    if (userInputs.length > 1) {
        var btn = document.getElementById("removeUser");
        btn.disabled = false;
    }

    UIOWA_QuickProjects.updateFields();
};

UIOWA_QuickProjects.removeElement = function() {
    var userInputs = document.getElementsByName("userItem");
    var lastUser = userInputs[userInputs.length - 1];

    if (userInputs.length > 1) {
        lastUser.parentNode.removeChild(lastUser);
    }
    if (userInputs.length == 1) {
        var btn = document.getElementById("removeUser");
        btn.disabled = true;
    }

    UIOWA_QuickProjects.updateUrlText();
};

UIOWA_QuickProjects.updateUrlText = function() {
    var paramStr = '';
    var paramList = [
        'token',
        'projectToken',
        'storedXml',
        'flag',
        'title',
        'irb',
        'pi_fname',
        'pi_lname',
        'purpose',
        'purpose_other',
        'note',
        'surveys',
        'longitudinal',
        'autonumber',
        'prod',
        'userItem'
    ];

    paramStr += '&NOAUTH=';

    var projectSetup = document.querySelector('input[name = "setupMethod"]:checked').value;
    paramStr += '&method=' + projectSetup;

    var returnValue = document.querySelector('input[name = "returnValue"]:checked').value;
    paramStr += '&return=' + returnValue;

    var irbDiv = document.getElementById('project_pi_irb_div');
    var irbDivStyle = window.getComputedStyle(irbDiv);

    if (document.getElementById('irb_unique').checked && irbDivStyle.display != 'none') {
        paramStr += '&unique=true';
    }

    for (var i = 0; i < paramList.length; i++) {
        var projectInfo = document.getElementsByName(paramList[i]);

        for (var j = 0; j < projectInfo.length; j++) {
            var inputValue = '';

            if (paramList[i] == 'userItem') {
                inputValue = projectInfo[j].children[1].children['user'].value;

                if (inputValue) {
                    paramStr += '&user[]=' + inputValue.replace(/ /g, '+');

                    var rightsSelect = projectInfo[j].children[1].children['rights'];
                    var rightsValue = rightsSelect.options[rightsSelect.selectedIndex].value;

                    paramStr += '&rights[]=' + rightsValue;

                    var notifyValue = projectInfo[j].children[1].children['surveyNotification'].children[0].checked;

                    paramStr += '&surveyNotification[]=' + notifyValue;
                }
            }
            else if (paramList[i] == 'purpose_other') {
                var purposeStyle = window.getComputedStyle(projectInfo[j]);

                if (purposeStyle.display != 'none') {
                    var otherPurposes = projectInfo[j].children;
                    inputValue = '';

                    for (var k = 0; k < otherPurposes.length; k++) {
                        var currentPurpose = otherPurposes[k].children[0];

                        if (currentPurpose.checked) {
                            inputValue = (inputValue ? inputValue + ',' : inputValue) + currentPurpose.value;
                        }
                    }

                    if (inputValue) {
                        paramStr += '&' + paramList[i] + '=' + inputValue;
                    }
                }
            }
            else if (paramList[i] == 'surveys' || paramList[i] == 'longitudinal' || paramList[i] == 'autonumber' || paramList[i] == 'storedXml' || paramList[i] == 'prod') {
                if (projectInfo[j].checked && projectSetup == 'create') {
                    paramStr += '&' + paramList[i] + '=' + '1';
                }
            }
            else if (paramList[i] == 'token') {
                var superToken = document.getElementById('superToken');
                var tokenStyle = window.getComputedStyle(superToken);

                inputValue = projectInfo[j].value;

                if (tokenStyle.display !== 'none' && inputValue) {
                    paramStr += '&' + paramList[i] + '=' + inputValue.replace(/ /g, '+');
                }
            }
            else if (paramList[i] == 'flag') {
                if (projectSetup == 'modify') {
                    var flagSelect = projectInfo[j];
                    var flagValue = flagSelect.options[flagSelect.selectedIndex].value;

                    paramStr += '&' + paramList[i] + '=' + flagValue.replace(/ /g, '+');
                }
            }
            else {
                inputValue = projectInfo[j].value;

                if (inputValue) {
                    paramStr += '&' + paramList[i] + '=' + inputValue.replace(/ /g, '+');
                }
            }
        }
    }

    urlElm.value = baseUrl.trim() + paramStr;
    submitElm.action = baseUrl.trim() + paramStr;
};

UIOWA_QuickProjects.updateFields = function(reqToken) {
    var projectSetup = document.querySelector('input[name = "setupMethod"]:checked').value;
    var projectTitle = document.getElementById('app_title');
    var projectPurpose = document.getElementById('purpose');
    var superToken = $('#superToken');
    var reservedFlag = document.getElementById('reservedFlag');
    var newProjectSettings = $('#newProjectSettings > input');
    var templateProjectToken = $('#templateProjectToken');
    var templateXml = $('#templateXml');

    if (projectSetup == 'create') {
        projectTitle.required = true;
        projectPurpose.required = true;

        if (!templateXml.hasClass('always-hide')) {
            superToken.show();
        }

        newProjectSettings.attr("disabled", false);
        reservedFlag.style.display = "none";
        templateProjectToken.show();

        if (!templateXml.hasClass('always-hide')) {
            templateXml.show();
        }
    }
    else if (projectSetup == 'modify') {
        projectTitle.required = false;
        projectPurpose.required = false;
        reservedFlag.style = "";

        newProjectSettings.attr("disabled", true);

        if (!reqToken[0] || !superToken.hasClass('show-on-modify')) {
            superToken.hide();
        }

        templateProjectToken.hide();
        templateXml.hide();
    }
};

UIOWA_QuickProjects.confirmRedirect = function(message, url) {
    var msgStr = '';

    for (var i in message) {
        msgStr += message[i] + (i < message.length - 1 ? '\n\n' : '')
    }

    var confirmed = confirm(msgStr);

    if (confirmed) {
        window.location.href = url;
    }
};
