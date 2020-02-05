## Quick Projects

### Description
The Quick Projects module allows for the quick creation or modification of REDCap projects (including the process of adding users and assigning rights) via a single POST request. Project creations are achieved through a series of REDCap API calls, while project modifications are performed against a pool of pre-configured "reserve" projects. After the project is created/modified, a link to the project/public survey will be returned.

### Basic Usage
After downloading and enabling this module on your REDCap instance, a link to Quick Projects will appear at the bottom of the Control Center sidebar. This page will allow you to configure your create/modify request via a graphical interface and execute immediately or copy the URL as a template for automation in another system.

If the Quick Permissions module is also installed, any custom user rights presets configured will be available for use in Quick Projects as well.

### Create Project Method
A Super API token is required to create projects.

Another project can be used as a template by either specifying a "Source project API token" or using a "stored project XML template". If an API token is specified, the source project's design will automatically be imported into the newly created project. If the XML template is used, the module will import the stored XML file (uploaded via module config) into the newly created project.

NOTE: REDCap v8.10.0 allows a number of additional project components (such as survey settings and reports) to be optionally included when downloading a project's metadata in XML format. However, none of these additional components will be included when using the "Source project API token" method. Please keep this in mind when deciding which of these features to use.

### Modify Project Method
No additional configuration is required to create projects with this module, but performing a modify operation requires a bit of setup before use.

To use the modify project feature, you must have a "reserve" of template projects. These projects are designated by a specific project note (defined in module configuration) and every time a project is modified via Quick Projects, the project note will be erased (or replaced), removing it from the reserve. Additionally, the user requesting the modification must have an API token attached to the reserved project.

NOTE: This feature was originally implemented as a workaround for the lack of a "deep copy" method in REDCap. With the new functionality added in REDCap v8.10.0 (see note under "Create Project Method" section), this feature may no longer be necessary.

### Configuration Options
* **Prepopulate Super API Token based on currently logged in user:** Check this option to automatically populate the Super API Token field if the current user has one.
* **Prepopulate Super API Token with specified value:** If this field is not blank, the value entered will automatically populate the Super API Token field (regardless of the above option)
* **Automatically use above token if not specified in request parameter:** If no super API token is passed with a request, the module will assume the token in the field above is to be used. WARNING: Enable at your own risk, as this allows anyone to create projects without authentication by submitting a request. It is recommended that the IP whitelisting feature is used in conjunction with this option for additional security.
* **Only allow requests from whitelisted IP addresses:** If this option is enabled, Quick Projects will immediately return an error message to requests from IP addresses that are not explicitly whitelisted.
* **Stored project metadata file:** An XML file containing project metadata (in CDISC ODM format) can be uploaded and optionally imported into projects created with this module.
* **Email address to send error reports:** Various notifications will be sent to defined emails when a reserve is empty or low or if errors cause a request to fail.

#### Modify Project Settings

* **Project note to identify reserved projects:** When making modify project requests, Quick Projects will only use projects with this exact string in its project note. The string can be anything but must be enclosed with brackets (for example, "[RESERVED FOR RESEARCH STUDY]" would be a valid reserve note). Multiple strings can be defined so requests can be executed against multiple different project reserves.
* **Require Super API Token for the Modify Project action:** A Super API Token is not required by REDCap for modifying projects, but can be optionally required by the Quick Projects module as an extra layer of authentication.
* **Return error message if Survey Notifications cannot be enabled:** If a modify request fails to enable survey notifications for a user but is otherwise successful, it will not return an error unless this option is checked.
