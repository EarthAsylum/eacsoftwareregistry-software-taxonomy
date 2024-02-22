<?php
/**
 * EarthAsylum Consulting {eac}SoftwareRegistration software product taxonomy
 *
 * @category	WordPress Plugin
 * @package		{eac}SoftwareRegistry
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.earthasylum.com>
 * @version		1.x
 *
 * included for render_help() method
 * @version 24.0215.1
 */

defined( 'ABSPATH' ) or exit;

ob_start();
?>
	{eac}SoftwareRegistry Software Taxonomy allows you to set and override {eac}SoftwareRegistry
	options for specific software products. It both defines the software product as well as the
	server parameters used when that product is registered via your software registration
	application program interface.
<?php

$content = ob_get_clean();

//  add a tab - tab_name , content , <details> heading (optional), priority (optional)
$this->addPluginHelpTab(self::HELPTAB_NAME, $content, [$this->className,'open']);

ob_start();
?>
	Wether you're using software registration or not, you can use Github Hosting to manage your
	software releases and provide automated updates for your WordPress plugins.

	These options allow for the "self-hosting" of WordPress plugins on GitHub in a way similar
	to and meeting the requirements of the <a href='https://wordpress.org/plugins/eacsoftwareregistry-software-taxonomy/' _target='_blank'>WordPress plugin repository</a>.

	Like a WordPress plugin, your *self-hosted* plugin should have a well-formed readme.txt that
	follows the <a href='https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works' _target='_blank'>WordPress readme file standard</a>.
	From this file, a json updater file is generated to provide WordPress with the information
	needed to enable automated updating of your plugin.
<?php

$content = ob_get_clean();

//  add a tab - tab_name , content , <details> heading (optional), priority (optional)
$this->addPluginHelpTab(self::HELPTAB_NAME, $content, 'github_hosting');

// add sidebar text/html
$this->addPluginSidebarText('<h4>{eac}SoftwareRegistry</h4>');

// add sidebar link - title , url , tooltip (optional)
$this->addPluginSidebarLink(
	"<span class='dashicons dashicons-editor-help'></span>Registry API",
	'https://swregistry.earthasylum.com/software-registration-server/#api-details',
	'{eac}SoftwareRegistry Application Program Interface'
);
$this->addPluginSidebarLink(
	"<span class='dashicons dashicons-editor-help'></span>Software Products",
	'https://swregistry.earthasylum.com//software-taxonomy/',
	'{eac}SoftwareRegistry Software Product Taxonomy'
);

$this->plugin_help_render($screen);

$this->plugin->html_input_help(self::HELPTAB_NAME, 'name', [
		'label'	=>	'Name',
		'help'	=>	'The Software Product display name.'
	]
);
$this->plugin->html_input_help(self::HELPTAB_NAME, 'slug', [
		'label'	=>	'Slug',
		'help'	=>	'The Software Product Id used to programmatically identify the registered product.'
	]
);
$this->plugin->html_input_help(self::HELPTAB_NAME, 'desc', [
		'label'	=>	'Description',
		'help'	=>	'The Software Product Description.'
	]
);
