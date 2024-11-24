<?php
/**
 * EarthAsylum Consulting {eac}SoftwareRegistration software product taxonomy
 *
 * @category	WordPress Plugin
 * @package		{eac}SoftwareRegistry
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.earthasylum.com>
 *
 * included for register_options() method
 * @version 24.1123.1
 */

defined( 'ABSPATH' ) or exit;

$term = ($term_id) ? get_term($term_id) : null;

$this->term_option_fields =
[
	'registrar_contact'			=>
		[
			'registrar_name'		=> array(
						'type'		=> 	'text',
						'label'		=> 	'Registrar Name',
						'info'		=>	'When sending client email, send from this name.',
			),
			'registrar_phone'		=> array(
						'type'		=> 	'tel',
						'label'		=> 	'Registrar Telephone',
						'info'		=> 	'Include telephone in client notifications.',
			),
			'registrar_contact'		=> array(
						'type'		=> 	'email',
						'label'		=> 	'Registrar Support Email',
						'info'		=> 	'Include support email in client notifications -and- send client email from this address.'
			),
			'registrar_web'		=> array(
						'type'		=> 	'url',
						'label'		=> 	'Registrar Web Address',
						'info'		=> 	'Include web address in client notifications.',
			),
		],
	'registration_defaults' 	=>
		[
			'registrar_status'		=> array(
						'type'		=>	'select',
						'label'		=>	'Default Status',
						'options'	=>	$this->plugin->REGISTRY_STATUS_CODES,
						'info'		=>	'The default status to assign to newly created registrations.'
			),
			'registrar_term'		=> array(
						'type'		=>	'select',
						'label'		=>	'Default Initial Term',
						'options'	=>	$this->plugin->REGISTRY_INITIAL_TERMS,
						'info'		=>	"The initial term when creating a new registration (pending or trial)."
			),
			'registrar_fullterm'	=> array(
						'type'		=>	'select',
						'label'		=>	'Default Full Term',
						'options'	=>	$this->plugin->REGISTRY_FULL_TERMS,
						'info'		=>	"The full term when activating a registration."
			),
			'registrar_license'		=> array(
						'type'		=>	'select',
						'label'		=>	'Default License',
						'options'	=>	$this->plugin->REGISTRY_LICENSE_LEVEL,
						'info'		=>	'The default license level to assign to newly created registrations.'
			),
		],
	'client_notification'		=>
		[
			'client_notification_help'	=> array(
					'type'		=> 	'help',
					'label'		=> 	'Client Notification',
					'title'		=>	'Client notification messages may use shortcode-like macros to insert field values.',
					'help'		=>	'<details><summary>[title]</summary><ul>'.
									'<li>Registration Values:<br><small>'.
									implode(', ',array_filter(array_map(function($k){
										return is_scalar($this->plugin::REGISTRY_DEFAULTS[$k]) ? "[{$k}]" : null;
									},array_keys($this->plugin::REGISTRY_DEFAULTS)))) .
									'</small>'.
									'<li>Registrar Values:<br><small>'.
									'[registrar_email], [registrar_name], [registrar_phone], [registrar_contact], [registrar_web]</small>'.
									'<li>Others:<br><small>'.
									'[update_context] (created/activated/revised), [default_message]</small>'.
									'</ul></details>',
			),
			'client_email_message'		=> array(
					'type'		=> 	'html',
					'label'		=> 	'Client Email Message',
					'default'	=>
						"<p>[registry_name],</p>\n".
						"<p>Your product registration for <var>[registry_title]</var> has been [update_context].\n".
						"	The details of your registration are below. Your registration key is: \n".
						"	<code>[registry_key]</code>\n".
						"</p>\n".
						"<p>Thank you for your support and for registering <var>[registry_title]</var>!\n".
						"	We hope you find this software beneficial\n".
						"	and we welcome any thoughts, questions, or concerns you may have.</p>\n".
						"<p>Please feel free to contact us at this email address.</p>\n".
						"<p>Best Regards,</p>",
					'info'		=> 	'Message included in client notification email, followed by registration details.',
			),
			'client_email_footer'		=> array(
					'type'		=> 	'html',
					'label'		=> 	'Client Email Footer',
					'default'	=>	'',
					'info'		=> 	'Optional footer added in client notification email.',
			),
			'client_api_message'		=> array(
					'type'		=> 	'html',
					'label'		=> 	'Client API Message',
					'default'	=>
						"<em>For product support, email\n".
						"<a href='mailto:[registrar_contact]'>[registrar_contact]</a></em>",
					'info'		=> 	'Short message included with API response.',
					'help'		=>	'[info] <br><cite>registrar->message</cite>',
					'wp_editor'	=> ['media_buttons' => false],
			),
			'client_api_supplemental'	=> array(
					'type'		=> 	'html',
					'label'		=> 	'Client API Supplemental',
					'default'	=>	'',
					'info'		=> 	'Suplemental html passed to client via api response.',
					'help'		=>	'[info] <br><cite>supplemental</cite>',
					'wp_editor'	=> ['media_buttons' => false],
			),
			'client_success_notice'		=> array(
					'type'		=> 	'html',
					'label'		=> 	'Client Success Notification',
					'default'	=>
						"<p>Thank you for registering <em>[registry_title]</em>!<br/>\n".
						"	We hope you find this software beneficial\n".
						"	and we welcome any thoughts, questions, or concerns you may have.</p>\n".
						"<p>Please feel free to contact us at \n".
						"	<a href='mailto:[registrar_contact]?body=Registration: [registry_key]'>[registrar_contact]</a>.</p>\n".
						"<p>Best Regards, <em>The Team at [registrar_name]</em></p>",
					'info'		=> 	'Success notification sent via API response.',
					'help'		=>	'[info] <br><cite>registrar->notices[\'success\']</cite>',
					'wp_editor'	=> ['media_buttons' => false],
			),
			'client_error_notice'		=> array(
					'type'		=> 	'html',
					'label'		=> 	'Client Error Notification',
					'default'	=>
						"<p>[default_message]<br>For assistance, contact us at\n".
						"	<a href='mailto:[registrar_contact]?subject=Registration: [registry_key]'>[registrar_contact]</a>.\n".
						"	Please reference your registration key.</p>",
					'info'		=> 	'Append to error notification sent via API response.',
					'help'		=>	'[info] <br><cite>registrar->notices[\'error\']</cite>',
					'wp_editor'	=> ['media_buttons' => false],
			),
		],
	'license_limitations'		=>
		[
			// see below
		],
	'github_hosting'			=>
		[
			'github_plugin_slug'	=> array(
						'type'		=> 	'text',
						'label'		=> 	'WordPress Plugin Slug',
						'info'		=>	'The {directory}/{plugin.php} slug of the WordPress plugin. '.
										'This field is case-sensitive and must match the slug name used when installed in WordPress.',
						'attributes'=>	['placeholder'=> ($term) ? "{$term->slug}/{$term->slug}.php" : "{directory}/{plugin}.php"],
			),
			'github_repository'		=> array(
						'type'		=> 	'text',
						'label'		=> 	'GitHub Repository',
						'info'		=>	'The {owner}/{repository} id of the github repository.',
						'attributes'=>	['placeholder'=>'{owner}/{repo}'],
			),
			'github_source'			=> array(
						'type'		=>	'select',
						'label'		=>	'Repository Source',
						'options'	=>	[	'Release (latest)'				=> 'release',
											'Branch (default)'				=> 'branch',
											'Either (release or branch)' 	=> 'either' ],
						'default'	=>	'release',
						'info'		=>	"Select which source to use from the repository.",
						'help'		=>	"[info]<br>Use the release feature in GitHub to control when your plugin is available for production use. ".
										"If your WordPress updater passes an 'environment' value in the update uri and this fields is set to 'either', ".
										"the latest release will be used for 'production' environments while the default branch will be used for all others."
			),
			'github_sourceId'		=> array(
						'type'		=> 	'text',
						'label'		=> 	'Tag Name',
						'info'		=>	'(Optional) Specific release or branch tag name (or id). '.
										'<br>When blank, use latest release or default branch.',
			),
			'github_readme'			=> array(
						'type'		=>	'text',
						'label'		=>	'Path to readme.txt',
						'default'	=>	'/readme.txt',
						'info'		=>	"Pathname within the repository to the readme.txt file.",
						'help'		=>	"[info]<br>If omitted, the json file will be built from repository information.",
						'attributes'=>	['placeholder'=>'/readme.txt'],
			),
			'github_token'			=> array(
						'type'		=> 	'password',
						'label'		=> 	'GitHub Access Token',
						'default'	=> 	defined('GITHUB_ACCESS_TOKEN') ? GITHUB_ACCESS_TOKEN : '',
						'info'		=>	'Increases the github rate limit and is required for private repositories.',
						'help'		=>	"[info]<br>This value is encrypted when stored.",
						'attributes'=> 	['autocomplete'=>'new-password'],
						'encrypt'	=>	true,
			),
/*
			'github_cdn_root'			=> array(
						'type'		=> 	'url',
						'label'		=> 	'CDN Root (optional)',
						'info'		=>	'Replaces '.home_url('/wp-json/').' in API URLs.',
						'help'		=>	"[info]<br>Enables the use of your CDN for access to registrar urls.",
						'validate'	=>	function($cdnHost,$optionKey,$optionMeta,$savedValue) {
							if ($cdnHost == $savedValue) return $cdnHost; // no change
							$cdnHost = sanitize_url(rtrim(strtolower(trim($cdnHost)),'/'), ['http', 'https']);
							if (empty($cdnHost)) return $savedValue;
							$response = wp_remote_get(
								$cdnHost, ['method'=>'HEAD', 'headers'=>['Referer'=>home_url()]]
							);
							if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) > 399) {
								$this->add_option_error($optionKey,"Error accessing {$cdnHost}\n ".
									wp_remote_retrieve_response_message( $response )
								);
								return $savedValue;
							}
							$_POST[$optionKey] = $cdnHost;
							return $cdnHost;
						}
			),
*/
		],
];


do_action('eacReadme_load_parser'); 	// loads \eacParseReadme static class
if (! class_exists('\eacParseReadme'))
{
	$url = admin_url("/plugin-install.php?s=eacreadme&tab=search&type=term");
	$this->term_option_fields['github_hosting'] = array(
			'_github_display'		=> array(
					'type'		=> 	'display',
					'label'		=> 	'Required Plugin',
					'default'	=> 	"GitHub Hosting requires the installation of the " .
									"<a href='{$url}'>{eac}Readme</a> plugin."
			)
	);
}
else if ($term
	 &&	 ($slug = $this->get_term_meta($term->term_id, 'github_plugin_slug'))
	 &&	 ($repo = $this->get_term_meta($term->term_id, 'github_repository')) )
{
	$this->set_transient($this->github_transient,'enabled');
	$this->get_working_path(dirname($slug));
//	$cdnhost 	= $this->get_term_meta($term->term_id, 'github_cdn_root');
	$cdnhost	= (defined( 'EAC_GITHUB_CDN_HOST' ) && is_string( EAC_GITHUB_CDN_HOST ))
					? EAC_GITHUB_CDN_HOST : false;
	/**
	 * filter {classname}_github_cdn_host to override url cdn host
	 * @param	string|bool default cdn host
	 * @param	string		plugin slug
	 * @return	string|bool cdn host
	 */
	$cdnhost 	= $this->apply_filters('github_cdn_host',$cdnhost,$slug);
	$homeURL 	= ($cdnhost) ? $cdnhost : home_url();
	$rootURL 	= trailingslashit( ($cdnhost) ? $cdnhost : $this->github_api_path );
	$update 	= $rootURL.$term->slug.'.json';
	$download 	= $rootURL.$term->slug.'.zip';
	$plugins 	= $rootURL.'plugin_info'.'.json';
	$assets 	= $this->LOCAL_URL;
	$this->term_option_fields['github_hosting']['_github_display'] = array(
					'type'		=> 	'display',
					'label'		=> 	'Plugin URLs',
					'default'	=> 	"Plugin API Update URI: <small><em>use this in your plugin header for automatic updates.</em>" .
										"<br>&nbsp;-&nbsp;<a href='{$update}' target='_blank'>".str_replace($homeURL,'',$update)."</a></small><br>" .
									"Plugin API Update Info: <small><em>use for updates with update_plugins_{hostname} filter.</em>" .
										"<br>&nbsp;-&nbsp;<a href='{$plugins}' target='_blank'>".str_replace($homeURL,'',$plugins)."</a></small><br>" .
									"Download Link: <small><em>redirects to the current version zip file.</em>" .
										"<br>&nbsp;-&nbsp;<a href='{$download}'>".str_replace($homeURL,'',$download)."</a></small><br>" .
									"Asset Link: <small><em>use this for image links.</em>" .
										"<br>&nbsp;-&nbsp;<a href='{$assets}' target='_blank'>".str_replace($homeURL,'',$assets)."</a>/{asset.ext}</small>",
	);

	if (\wp_using_ext_object_cache() && \wp_cache_supports( 'flush_group' ))
	{
		$this->term_option_fields['github_hosting']['_github_cache'] = array(
						'type'		=> 	'button',
						'label'		=> 	'Clear Cache',
						'default'	=> 	'Erase',
						'info'		=>	"Erase the '".$this->className."' object cache.",
						'validate'	=>	function() {\wp_cache_flush_group($this->className);},
		);
	}
} else {
	$this->delete_transient($this->github_transient); // not necessarily
}

$licenseOptions = array(
			"_license_help"		=> 	array(
						'type'		=>	'help',
						'label'		=>	'License Limitations',
						'title'		=>	'Based on the license level assigned to a registration...',
						'help'		=>	'<details><summary>[title]</summary> we can limit the optional '.
										'values in the registration API (count, variations, options, domains & sites). '.
										'This allows APIs (possibly from multiple sources) to register any/all values for '.
										'these options while filtering here on the registration server, '.
										'providing an effective (albeit rudimentary) licensing validation.<br>'.
										'For each license level (' .
										$this->plugin->implode_with_keys(', ',array_flip($this->plugin->REGISTRY_LICENSE_LEVEL)) .
										') you may set a limit for count, variations, options, domains, and sites.' .
										'<ul>' .
										'	<li>For count, the value passed through the API is limited to a maximum value.' .
										'	<li>For variations, options, domains, and sites, the arrays passed through the API are sliced to a maximum number of elements.' .
										'</ul></details>',
			)
);
$licenseArray = $this->plugin->REGISTRY_LICENSE_LEVEL;
foreach($licenseArray as $key=>$value) // 'Lite'=>'L1'
{
	$licenseOptions["_license_{$value}"]	= array(
						'type'		=> 	'display',
						'label'		=> 	"<em>{$key}</em>",
						'default'	=> 	"<p>Limitations for license level {$value}</p>",
			);
	foreach (['count','variations','options','domains','sites'] as $name)
	{
		$licenseOptions["registrar_{$value}_{$name}"] 	= array(
						'type'		=> 	'number',
						'label'		=> 	"&nbsp;&nbsp;&nbsp;{$name}",
						'attributes'=>	['placeholder=\'unlimited\'','min=1','step=1','max=99999999'],
						'help'		=> 	false,
			);
	}
}
$this->term_option_fields['license_limitations'] = $licenseOptions;
