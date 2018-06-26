var UIOWA_QuickProjects = {};

UIOWA_QuickProjects.addElement = function() {
    var clonedUserItem = newUserItem.cloneNode(true);
    document.getElementById("projectInfo").appendChild(clonedUserItem);
    clonedUserItem.firstChild.value = '';
    var userInputs = document.getElementsByName("userItem");

    if (userInputs.length > 1) {
        var btn = document.getElementById("removeUser");
        btn.disabled = false;
    }
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
        'removeSelf',
        'userItem'
    ];

    var projectSetup = document.querySelector('input[name = "setupMethod"]:checked').value;
    paramStr += '&method=' + projectSetup;

    if (projectSetup == 'create') {
        document.querySelector('#projectLink').checked = true;
        document.querySelector('#publicSurveyLink').disabled = true;
    }
    else {
        document.querySelector('#publicSurveyLink').disabled = false;
    }

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
            else if (paramList[i] == 'surveys' || paramList[i] == 'longitudinal' || paramList[i] == 'autonumber' || paramList[i] == 'removeSelf') {
                if (projectInfo[j].checked) {
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
    var superToken = document.getElementById('superToken');

    if (projectSetup == 'create') {
        projectTitle.required = true;
        projectPurpose.required = true;
        superToken.style = ""
    }
    else if (projectSetup == 'copy') {
        projectTitle.required = false;
        projectPurpose.required = false;

        if (!reqToken[0]) {
            superToken.style.display = "none"
        }
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
