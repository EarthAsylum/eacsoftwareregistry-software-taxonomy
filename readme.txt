=== {eac}SoftwareRegistry Software Taxonomy ===
Plugin URI:         https://swregistry.earthasylum.com/software-taxonomy/
Author:             [EarthAsylum Consulting](https://www.earthasylum.com)
Stable tag:         2.0.12
Last Updated:       21-Jul-2025
Requires at least:  5.8
Tested up to:       6.8
Requires PHP:       7.4
Contributors:       kevinburkholder
Donate link:        https://github.com/sponsors/EarthAsylum
License:            GPLv3 or later
License URI:        https://www.gnu.org/licenses/gpl.html
Tags:               software registration, software registry, software license, software product, github hosting, {eac}SoftwareRegistry
WordPress URI:      https://wordpress.org/plugins/eacsoftwareregistry-software-taxonomy
Github URI:         https://github.com/EarthAsylum/eacsoftwareregistry-software-taxonomy

Software Product Taxonomy - Customize {eac}SoftwareRegistry with options, licensing, client messaging, and Github hosting for each software product.

== Description ==

**{eac}SoftwareRegistry Software Taxonomy** is an extension plugin to [{eac}SoftwareRegistry Software Registration Server](https://swregistry.earthasylum.com/software-registration-server/).

Now with [plugin hosting on Github](https://swregistry.earthasylum.com/github-hosting/) to provide complete, automated plugin information and updates in WordPress.

**{eac}SoftwareRegistry Software Taxonomy** is a simple plugin extension that allows you to set and override {eac}SoftwareRegistry options for specific software products. It both defines the software product as well as the server parameters used when that product is registered via the software registration application program interface.

When an API request is received by the registry server, the `registry_product` is matched to the software taxonomy slug. When a match is found, the parameters entered in the software taxonomy meta data are used to override the registry server default parameters.

=  Options set on a per-product basis by this extension =

+   _Software Product_
    +   _Registry Title_            - The Software Product display name
    +   _Registry Description_      - The Software Product Description

+   _Registrar Contact_ (override existing global options in {eac}SoftwareRegistry)
    +   _Registrar Name_            - Sending client email from this name
    +   _Registrar Telephone_       - Include telephone in client notifications
    +   _Registrar Support Email_   - Include support email address in client notifications
    +   _Registrar Web Address_     - Include web address in client notifications

+   _Registration Defaults_ (override existing global options in {eac}SoftwareRegistry)
    +   _Default Status_            - The default status to assign to newly created registrations
    +   _Default Initial Term_      - The initial term when creating a new registration (pending or trial)
    +   _Default Full Term_         - The full term when activating a registration
    +   _Default License_           - The default license level (L1-L5, LD) to assign to newly created registrations

+   _Client Notification_ (Customize the email message and API response notifications sent to the client)
    +   _Client Email Message_      - Message sent to client on creation, activation or update of registration.
    +   _Client API Message_        - Short message included with all API responses.
    +   _Client Success Notice_     - Success notification sent via API response.
    +   _Client Error Notice_       - Error notification sent via API response.

+   _License Limitations_
    +   _see below_

+   _GitHub Hosting_
    +   _WordPress Plugin Slug_     - The {directory}/{plugin.php} slug of the WordPress plugin.
    +   _GitHub Repository_         - The {owner}/{repository} id of the github repository.
    +   _Repository Source_         - Select which source to use from the repository (branch, release).
    +   _Tag Name_                  - (Optional) Specific release or branch tag name (or id).
    +   _Path to readme.txt_        - Pathname within the repository to the readme.txt file.
If blank, the json file will be generated from information available in the GitHub repository.
    +   _GitHub Access Token_       - Your GitHub personal access token, Increases the github rate limit and is required for private repositories.
    +   _Plugin URLs_               - Displays the URL(s) you will need for your plugin file and readme.txt file.

_License Limitations_

Based on the license level assigned to a registration, we can limit the optional values in the registration API (count, variations, options, domains & sites). This allows APIs (possibly from multiple sources) to register any/all values for these options while filtering on the registration server, providing an effective (albeit rudimentary) licensing validation.

For each license level ( L1=Lite, L2=Basic, L3=Standard, L4=Professional, L5=Enterprise, LD=Developer ) you may set a limit for count, variations, options, domains, and sites.

+   _count_        - Number of licenses (users/seats/devices)
+   _variations_   - List of custom name/value pairs
+   _options_      - List of custom registry options
+   _domains_      - List of valid/registered domains
+   _sites_        - List of valid/registered sites/uris

For count, the value passed through the API is limited to a maximum value.
For variations, options, domains, and sites, the arrays passed through the API are sliced to a maximum number of elements.

= GitHub Hosting =

>   \* GitHub hosting requires installation of the [{eac}Readme plugin](https://wordpress.org/plugins/eacreadme/) in order to process readme.txt files.

Wether you're using software registration or not, you can use Github Hosting to manage your software releases and provide automated updates for your WordPress plugins.

New in version 2.0, these options allow for the "self-hosting" of WordPress plugins on GitHub in a way similar to and meeting the requirements of the [WordPress plugin repository](https://wordpress.org/plugins/eacsoftwareregistry-software-taxonomy/).

More details may be found in the included [readme_github.md](https://swregistry.earthasylum.com/github-hosting/) file.


== Installation ==

**{eac}SoftwareRegistry Software Taxonomy** is an extension plugin to and requires installation and registration of [{eac}SoftwareRegistry](https://swregistry.earthasylum.com/).

= Automatic Plugin Installation =

This plugin is available from the [WordPress Plugin Repository](https://wordpress.org/plugins/search/earthasylum/) and can be installed from the WordPress Dashboard » *Plugins* » *Add New* page. Search for 'EarthAsylum', click the plugin's [Install] button and, once installed, click [Activate].

See [Managing Plugins -> Automatic Plugin Installation](https://wordpress.org/support/article/managing-plugins/#automatic-plugin-installation-1)

= Upload via WordPress Dashboard =

Installation of this plugin can be managed from the WordPress Dashboard » *Plugins* » *Add New* page. Click the [Upload Plugin] button, then select the eacsoftwareregistry-software-taxonomy.zip file from your computer.

See [Managing Plugins -> Upload via WordPress Admin](https://wordpress.org/support/article/managing-plugins/#upload-via-wordpress-admin)

= Manual Plugin Installation =

You can install the plugin manually by extracting the eacsoftwareregistry-software-taxonomy.zip file and uploading the 'eacsoftwareregistry-software-taxonomy' folder to the 'wp-content/plugins' folder on your WordPress server.

See [Managing Plugins -> Manual Plugin Installation](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation-1)

= Settings =

Taxonomy settings available from this extension will be seen in the *Software Registry → Software Products* menu.


== Screenshots ==

1. Software Registry → Software Products
![{eac}SoftwareRegistry Software Taxonomy](https://ps.w.org/eacsoftwareregistry-software-taxonomy/assets/screenshot-1.png)

2. Software Registry → Software Products → {product} → Registrar Contact
![{eac}SoftwareRegistry Software Product](https://ps.w.org/eacsoftwareregistry-software-taxonomy/assets/screenshot-2.png)

3. Software Registry → Software Products → {product} → Registration Defaults
![{eac}SoftwareRegistry Software Product](https://ps.w.org/eacsoftwareregistry-software-taxonomy/assets/screenshot-3.png)

4. Software Registry → Software Products → {product} → Client Notification
![{eac}SoftwareRegistry Software Product](https://ps.w.org/eacsoftwareregistry-software-taxonomy/assets/screenshot-4.png)

5. Software Registry → Software Products → {product} → License Limitations
![{eac}SoftwareRegistry Software Product](https://ps.w.org/eacsoftwareregistry-software-taxonomy/assets/screenshot-5.png)

6. Software Registry → Software Products → {product} → Github Hosting
![{eac}SoftwareRegistry Software Product](https://ps.w.org/eacsoftwareregistry-software-taxonomy/assets/screenshot-6.png)


== Other Notes ==

= See Also =

+   [{eac}SoftwareRegistry – Software Registration Server](https://swregistry.earthasylum.com/software-registration-server/)

+   [Implementing the Software Registry SDK](https://swregistry.earthasylum.com/software-registry-sdk/)



== Copyright ==

= Copyright © 2019-2025, EarthAsylum Consulting, distributed under the terms of the GNU GPL. =

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should receive a copy of the GNU General Public License along with this program. If not, see [https://www.gnu.org/licenses/](https://www.gnu.org/licenses/).


== Changelog ==

= Version 2.0.12 – July 21, 2025 =

+   Added additional registrar option filters.
    +   'registrar_status', 'registrar_term', 'registrar_fullterm', 'registrar_license'

= Version 2.0.11 – April 19, 2025 =

+   Compatible with WordPress 6.8+

= Version 2.0.10 – March 10, 2025 =

+   Fix `PHP Warning:  Undefined array key` in taxonomy extension.

= Version 2.0.9 – November 23, 2024 =

+   Added 'client_api_supplemental' field and filter to pass supplemental html to client via api response.
+   Change handling of github hosting log file by appending rather than read/write resulting in change to format.
+   Compatible with WordPress 6.7.

= Version 2.0.8 – May 24, 2024 =

+   Fix update object upgrade_info not set.

= Version 2.0.7 – April 25, 2024 =

+   Minor layout changes/fixes.
+   Fixed version discrepancy.

= Version 2.0.6 – April 19, 2024 =

+   Fixed default return of get_software_options() method.

= Version 2.0.5 – April 15, 2024 =

+   Added 'client_email_footer' option and filter to add optional footer to client email notifications.

= Version 2.0.4 – March 8, 2024 =

+   Changed route argument names from 'route_*' to 'update_*'.
+   Changed selection values from 'latest_release' or 'default_branch' to 'release' or 'branch'.
+   Allow specifying release id (or tag) or branch name.
    +   defaults to 'latest' release or 'default' branch.
+   Specific url: `/{release|branch}/{plugin_name}.json?update_id={tag|id}` overrides set options.

= Version 2.0.3 – March 1, 2024 =

+   Fixed error getting .distignore file from zip archive.
+   Include owner in default contributors.
+   Use GITHUB_ACCESS_TOKEN if defined.

= Version 2.0.2 – February 25, 2024 =

+   Use plugin name (plugin), rather than slug (plugin/plugin.php) in JSON.
+   Some restructuring of code.
+   Set main plugin version correctly.
+   Allow full name source (latest_release|default_branch) as well as abbreviated (release|branch).
+   Changed source 'any' to 'either'.
+   Change user-agent in github api.

= Version 2.0.1 – February 23, 2024 =

+   Added 'slug' to json array.
+   Support 'svg' image type if present.

= Version 2.0.0 – February 22, 2024 =

+   Added GitHub hosting for WordPress plugins.

= Version 1.1.1 – October 31, 2023 =

+   Prevent direct load of include files.

= Version 1.1.0 – May 9, 2023 =

+   Added client notification messages.
+   Reformatted license limitations tab.
+   Added contextual help.

= Version 1.0.0 – April 11, 2023 =

+   Initial release.
