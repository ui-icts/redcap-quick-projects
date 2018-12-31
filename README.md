## Quick Projects

### Description
The Quick Projects module allows for the quick creation or modification of REDCap projects (including the process of adding users and assigning rights) via a simple user interface or POST request. Project creations are achieved through a series of REDCap API calls, while project modifications are performed against a pool of pre-configured "reserve" projects, allowing for more complicated project setups to be duplicated quickly. After the project is created/modified, a link to the project/public survey will be returned.

### Basic Usage
After downloading and enabling this module on your REDCap instance, a link to Quick Projects will appear at the bottom of the Control Center sidebar. This interface will allow you to configure your create/modify request via a graphical interface and execute immediately or copy the URL as a template for automation in another system.

If the Quick Permissions module is also installed, any custom user rights presets configured will be available for use in Quick Projects as well.

### Modify Project Method
No additional configuration is required to create projects with this module, but performing a modify operation requires a bit of setup before use.

To use the modify project feature, you must have a "reserve" of template projects. These projects are designated by a specific project note (defined in module configuration) and every time a project is modified via Quick Projects, the project note will be erased (or replaced), removing it from the reserve. Additionally, the user requesting the modification must have an API token attached to the reserved project.

### Configuration Options
* **Prepopulate Super API Token based on currently logged in user:** Check this option to automatically populate the Super API Token field if the current user has one.
* **Prepopulate Super API Token with specified value:** If this field is not blank, the value entered will automatically populate the Super API Token field (regardless of the above option)
* **Only allow requests from whitelisted IP addresses:** If this option is enabled, Quick Projects will immediately return an error message to requests from IP addresses that are not explicitly whitelisted.

#### Modify Project Settings

* **Project note to identify reserved projects:** When making modify project requests, Quick Projects will only use projects with this exact string in its project note. The string can be anything but must be enclosed with brackets (for example, "[RESERVED FOR RESEARCH STUDY]" would be a valid reserve note). Multiple strings can be defined so requests can be executed against multiple different project reserves.
* **Email address to send alerts about reserved projects:** Various notifications will be sent to defined emails when a reserve is empty or low. If a project modify request is submitted while a reserve is empty, the request information will be included in the email.
* **Require Super API Token for the Modify Project action:** A Super API Token is not required by REDCap for modifying projects, but can be optionally required by the Quick Projects module as an extra layer of authentication.
* **Return error message if Survey Notifications cannot be enabled:** If a modify request fails to enable survey notifications for a user but is otherwise successful, it will not return an error unless this option is checked.