<?php
/**
 * EarthAsylum Consulting {eac} Software Registration Server - Software Product Taxonomy
 *
 * @category	WordPress Plugin
 * @package		{eac}SoftwareRegistry Software Product Taxonomy
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.earthasylum.com>
 * @uses		{eac}SoftwareRegistry
 *
 * @see https://developer.wordpress.org/reference/functions/plugins_api/
 * @see https://developer.wordpress.org/reference/functions/install_plugin_information/
 *
 * @wordpress-plugin
 * Plugin Name:			{eac}SoftwareRegistry Software Taxonomy
 * Description:			Software Registration Server Software Product Taxonomy - Define software products to be registered with {eac}Software Registration Server.
 * Version:				2.0.11
 * Requires at least:	5.8
 * Tested up to:		6.8
 * Requires PHP:		7.4
 * Plugin URI:          https://swregistry.earthasylum.com/software-taxonomy/
 * Author:				EarthAsylum Consulting
 * Author URI:			http://www.earthasylum.com
 * License: 			GPLv3 or later
 * License URI: 		https://www.gnu.org/licenses/gpl.html
 * Text Domain:			eacSoftwareRegistry
 * Domain Path:			/languages
 */

/*
 * This simple plugin file responds to the 'eacSoftwareRegistry_load_extensions' filter to load additional extensions.
 * Using this method prevents overwriting extensions when the plugin is updated or reinstalled.
 */

namespace EarthAsylumConsulting;

define('EAC_SOFTWARE_TAXONOMY','software_product');

class eacSoftwareRegistry_Software_Taxonomy
{
	/**
	 * constructor method
	 *
	 * @return	void
	 */
	public function __construct()
	{
		/**
		 * eacSoftwareRegistry_load_extensions - get the extensions directory to load
		 *
		 * @param 	array	$extensionDirectories - array of [plugin_slug => plugin_directory]
		 * @return	array	updated $extensionDirectories
		 */
		add_filter( 'eacSoftwareRegistry_load_extensions',	function($extensionDirectories)
			{
				/*
    			 * Enable update notice (self hosted or wp hosted)
    			 */
				eacSoftwareRegistry::loadPluginUpdater(__FILE__,'wp');

				/*
    			 * Add links on plugins page
    			 */
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ),function($pluginLinks, $pluginFile, $pluginData)
					{
						return array_merge(
							[
								'settings'		=> "<a href='".admin_url('edit-tags.php?taxonomy='.EAC_SOFTWARE_TAXONOMY.'&post_type=softwareregistry')."'>Settings</a>",
								'documentation'	=> eacSoftwareRegistry::getDocumentationLink($pluginData),
								'support'		=> eacSoftwareRegistry::getSupportLink($pluginData),
							],
							$pluginLinks
						);
					},20,3
				);

				/*
    			 * Add our extension to load
    			 */
				$extensionDirectories[ plugin_basename( __FILE__ ) ] = [plugin_dir_path( __FILE__ )];
				return $extensionDirectories;
			}
		);
	}
}
new \EarthAsylumConsulting\eacSoftwareRegistry_Software_Taxonomy();
?>
