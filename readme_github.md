# {eac}SoftwareRegistry Software Taxonomy - Github Hosting  
Plugin URI:         https://swregistry.earthasylum.com/software-taxonomy/  
Author:             [EarthAsylum Consulting](https://www.earthasylum.com)  
Last Updated:       24-Feb-2024  
Contributors:       [kevinburkholder](https://profiles.wordpress.org/kevinburkholder)  
WordPress URI:      https://wordpress.org/plugins/eacsoftwareregistry-software-taxonomy  

> Software Product Taxonomy - Github Hosting provides "self-hosting" and automatic updates of your WordPress plugins on Github.

## Description

>   \* GitHub hosting requires installation of the [{eac}Readme plugin](https://wordpress.org/plugins/eacreadme/) in order to process readme.txt files.

Wether you're using software registration or not, you can use Github Hosting to manage your software releases and provide automated updates for your WordPress plugins.

New in version 2.0, these options allow for the "self-hosting" of WordPress plugins on GitHub in a way similar to and meeting the requirements of the [WordPress plugin repository](https://wordpress.org/plugins/eacsoftwareregistry-software-taxonomy/).

Like a WordPress plugin, your *self-hosted* plugin should have a well-formed readme.txt that follows the [WordPress readme file standard](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works). From this file, a json updater file is generated to provide WordPress with the information needed to enable automated updating of your plugin, including complete 'view details' tabs and 'update now' link. *(see the included readme_template.txt)*

![Automatic Plugin Update](https://ps.w.org/eacsoftwareregistry-software-taxonomy/assets/plugin_update.png)

#### Recommendations

+   Use GitHub releases to control what and when a version is ready for production.
    +   Ability too install the latest release on production systems or the default branch on non-production.
+   Tag your release with a version number (using semantic versioning: M.m.p).
    +   Tags are used in zip file names.
+   Include a description (change log) with your release.

If necessary, *custom properties* can be set in your github repository to provide missing readme header values to WordPress. For example, WordPress needs a `requires_php` header; you can set a custom property named `wp-requires_php` with a value such as "8.0" to be used if the header is not found in the readme.txt file. Any custom property prefixed with "wp-" can be used.

You may also set a custom property named `wp-sections` with a comma delimited list of section names (tabs) to be pulled from the readme.txt file. This is used to filter out unwanted sections or include non-standard sections (e.g. `wp-sections = "description,changelog,extra-details"`)

When creating an installation zip file, your repository is downloaded to your WordPress server, extracted, and a new zip file is created. By default, root hidden files are ignored as well as folders named `.wp-assets`,`.wordpress-org`, or `wp-assets`. If present, a file named `.distignore` will be used to determine the files or folders to ignore. `.distignore` is simply a list of file names that should not be included in your plugin installer zip.

Rather than having the zip file created, you can manually create the file and include it as an asset when creating a new release in your repository. This zip file will be assumed to be a complete plugin installer zip and will be used as-is. The name of this file should be {pluginname}-{version}.zip

Additionally, any image assets in one of the aforementioned folders are downloaded so that they may be used to display banners, icons, or screenshots via your readme file in the WordPress plugin update tabs. Using one of these folders is a good way to store images that should not be included in your plugin installer.

#### Types of API requests

+   `/{pluginname}.json`
    +   An 'info' request for the plugin named '{pluginname}'.
    +   Returns a JSON object built from the readme.txt file.
    +   This is your 'Update URI' for a single plugin.
+   `/{pluginname}.zip`
    +   A 'download' request that results in a redirect to the plugin .zip file.
    +   May be used for external links (e.g. in your e-commerce setup) to download the current version.
+   `/branch/{pluginname}.proxy` or `/release/{pluginname}.proxy`
    +   A 'proxy' request to download the default branch or latest release .zip file.
    +   Used specifically by WordPress updater to download the proper installer.
    +   This url is included in the JSON response 'download_link' and 'package' fields.
+   `/plugin_info.json`
    +   An 'info' request that returns a JSON object for all available plugins.
    +   Specifically intended for the WordPress `update_plugins_{$hostname}` filter available since version 5.8.
    +   May be used as your 'Update URI' for all of your plugins but does not provide plugin information (View Details).

\* Note: you may use the alternate '-' or '+' URI (/{pluginname}-json or /{pluginname}+json).

#### Image asset names

+   banners
    +   high-res: banner-1544x500.jpg or {pluginname}-banner-1544x500.jpg
    +   low-res: banner-772x250.jpg or {pluginname}-banner-772x250.jpg
+   icons
    +   high-res: icon-256x256.jpg or {pluginname}-icon-256x256.jpg
    +   low-res: icon-128x128.jpg or {pluginname}-icon-128x128.jpg
+   Screenshots
    +   screenshot-n.jpg (n = 1,2,...)
+   Acceptable image extensions
    +   .bmp, .gif, .jpg, .jpeg, .png, .svg, .webp

#### Optional Constants (defined in wp-config.php file)

+   `define('EAC_GITHUB_HOSTING_DIR','/path/to/folder')`
    +   Overrides the default ('/wp-content/uploads') local folder.
+   `define('EAC_GITHUB_API_SHORTCUT','shortcut')`
    +   Overrides the default ('/wp-json/softwareregistry/v1') api route for shortened urls.
+   `define('EAC_GITHUB_API_LOG','/relative/path/to/log.json');`
    +   Enables and names the log file (in JSON format).
+   `define('EAC_GITHUB_CDN_HOST','https://cdn_host/path/')`
    +   To use a CDN, replaces the default ('/wp-json/softwareregistry/v1') or shortcut url with this CDN url.

__Recommended__:

	define( 'EAC_GITHUB_HOSTING_DIR', '/software-updates' );
	define( 'EAC_GITHUB_API_SHORTCUT', EAC_GITHUB_HOSTING_DIR );

Optional:

	define( 'EAC_GITHUB_API_LOG', EAC_GITHUB_HOSTING_DIR.'/github_api_log.'.date('Y-m').'.json');


#### Filters & Actions

+   Filter: `eacSoftwareRegistry_github_api_shortcut`
    +   Override the default ('/wp-json/softwareregistry/v1') api route for shortened urls.
+   Filter: `eacSoftwareRegistry_github_cdn_host`
    +   To use a CDN, replaces the default ('/wp-json/softwareregistry/v1') or shortcut url with this CDN url.
+   Filter: `eacSoftwareRegistry_github_api_auth`
    +   Provides a means to further authenticate any "Github Hosting" request.
+   Filter: `eacSoftwareRegistry_github_api_info_response`
    +   Filters the resulting array from a specific plugin 'info' request.
+   Filter: `eacSoftwareRegistry_github_api_plugin_info`
    +   Filters the resulting array from a 'plugin_info' request.

+   Action: `eacSoftwareRegistry_github_api_info`
    +   Triggered before a 'info' request, i.e. when WordPress is checking for an automated plugin update.
+   Action: `eacSoftwareRegistry_github_api_download`
    +   Triggered before a 'download' request from an external link.
+   Action: `eacSoftwareRegistry_github_api_proxy`
    +   Triggered before a 'proxy' request, when WordPress is downloading the '.zip' file to install.

#### WordPress Updates

This extension, obviously, does not provide the necessary code to update your plugins on the WordPress system to which they are installed. It does provide the needed URLs necessary for you to provide information and to automatically update your plugins from within your client's WordPress system.

If your plugin or extension is built with {eac}Doojigger, you're all set. Everything needed is built into the {eac}Doojigger plugin update trait. All you need to do is provide the `Update URI` in the main plugin file.

If not, you will need to include code within your plugin using the following filters:

+   `plugins_api`, `plugin_information` action
    +   See [plugins_api](https://developer.wordpress.org/reference/functions/plugins_api/)
    +   Use the 'Update URI' to retrieve and provide the JSON object needed for the WordPress 'View Info/Details' tabs.
+   `site_transient_update_plugins`
    +   See [support the auto-updates UI](https://make.wordpress.org/core/2020/07/30/recommended-usage-of-the-updates-api-to-support-the-auto-updates-ui-for-plugins-and-themes-in-wordpress-5-5/)
    +   For WordPress versions prior to 5.8, use this filter to provide the updater object.
+   `update_plugins_{$hostname}`
    +   See [update_plugins_{$hostname}](https://developer.wordpress.org/reference/hooks/update_plugins_hostname/)
    +   For WordPress versions 5.8 or greater, use this filter to provide the updater object.

Within these filters, you should cache results (using transients) as the filters may be triggered quite frequently.

Here's a simple (incomplete) example remote call to retrieve and cache the json file:

    if ($result = \get_site_transient('my_plugin_update_transient')) {
        return $result;
    }
    $remote = wp_remote_get($update_uri,
        [
            'headers'   => ['Accept' => 'application/json'],
        ]
    );
    if (!empty($remote['body])) {
        $result = json_decode( $remote['body'], true );
        \set_site_transient('my_plugin_update_transient', $result, HOUR_IN_SECONDS);
    }

When using the `plugins_api` filter, return the full array as an object:

    return (object) $result.

When using the `site_transient_update_plugins` or `update_plugins_{$hostname}` filter, return the plugin array as an object:

    return (object) $result[ $result['slug'] ];

In addition:

1. If your plugin is registered via {eac}SoftwareRegistry, you may add an authentication header including the registration key which will then be verified as valid and active before updating:

		headers   => ['Accept' => 'application/json',
                    'Authentication' => 'token '.base64_encode($this->getRegistrationKey()) ],

2. You may pass a `environment` argument in the URL with the WordPress environment:

		$update_uri = add_query_arg(['environment'=>wp_get_environment_type()],update_uri);

Using this along with setting your Github source to 'Either (Latest or Default)' will allow updates from the default branch for non-production systems and from the latest release for production systems.


## Screenshots

6. Software Registry → Software Products → {product} → Github Hosting
![{eac}SoftwareRegistry Software Product](https://ps.w.org/eacsoftwareregistry-software-taxonomy/assets/screenshot-6.png)

