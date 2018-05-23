## Quick Projects

### Description
The Quick Projects module allows for the quick creation or copy of REDCap projects (including the process of adding users and assigning rights) via a simple user interface or POST request. Project creations are achieved through a series of REDCap API calls, while project copies are pulled from a pool of pre-configured "reserve" projects, allowing for more complicated project setups to be copied quickly. After the project is created/copied, a link to the project/public survey will be returned.

### Basic Usage
After downloading and enabling this module on your REDCap instance, a link to Quick Projects will appear at the bottom of the Control Center sidebar. This interface will allow you to configure your project and create/copy it immediately or copy the POST request URL.

If the Quick Permissions module is also installed, any custom user rights presets configured will be available for use in Quick Projects as well.

### Copy Project Method
No additional configuration is required to create projects with this module, but copying projects requires a bit of setup before use. Instead of copying a project in the traditional sense, the Quick Projects module takes an existing project and modifies it based on the parameters passed (via the user interface or POST request). While this is a slightly more roundabout way to configure a project copy, the main advantage is the potential for automation (by submitting POST requests) and the ability to have a public survey link returned after "copy".

To use the copy project feature, you must have a "reserve" of template projects. These projects are designated by a specific project note (defined in module configuration) and every time a project is copied via Quick Projects, the project note will be erased (or replaced), removing it from the reserve. Additionally, the user requesting the copy must have an API token attached to the reserved project.

### Configuration Options
* **Prepopulate Super API Token based on currently logged in user:** Check this option to automatically populate the Super API Token field if the current user has one.
* **Prepopulate Super API Token with specified value:** If this field is not blank, the value entered will automatically populate the Super API Token field (regardless of the above option)

#### Copy Project Settings

* **Project note to identify reserved projects:** When using making copy project requests, Quick Projects will only use projects with this exact string in its project note. The string can be anything but must be enclosed with brackets (for example, "[RESERVED FOR RESEARCH STUDY]" would be a valid reserve note).
* **Email address to send alerts about reserved projects:** Various notifications will be sent to defined emails when the reserve is empty or low. If a project copy request is submitted while the reserve is empty, the request information will be included in the email.