<?php
namespace EarthAsylumConsulting\Extensions;

/**
 * EarthAsylum Consulting {eac}SoftwareRegistration software product taxonomy
 *
 * @category	WordPress Plugin
 * @package		{eac}SoftwareRegistry
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.earthasylum.com>
 * @version		1.x
 */

trait software_product_github_hosting
{
	/**
	 * @var string trait version
	 */
	private $TRAIT_VERSION 		= '24.0225.1';

	/**
	 * @var string local folder
	 */
	private $LOCAL_PATH 		= '';

	/**
	 * @var string root url to plugin download and assets
	 */
	private $LOCAL_URL 			= '';

	/**
	 * @var string local asset folder (link images here)
	 */
	private $LOCAL_ASSETS 		= '';

	/**
	 * @var string the update time from repository/readme
	 */
	private $LAST_UPDATE 		= null;

	/**
	 * @var string temp file folder
	 */
	private $TEMP_PATH 			= '';

	/**
	 * @var array WP asset folder to download
	 */
	private $WP_ASSETS 			= [
		/* excluded from plugin */	['.wp-assets','.wordpress-org','wp-assets'],
		/* included in plugin */	['assets']
	];

	/**
	 * @var array image extensions
	 */
	private $IMAGE_TYPES 		= ['bmp','gif','jpg','jpeg','png','svg','webp'];

	/**
	 * @var array the current term (as array), with meta data and extended properties
	 */
	private $options 			= [
		'plugin_slug' 			=> '',					// WordPress dirname/slugname.php
		'repository' 			=> '',					// Github {owner}/{repo}
		'token'					=> '',					// personal access token
		'readme'				=> '/readme.txt',		// path within repository to readme.txt
		'source'				=> 'either',			// 'latest_release' | 'default_branch' | 'any'
		// not from term, may be set from repository custom property (wp-sections)
		'sections' 				=> [
			'description'				=>	'description',
			'description/summary'		=>	'description',
			'installation'				=>	'installation',
			'faq'						=>	'faq',
			'frequently asked questions'=>	'faq',
			'screenshots'				=>	'screenshots',
			'changelog'					=>	'changelog',
			'reviews'					=>	'reviews',
			'other notes'				=>	'other_notes',
			// non-standard
			'upgrade notice'			=>	'upgrade_notice',
			'additional information'	=>	'additional_information',
			'copyright'					=>	'copyright'
		],
		'override'				=> [/* name => value */],
	];

	/**
	 * @var array route pieces (slug,type)
	 */
	private $route 				= [];

	/**
	 * @var string plugin slug (dirname)
	 */
	private $plugin_slug 		= null;

	/**
	 * @var string plugin url
	 */
	private $plugin_url 		= null;

	/**
	 * @var array github repository
	 */
	private $repository 		= null;

	/**
	 * @var bool is this repository private
	 */
	private $isPrivate 			= false;

	/**
	 * @var array github release (latest | branch)
	 */
	private $release 			= null;

	/**
	 * @var string transient name
	 */
	private $github_transient 	= 'github_is_enabled';

	/**
	 * @var string api endpoint
	 */
	private $github_namespace 	= null;

	/**
	 * @var string api path - /wp-json/namespace
	 */
	private $github_api_path	= null;

	/**
	 * @var object WP_Filesystem
	 */
	private $fs					= null;

	/**
	 * @var int default cache setting
	 */
	private $cache_default		= HOUR_IN_SECONDS * 4;


	/**
	 * Github API Constructor.
	 * called from eacSoftwareRegistry taxonomy register_taxonomy
	 */
	protected function github_hosting_construct(): void
	{
		if ($this->github_is_enabled())
		{
			// same prefix as main plugin ------- softwareregistry----------------- /v1
			$this->github_namespace = $this->plugin::CUSTOM_POST_TYPE . $this->plugin::API_VERSION . '/swupdate';
			// may override with shortcut constant
			$this->github_api_path 	= trailingslashit(home_url(rest_get_url_prefix()).'/'.$this->github_namespace);

			$this->init_register_rewrites();
			add_action( 'rest_api_init', 	array($this, 'init_register_routes') );

			$this->fs = \eacDoojigger()->fs->link_wp_filesystem(true,
					'GitHub Hosting requires WordPress file access to manage assets.'
			);
		}
	}


	/**
	 * see if we have any products (terms) with github hosting enables
	 *
	 * @return bool
	 */
	protected function github_is_enabled(): bool
	{
		if ($this->get_transient($this->github_transient))
		{
			return true;
		}
		$terms = get_terms( ['taxonomy' => self::TAXONOMY_NAME, 'hide_empty' => false] );
		if (is_wp_error($terms)) return false;
		foreach ($terms as $term)
		{
			if ( ($this->get_term_meta($term->term_id, 'github_plugin_slug'))
			&&   ($this->get_term_meta($term->term_id, 'github_repository')) )
			{
				$this->set_transient($this->github_transient,'enabled');
				return true;
			}
		}
		return false;
	}


	/**
	 * get the root folder(s) & urls for local storage
	 *
	 * @param string $slugdir the plugin name
	 */
	protected function get_working_path(string $slugdir): void
	{
		if (defined( 'EAC_GITHUB_HOSTING_DIR' ) && is_string( EAC_GITHUB_HOSTING_DIR )) {
			$this->LOCAL_URL 	= trailingslashit(home_url(EAC_GITHUB_HOSTING_DIR));
			$this->LOCAL_PATH 	= trailingslashit($this->varServer('document_root').EAC_GITHUB_HOSTING_DIR);
		} else {
			$this->LOCAL_URL	= trailingslashit(wp_get_upload_dir()['baseurl']).'swupdate/';
			$this->LOCAL_PATH 	= trailingslashit(wp_get_upload_dir()['basedir']).'swupdate/';
		}

		$this->LOCAL_PATH 		.= strtolower($slugdir);
		$this->LOCAL_ASSETS 	 = $this->LOCAL_PATH.'/assets';
		$this->LOCAL_URL 		.= strtolower($slugdir).'/assets';

		if ($this->fs)
		{
			if (!$this->fs->is_dir($this->LOCAL_PATH )) {
				$this->fs->mkdir($this->LOCAL_PATH,FS_CHMOD_DIR | 0660);
				$this->cache_default = 0;
			}
			// because touch() doesn't always work via $fs
			$this->LAST_UPDATE	= ($this->fs->is_file($this->LOCAL_PATH .'/.github_last_update'))
					? strtotime($this->fs->get_contents($this->LOCAL_PATH .'/.github_last_update')) : 0;
			if (!$this->LAST_UPDATE) {
				$this->cache_default = 0;
			}
		}

		$this->TEMP_PATH = rtrim(get_temp_dir(),'/'); // wp temp folder path
	}


	/**
	 * github request stream context for remote file_get_contents()
	 *
	 * @param 	string	$context - json | zip | text
	 * @return	resource context with headers
	 */
	private function github_stream_context(string $context='json')
	{
		static $github_header = "X-GitHub-Api-Version: 2022-11-28" . "\r\n";
		static $agent_header = null;

		if (!$agent_header) {
			require ABSPATH . WPINC . '/version.php';
			$agent_header = "User-Agent: " .
							'WordPress/'.$wp_version.'; ' .
							$this->pluginName.'/'.$this->TRAIT_VERSION.'; ' .
							home_url( '/' ) . "\r\n";
		}

		$auth_header = ($this->options['token'])
				? "Authorization: Token ".$this->options['token'] . "\r\n" : "";

		switch ($context) {
			case 'zip':
				$accept_header = "Accept: application/octet-stream, application/zip" . "\r\n";
				break;
			case 'text':
				$accept_header = "Accept: text/plain" . "\r\n";
				break;
			case 'none':
				$accept_header = "";
				break;
			case 'json':
			default:
				$accept_header = "Accept: application/vnd.github+json, application/json" . "\r\n";
		}


		$context = stream_context_create(array(
			'http'	=> array(
				'method'	=> 	"GET",
				'header'	=> 	$auth_header 	.
								$accept_header 	.
								$github_header 	.
								$agent_header,
			)
		));
		return $context;
	}


	/**
	 * Register url rewrites, allow shortcut to /wp-json/namespace
	 *
	 */
	public function init_register_rewrites(): void
	{
		global $wp_rewrite;

		// api_shortcut replaces /wp-json/namespace in request urls
		$api_shortcut = (defined( 'EAC_GITHUB_API_SHORTCUT' ) && is_string( EAC_GITHUB_API_SHORTCUT ))
					? EAC_GITHUB_API_SHORTCUT
					: false;
		/**
		 * filter {classname}_github_api_shortcut to override url shortcut prefix
		 * @param	string|bool default shortcut prefix or false
		 * @return	string|bool shortcut prefix or false
		 */
		$api_shortcut = $this->apply_filters('github_api_shortcut',$api_shortcut);

		if (empty($api_shortcut)) return;

		$api_shortcut = trim($api_shortcut,'/');
		$this->github_api_path = trailingslashit(home_url( $api_shortcut ));

		$expresion	= '^' . $api_shortcut . '/(branch/|release/)?(.+)([\.\-\+])(json|zip|proxy)(\?.*)?$';
		$endpont	= 'index.php?rest_route=/'.$this->github_namespace.'/$matches[1]$matches[2]$matches[3]$matches[4]';
		$rules 		= get_option( 'rewrite_rules', array() );

		add_rewrite_rule($expresion,$endpont,'top');
		if ( ! isset($rules[$expresion]) ) {
			flush_rewrite_rules();
		}
	}


	/**
	 * Register WP REST api endpoints
	 *
	 */
	public function init_register_routes($restServer): void
	{
		if (defined( 'EAC_GITHUB_CACHE_LIFETIME' ) && is_int( EAC_GITHUB_CACHE_LIFETIME )) {
			$this->cache_default = EAC_GITHUB_CACHE_LIFETIME;
		}

		/* when getting the info/update api json file */
		register_rest_route
		(
			$this->github_namespace, '/(?P<route_source>branch/|release/)?(?P<route_slug>.+)(?P<route_delim>[\.\-\+])json',
			array([
				'methods'				=> 'GET',
				'callback'				=> array( $this, 'rest_api_info' ),
				'permission_callback' 	=> array( $this, 'rest_api_authentication' ),
				'args'					=> array(
					'route_type'		=> ['default' => 'info'],
					'cache'				=> ['default' => $this->cache_default],
				),
			])
		);

		/* when downloading via the WordPress plugin update api (download_link/package) */
		register_rest_route
		(
			$this->github_namespace, '/(?P<route_source>branch/|release/)(?P<route_slug>.+)[\.\-\+]proxy',
			array([
				'methods'				=> 'GET',
				'callback'				=> array( $this, 'rest_api_proxy' ),
				'permission_callback' 	=> array( $this, 'rest_api_authentication' ),
				'args'					=> array(
					'route_type'		=> ['default' => 'proxy'],
					'cache'				=> ['default' => $this->cache_default],
				),
			])
		);

		/* when downloading zip file (i.e. from external link) */
		register_rest_route
		(
			$this->github_namespace, '/(?P<route_source>branch/|release/)?(?P<route_slug>.+)[\.\-\+]zip',
			array([
					'methods'				=> 'GET',
					'callback'				=> array( $this, 'rest_api_download' ),
					'permission_callback' 	=> array( $this, 'rest_api_authentication' ),
					'args'					=> array(
						'route_type'		=> ['default' => 'download'],
						'cache'				=> ['default' => $this->cache_default],
					),
			])
		);
	}


	/**
	 * REST API Authentication
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @return 	bool|WP_Error
	 */
	public function rest_api_authentication(\WP_REST_Request $request)
	{
		$this->route = [
			'slug' 		=> sanitize_text_field($request->get_param('route_slug')), 		// plugin name
			'type' 		=> sanitize_text_field($request->get_param('route_type')),		// info|proxy|download
			'source'	=> sanitize_text_field($request->get_param('route_source')),	// branch|release
			'delim' 	=> sanitize_text_field($request->get_param('route_delim')),		// .|-|+
		];

		/* validate type */
		if (! in_array($this->route['type'],['info','proxy','download']) )
		{
			return $this->log_response($request,
				new \WP_Error( 'github_invalid_route', __("invalid route type requested"),
					[ 'status' => 400, 'route' => $this->route ])
			);
		}

		/* validate source */
		if ($this->route['source'])
		{
			switch (trim($this->route['source'],'/')) {
				case 'branch':
				case 'default_branch':
					$this->route['source'] = 'default_branch';
					break;
				case 'release':
				case 'latest_release':
					$this->route['source'] = 'latest_release';
					break;
				case 'any':
				case 'either':
					$this->route['source'] = 'either';
					break;
				default:
					return $this->log_response($request,
						new \WP_Error( 'github_invalid_source', __("invalid route source requested"),
							[ 'status' => 400, 'route' => $this->route ])
					);
			}
		}

		/* check for / validate registration/authorization */
		if ($registration = $request->get_header('Authorization'))
		{
			list ($authType,$registration) = explode(' ',$registration);
			switch (strtolower($authType))
			{
				case 'token':
				case 'bearer':
					$registration = base64_decode($registration);
					break;
				default:
					return $this->log_response($request,
						new \WP_Error( 'github_unauthorized', __("unauthorized request"),
							[ 'status' => 401, 'route' => $this->route ])
					);
			}
		} else {
			$registration = sanitize_text_field($request->get_param('registration'));
		}

		/* validate registration */
		if ($registration)
		{
			$product = ($this->route['slug'] != 'plugin_info') ? $this->route['slug'] : '*';
			$registration = $this->apply_filters('get_registration',
								$registration,
								['registry_product' => $product]
			);
			if (is_wp_error($registration) || !is_array($registration) || (in_array($registration['registry_status'],['expired','terminated'])))
			{
				return $this->log_response($request,
					new \WP_Error( 'github_unauthorized', __("invalid or inactive registration"),
						[ 'status' => 401, 'route' => $this->route ])
				);
			}
		}

		/* validate slug (term) */
		if ($this->route['slug'] != 'plugin_info')
		{
			if (! $term = get_term_by( 'slug', $this->route['slug'], self::TAXONOMY_NAME))
			{
				return $this->log_response($request,
					new \WP_Error( 'github_invalid_slug', __("requested slug name not found"),
						[ 'status' => 404, 'route' => $this->route ])
				);
			}

			if ( (! $this->get_term_meta($term->term_id, 'github_plugin_slug'))
			||   (! $this->get_term_meta($term->term_id, 'github_repository')) )
			{
				return $this->log_response($request,
					new \WP_Error( 'github_invalid_config', __("requested slug not configured"),
						[ 'status' => 412, 'route' => $this->route ])
				);
			}
		}

		/* set, maybe disable caching */
		$cache_ttl = $request->get_param('cache');
		if (! wp_using_ext_object_cache() || $this->isFalse($cache_ttl)) {
			$this->cache_default = 0;
		} else if (is_int($cache_ttl)) {
			$this->cache_default = $cache_ttl;
		}

		/**
		 * filter {classname}_github_api_auth to override rest authentication
		 * @param	bool 	is authenticated (true)
		 * @param 	object	WP_REST_Request Request object.
		 * @param 	array	route [type,slug]
		 * @return	bool|WP_Error
		 */
		return $this->apply_filters('github_api_auth',true,$request,$this->route);
	}


	/**
	 * rest_api_info - json formatted plugin data
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @return 	array|WP_Error - plugin_info passed to WP_REST_Response
	 */
	public function rest_api_info(\WP_REST_Request $request)
	{
		/**
		 * action {classname}_github_api_info
		 * @param 	object	WP_REST_Request Request object.
		 */
		$this->do_action('github_api_info',$request);

		if ($this->route['slug'] == 'plugin_info')
		{
			set_time_limit(120);
			$terms = get_terms( ['taxonomy' => self::TAXONOMY_NAME, 'hide_empty' => false] );
			if (is_wp_error($terms))
			{
				return $this->log_response($request,$terms);
			}
			$result = $package = [];
			foreach ($terms as $term)
			{
				$this->route['slug'] = $request['route_slug'] = $term->slug;
				if ( (! $this->get_term_meta($term->term_id, 'github_plugin_slug'))
				||   (! $this->get_term_meta($term->term_id, 'github_repository')) )
				{
					continue;
				}
				$plugin = $this->get_from_repository($request);
				if (!is_wp_error($plugin))
				{
					$result[ $plugin['slug'] ] = $plugin[ $plugin['slug'] ];
					$package[] 	= $plugin['eac_github_hosting']['package'];
				}
			}
			$result['eac_github_hosting'] = $plugin['eac_github_hosting'];
			$result['eac_github_hosting']['package'] = $package;
			$this->route['slug'] = $request['route_slug'] = 'plugin_info';

			/**
			 * filter {classname}_github_api_plugin_info
			 * @param 	array	$plugin array.
			 * @param 	object	WP_REST_Request Request object.
			 */
			return $this->log_response($request,
				$this->apply_filters('github_api_plugin_info',$result,$request)
			);
		}

		/**
		 * filter {classname}_github_api_info_result
		 * @param 	array	$plugin_info array.
		 * @param 	object	WP_REST_Request Request object.
		 */
		return $this->log_response($request,
			$this->apply_filters('github_api_info_response',$this->get_from_repository($request),$request)
		);
	}


	/**
	 * rest_api_proxy - download via proxy
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @return 	void|WP_Error
	 */
	public function rest_api_proxy(\WP_REST_Request $request)
	{
		/**
		 * action {classname}_github_api_proxy
		 * @param 	object	WP_REST_Request Request object.
		 */
		$this->do_action('github_api_proxy',$request);

		$plugin_info = $this->get_from_repository($request);

		$this->log_response($request,$plugin_info);

		if (is_wp_error($plugin_info)) return $plugin_info;

		return $this->get_proxy_download($request,$plugin_info);
	}


	/**
	 * rest_api_download - redirect to version download file
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @return 	rest response|WP_Error
	 */
	public function rest_api_download(\WP_REST_Request $request)
	{
		/**
		 * action {classname}_github_api_diirect
		 * @param 	object	WP_REST_Request Request object.
		 */
		$this->do_action('github_api_download',$request);

		$plugin_info = $this->get_from_repository($request);

		$this->log_response($request,$plugin_info);

		if (is_wp_error($plugin_info)) return $plugin_info;

		return $this->get_redirect_download($request,$plugin_info);
	}


	/**
	 * get plugin information from github repository
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @return 	array|WP_Error
	 */
	private function get_from_repository(\WP_REST_Request $request)
	{
		/* get term values for options */
		$term = get_term_by( 'slug', $this->route['slug'], self::TAXONOMY_NAME);

		$this->options 	= array_merge($this->options,get_object_vars($term));
		$termMeta 		= $this->get_term_meta($term->term_id);
		foreach ($termMeta as $name => $value) {
			if (substr($name,0,6) == 'github') {
				$this->options[substr($name,7)] = $value;				// e.g. github_repository => repository
			}
		}
		unset($term,$termMeta);
		//$this->options['sections'] 	= ?; 							// section names to include
		//$this->options['override'] 	= ?;							// override headers

		/* source from route override */
		if ($this->route['source'])
		{
			$this->options['source'] = $this->route['source'];
		}

		/* if environment parameter - ?environment=production, decide source for production/non-production */
		if ($this->options['source'] == 'either')
		{
			if ($param = $request->get_param('environment')) {
				$this->options['source'] = ($param == 'production') ? 'latest_release' : 'default_branch';
			}
		}

		$this->plugin_slug 		= dirname($this->options['plugin_slug']);	// plugin name/directory
		$this->get_working_path($this->plugin_slug);
		$this->plugin_url 		= dirname($this->LOCAL_URL);				// download url

		/* check for cached version of this request */
		$wp_cache_key = $this->TRAIT_VERSION . '|' .
						$this->route['slug'] . '|' .
						$this->route['type'] . '|' .
						$this->options['source'];
		if ($plugin_info = $this->getPluginCache($wp_cache_key)) return $plugin_info;

		/* loads eacParseReadme static class */
		do_action('eacReadme_load_parser');
		if (! class_exists('\eacParseReadme'))
		{
			return new \WP_Error( 'github_no_parser', 'unable to load the readme.txt parser class, {eac}Readme plugin required.',
					[ 'status' => 500 ]);
		}

		/* get the github repository */
		$this->repository 			= $this->getPluginRepository();
		if (is_wp_error($this->repository))
		{
			return $this->repository;
		}

		/* get the github latest release or default branch */
		$this->release 				= $this->getPluginRelease();
		if (is_wp_error($this->release))
		{
			return $this->release;
		}

		$this->isPrivate			= $this->repository['private'];

		/* load the remote readme.txt file */
		$this->options['readme'] 	= $this->getPluginReadme();
		if (is_wp_error($this->options['readme']))
		{
			return $this->options['readme'];
		}

		/* process the readme file */
		$plugin_info 				= $this->getPluginInfo($request);

		/* this really shouldn't happen, but... */
		if (! $plugin_info["download_link"])
		{
			return new \WP_Error( 'github_not_found', __("requested file not found"),
					[ 'status' => 404, 'route' => $this->route ]);
		}

		/* cache the results */
		$this->putPluginCache($wp_cache_key,$plugin_info);

		/* return the results */
		return $plugin_info;
	}


	/**
	 * getPluginCache - get from WP object cache
	 *
	 * @param 	string 	cache key
	 * @return 	array 	plugin_info array
	 */
	private function getPluginCache(string $wp_cache_key)
	{
		if (! $this->isFalse($this->cache_default))
		{
			if ($plugin_info = wp_cache_get($wp_cache_key,$this->className)) {
				return $plugin_info;
			}
		} else {
			wp_cache_delete($wp_cache_key,$this->className);
		}
		return false;
	}


	/**
	 * putPluginCache - put to WP object cache
	 *
	 * @param 	string 	cache key
	 * @param 	array 	plugin_info array
	 * @return 	void
	 */
	private function putPluginCache(string $wp_cache_key, array $plugin_info)
	{
		if (! $this->isFalse($this->cache_default))
		{
			wp_cache_set($wp_cache_key,$plugin_info,$this->className,$this->cache_default);
		}
	}


	/**
	 * getPluginRepository - get github repository
	 *
	 * @return array|WP_Error github repository array
	 */
	private function getPluginRepository()
	{
		/* set the stream context for github reading */
		$context = $this->github_stream_context('json');

		/* get the repository */
		$contentURL = sprintf("https://api.github.com/repos/%s",
						$this->options['repository']
					);

		if (! $content = @file_get_contents($contentURL,false,$context))
		{
			return new \WP_Error( 'github_no_repository', 'unable to access repository '.$this->options['repository'],
				[	'status'	=> 400,
					'url'		=> $contentURL,
					'route' 	=> $this->route,
					'response'	=> $http_response_header
				]);
		}

		return json_decode($content,true);
	}


	/**
	 * getPluginRelease - get github release
	 *
	 * @return array|WP_Error github release array
	 */
	private function getPluginRelease()
	{
		/* set the stream context for github reading */
		$context = $this->github_stream_context('json');

		/* get the latest release */
		if (in_array($this->options['source'],['either','latest_release']))
		{
			$contentURL = sprintf("https://api.github.com/repos/%s/releases/latest",
							$this->repository['full_name']
						);

			if ($content = @file_get_contents($contentURL,false,$context)) {
				$this->options['source'] = 'latest_release';
				return json_decode($content,true);
			}
		}

		/* use the default branch to populate release array */
		if (in_array($this->options['source'],['either','default_branch']))
		{
			$contentURL = sprintf("https://api.github.com/repos/%s/branches/%s",
								$this->repository['full_name'],
								$this->repository['default_branch']
						);

			if ($content = @file_get_contents($contentURL,false,$context)) {
				$content = json_decode($content,true);
				$this->options['source'] = 'default_branch';
				return [
					'url' 			=> $content['_links']['self'],
					'html_url'		=> $content['_links']['html'],
					'author'		=> $content['commit']['author'],
					'tag_name' 		=> $this->repository['default_branch'], // 'main' or 'master'
					'created_at' 	=> $this->repository['created_at'],
					'updated_at' 	=> $this->repository['updated_at'],
				//	'published_at' 	=> max($this->repository['pushed_at'],$this->repository['updated_at']),
					'published_at' 	=> $content['commit']['commit']['committer']['date'],
					'zipball_url'	=> sprintf("https://api.github.com/repos/%s/zipball/%s",
										$this->repository['full_name'],
										$this->repository['default_branch']
									),
				];
			}
		}

		/* no release and/or branch */
		return new \WP_Error( 'github_no_source', 'unable to read '.$this->options['source'].' from repository',
			[	'status'	=> 400,
				'url'		=> $contentURL,
				'route' 	=> $this->route,
				'response'	=> $http_response_header
			]);
	}


	/**
	 * getPluginReadme - load the remote readme file
	 *
	 * @return string|WP_Error readme url
	 */
	private function getPluginReadme()
	{
		$readme = ltrim($this->options['readme'],'/');
		if (empty($readme))
		{
			return $readme;
		}

		/* set the stream context for github reading */
		$context = $this->github_stream_context('text');

		$readme 	= sprintf("https://raw.githubusercontent.com/%s/%s/%s",
									$this->repository['full_name'],
									$this->release['tag_name'],
									$readme,
								);

		/* load the readme file */
		if (!\eacParseReadme::loadFile($readme,$context))
		{
			return new \WP_Error( 'github_no_readme', 'unable to process '.$this->options['readme'].' from repository',
				[	'status'	=> 400,
					'file'		=> $readme,
					'response'	=> $http_response_header
				]);
		}

		return $readme;
	}


	/**
	 * getPluginInfo - get plugin_info array from readme.txt file
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @return 	array 	plugin_info array
	 */
	private function getPluginInfo(\WP_REST_Request $request)
	{
		/* some defaults from the repository/release used if missing from or no readme.txt */
		$homepage 		= $this->repository['homepage'] 	?? ($this->repository['html_url'] ?? $this->plugin_url);
		$description 	= $this->repository['description'] 	?? $this->options['description'];
		$tags 			= $this->repository['topics'] 		? array_combine($this->repository['topics'],$this->repository['topics']) : [];
		$version 		= $this->release['tag_name'] 		?? null;
		$lastupdate 	= $this->release['published_at'] 	?? null;
		if ($author 	= $this->repository['organization'] ?? ($this->repository['owner'] ?? null)) {
			$author 	=  "<a href='".$author['html_url']."'>".$author['login']."</a>";
		}
		$contributors 	= isset($this->release['author'])
							? [$this->release['author']['login'] => [
								'display_name' 	=> $this->release['author']['login'],
								'profile' 		=> $this->release['author']['html_url'],
								'avatar' 		=> $this->release['author']['avatar_url']]
							] : null;
		$changelog 		= $this->release['body'] 			?? null;

		/* get values from readme.txt */
		$plugin_info = array
		(
			"slug" 			=> 	dirname($this->options['plugin_slug']),					// plugin directory
			"plugin" 		=> 	$this->options['plugin_slug'],							// main plugin file once installed
			"name" 			=> 	\eacParseReadme::getTitle() 		?: $this->options['name'],	// title
			"homepage" 		=> 	\eacParseReadme::getHomepage() 		?: $homepage,		// url
			"description" 	=> 	\eacParseReadme::getShortDescription() ?: $description,	// short description
			"tags" 			=> 	\eacParseReadme::getTags(true) 		?: $tags,			// upto 12 tags
			"version" 		=> 	\eacParseReadme::getVersion() 		?: $version,		// current version "Stable tag"
			"last_updated" 	=> 	\eacParseReadme::getLastUpdated() 	?: $lastupdate,		// Last update date/time (default, reset below)
			"requires" 		=> 	\eacParseReadme::getRequiresAtLeast(),					// WordPress version minimum "Requires at least"
			"tested" 		=> 	\eacParseReadme::getTestedUpTo(),						// WordPress version tested "Tested up to"
			"requires_php" 	=> 	\eacParseReadme::getRequiresPHP(),						// PHP version minimum "Requires PHP"
			"author"		=> 	\eacParseReadme::getAuthor() 		?: $author,			// author [display name](url)
			'contributors' 	=> 	\eacParseReadme::getContributors(true) ?: $contributors,	// 'user' => [display_name=>, profile=>, avatar=>]
			"download_link" => 	false,													// source zip file
			"sections" 		=> 	array(),												// parsed below
		);

		/* custom repository properties (prefixed with 'wp-') assigned to the repository */
		if (isset($this->repository['custom_properties']))
		{
			foreach ($this->repository['custom_properties'] as $name => $value)
			{
				if (substr($name,0,3) != 'wp-') continue;
				if ($name == 'wp-sections') { // wp-sections = "description,changelog,..."
					$sections = array_map('trim',
						explode("\n", str_replace([',',' '],"\n",strtolower(esc_attr($value))))
					);
					$this->option['sections'] = array_combine($sections, $sections);
				} else {
					$name = esc_attr(substr($name,3));
					if (!isset($plugin_info[$name]) || empty($plugin_info[$name])) {
						$plugin_info[$name] = esc_attr($value);
					}
				}
			}
		}

		/* remove non-numeric prefix in version strings (vn.n.n) */
		foreach (['version','requires','tested','requires_php'] as $name)
		{
			$value = preg_replace("|[1-9]+.*|",'$0',$plugin_info[$name]);
			if (!empty($value)) $plugin_info[$name] = $value;
		}

		/* for complete info array, not needed for downloads */
		if ($this->route['type'] == 'info')
		{
			/* additional header values */
			foreach ([	'added','requires_plugins','author_profile','donate_link','downloaded',
						'rating','num_ratings','ratings','active_installs',
						'support_url','support_threads','support_threads_resolved' ] as $optional)
			{
				if ($value = \eacParseReadme::getHeader(str_replace('_',' ',$optional))) {
					$plugin_info[$optional] = $value;
				}
			}

			/* get html section blocks */
			$plugin_info = $this->getPluginInfoSections($plugin_info);

			if (!empty($description) && empty($plugin_info['sections']['description'])) {
				$plugin_info['sections']['description'] = $description;
			}
			if (!empty($changelog) && empty($plugin_info['sections']['changelog'])) {
				$plugin_info['sections']['changelog'] = $changelog;
			}

			/* get banner & icon images */
			$plugin_info = $this->getPluginInfoAssets($plugin_info);

			/* force download link through api proxy */
			$source = ($this->options['source'] == 'default_branch') ? 'branch' : 'release';
			$this->options['override']['download_link'] =
				$this->github_api_path.$source.'/'.$this->route['slug'].$this->route['delim'].'proxy';
		}

		/* get download zip file */
		$plugin_info = $this->getPluginInfoDownload($plugin_info);

		/* clean up any empty values */
		foreach ($plugin_info as $name => $data)
		{
			if (empty($data)) unset($plugin_info[$name]);
		}

		/* override array, get from readme (*) or hard value */
		foreach ($this->options['override'] as $override => $value)
		{
			$plugin_info[$override] = ($value == '*') ? \eacParseReadme::getHeader($override) : $value;
		}

		/* check for a CDN name to use in our download url */
		$cdnhost = (defined( 'EAC_GITHUB_CDN_HOST' ) && is_string( EAC_GITHUB_CDN_HOST ))
					? EAC_GITHUB_CDN_HOST : null;
		/**
		 * filter {classname}_github_cdn_host to override url cdn host
		 * @param	string|bool default cdn host
		 * @param	string		plugin slug
		 * @return	string|bool cdn host
		 */
		$cdnhost 	= $this->apply_filters('github_cdn_host',$cdnhost,$plugin_info['plugin']);
		if ($cdnhost)
		{
			$plugin_info['download_link'] = str_replace(
				trailingslashit($this->github_api_path),
				trailingslashit($cdnhost),
				$plugin_info['download_link']
			);
		}

		/* for complete info array, not needed for downloads */
		if ($this->route['type'] == 'info')
		{
			/* add the plugins api update array to be used by update_plugins_{$hostname} filter */
			$plugin_info[$plugin_info['slug']]	= $this->getPluginInfoUpdate($request,$plugin_info);

			/* lastly, add some identifying & source info */
			$plugin_info['eac_github_hosting']  = $this->getPluginInfoTrait($request,$plugin_info);
		}

		/* return results */
		return $plugin_info;
	}


	/**
	 * getPluginInfoSections - get the json file sections
	 *
	 * @param 	array	plugin info array from getPluginInfo()
	 * @return 	array	$plugin_info
	 */
	private function getPluginInfoSections(array $plugin_info): array
	{
		/*
		 * 'other notes' should start with a header (= xxx =) as it may get conflated with 'description'
		 * by the WordPress parser, otherwise use 'additional information'
		 */

		/* get sections from readme.txt file */
		foreach ($this->options['sections'] as $input => $section)
		{
			if ($text = \eacParseReadme::getSection($input))
			{
				$plugin_info['sections'][str_replace(' ','_',$section)] = $text;
			}
		}

		/* if upgrade_notice has sub-sections, get only the current version block */
		if (isset($plugin_info['sections']['upgrade_notice']) && preg_match("/<h4>(.*)<\/h4>/m",$plugin_info['sections']['upgrade_notice']))
		{
			// include un-versioned at beginning
			if (preg_match("/^(.*)\n<h4>/m",$plugin_info['sections']['upgrade_notice'],$matches)) {
				$plugin_info['sections']['upgrade_notice'] = $matches[1].' '; //space needed when tags are stripped by WP
			} else {
				$plugin_info['sections']['upgrade_notice'] = '';
			}
			$versions  = explode('.',$plugin_info['version'].'.0.0');
			foreach (
				[	$plugin_info['version'],			// M.m.p
					"{$versions[0]}.{$versions[1]}",	// M.m
					"{$versions[0]}.{$versions[1]}+",	// M.m+
					"{$versions[0]}",					// M
					"{$versions[0]}+",					// M+
				] as $version)
			{
				if ( $text = \eacParseReadme::getSection("upgrade notice/{$version}") ) {
					$plugin_info['sections']['upgrade_notice'] .= $text;
					break;
				}
				if ( $text = \eacParseReadme::getSection("upgrade notice/version {$version}") ) {
					$plugin_info['sections']['upgrade_notice'] .= $text;
					break;
				}
			}
		}

		foreach ($plugin_info['sections'] as $name => $data)
		{
			if (empty($data)) unset($plugin_info['sections'][$name]);
		}

		return $plugin_info;
	}


	/**
	 * getPluginInfoAssets - get the banner & icon images
	 *
	 * @param 	array	plugin info array from getPluginInfo()
	 * @return 	array 	$plugin_info
	 */
	private function getPluginInfoAssets(array $plugin_info): array
	{
		$plugin_info['banners'] 	= array();
		$plugin_info['icons'] 		= array();

		/* see if we already have local assets */
		$plugin_info = $this->getPluginLocalAssets($plugin_info);
		if (strtotime($this->release['published_at']) <= $this->LAST_UPDATE) {
			return $plugin_info;
		}

		/* read the release tree looking for assets folder */
		$context = $this->github_stream_context('json');
		$contents = str_replace('{/sha}','/'.$this->release['tag_name'],$this->repository['trees_url']);
		if (! $contents = @file_get_contents($contents,false,$context)) {
			return $plugin_info;
		}
		$contents = json_decode($contents, true);

		/* find the assets folder */
		foreach ($contents['tree'] as $leaf)
		{
			if (in_array($leaf['path'],array_merge($this->WP_ASSETS[0],$this->WP_ASSETS[1])))
			{
				$assetPath = $leaf['path'];
				if (! $leaf = @file_get_contents($leaf['url'],false,$context)) {
					return $plugin_info;
				}
				$leaf = json_decode($leaf, true);
				break;
			}
			$leaf = null;
		}
		if (!$leaf) {
			return $plugin_info;
		}

		/* link to public blob doesn't work as needed */

		/* download and link to private image asset */
		if (TRUE || $this->isPrivate)
		{
			$this->fs->put_contents($this->LOCAL_PATH.'/.github_last_update',$this->release['published_at'],FS_CHMOD_FILE | 0660);

			if (!$this->fs->is_dir($this->LOCAL_ASSETS )) {
				$this->fs->mkdir($this->LOCAL_ASSETS,FS_CHMOD_DIR | 0660);
			}

			foreach ($leaf['tree'] as $asset)
			{
				$type = pathinfo($asset['path'], PATHINFO_EXTENSION);
				if (!in_array($type,$this->IMAGE_TYPES)) continue;
				if ($contents = @file_get_contents($asset['url'],false,$context))
				{
					$contents = json_decode($contents, true);
					$this->fs->put_contents($this->LOCAL_ASSETS.'/'.$asset['path'], base64_decode($contents['content']),FS_CHMOD_FILE | 0660);
					$this->fs->touch($this->LOCAL_ASSETS.'/'.$asset['path'],strtotime($this->release['published_at']));
				}
			}

			$plugin_info = $this->getPluginLocalAssets($plugin_info);
			return $plugin_info;
		}
		/* link to public assets - but then we don't have them for other uses */
		/*
		else
		{
			foreach ($leaf['tree'] as $asset)
			{
				foreach ($this->IMAGE_TYPES as $ext)
				{
					$assetUrl = sprintf("https://raw.githubusercontent.com/%s/%s/%s/%s",
											$this->repository['full_name'],
											$this->release['tag_name'],
											$assetPath,
											$asset['path']
										);

					switch ($asset['path'])
					{
						case $this->plugin_slug."-banner-772x250.{$ext}":
						case "banner-772x250.{$ext}":
							$plugin_info['banners']['low'] = $assetUrl;
							break;
						case $this->plugin_slug."-banner-1544x500.{$ext}":
						case "banner-1544x500.{$ext}":
							$plugin_info['banners']['high'] = $assetUrl;
							break;
						case $this->plugin_slug."-icon-128x128.{$ext}":
						case "icon-128x128.{$ext}":
							$plugin_info['icons']['low'] = $assetUrl;
							break;
						case $this->plugin_slug."-icon-256x256.{$ext}":
						case "icon-256x256.{$ext}":
							$plugin_info['icons']['high'] = $assetUrl;
							break;
						case $this->plugin_slug."-icon.{$ext}":
						case "icon.{$ext}":
							$plugin_info['icons'][$ext] = $assetUrl;
							break;
					}
				}
			}
		}
		*/

		return $plugin_info;
	}


	/**
	 * getPluginLocalAssets - get local banner & icon images
	 *
	 * @param 	array	plugin info array from getPluginInfo()
	 * @return 	array	$plugin_info
	 */
	private function getPluginLocalAssets(array $plugin_info): array
	{
		/* if assets folder exists, find banner(s) and/or icons */
		if (is_dir($this->LOCAL_ASSETS))
		{
			foreach ($this->IMAGE_TYPES as $ext)
			{
				foreach ([$this->plugin_slug.'-',""] as $slug)
				{
					// banners
					if (file_exists($this->LOCAL_ASSETS."/{$slug}banner-772x250.{$ext}")) {
						$plugin_info['banners']['low'] = $this->LOCAL_URL."/{$slug}banner-772x250.{$ext}";
					}
					if (file_exists($this->LOCAL_ASSETS."/{$slug}banner-1544x500.{$ext}")) {
						$plugin_info['banners']['high'] = $this->LOCAL_URL."/{$slug}banner-1544x500.{$ext}";
					}
					// icons
					if (file_exists($this->LOCAL_ASSETS."/{$slug}icon-128x128.{$ext}")) {
						$plugin_info['icons']['low'] = $this->LOCAL_URL."/{$slug}icon-128x128.{$ext}";
					}
					if (file_exists($this->LOCAL_ASSETS."/{$slug}icon-256x256.{$ext}")) {
						$plugin_info['icons']['high'] = $this->LOCAL_URL."/{$slug}icon-256x256.{$ext}";
					}
					if (file_exists($this->LOCAL_ASSETS."/{$slug}icon.{$ext}")) {
						$plugin_info['icons'][$ext] = $this->LOCAL_URL."/{$slug}icon.{$ext}";
					}
				}
			}
		}
		return $plugin_info;
	}


	/**
	 * getPluginInfoDownload - get the zip file to download
	 *
	 * @param 	array	plugin info array from getPluginInfo()
	 * @return 	array 	$plugin_info
	 */
	private function getPluginInfoDownload(array $plugin_info): array
	{
		/* default to download release zip, which generally won't work for WordPress */
		$plugin_info["download_link"] = $this->release['zipball_url'];
		$plugin_info["last_updated"]  = date('Y-m-d H:i:s P',strtotime($this->release['published_at']));
		$isZipball = true; // we need to reprocess (full repository) zipballs

		$maybe_files = [
				$this->plugin_slug."-{$this->release['tag_name']}.zip",	// default, using -tagname
				$this->plugin_slug.".{$plugin_info['version']}.zip",	// using .version
				$this->plugin_slug."_{$plugin_info['version']}.zip",	// using _version
				$this->plugin_slug.".zip"								// unversioned
		];
		$maybe_files = array_unique( array_merge($maybe_files,array_map('strtolower', $maybe_files)) );
		$downloadName = $maybe_files[0]; 								// default filename of user download

		/* look for existing, version-specific (or not) local zip file */
		if (strtotime($this->release['published_at']) <= $this->LAST_UPDATE)
		{
			foreach($maybe_files as $zipName)
			{
				if (file_exists($this->LOCAL_PATH.'/'.$zipName)) {
					$plugin_info["download_link"] = $this->plugin_url."/{$zipName}";
					return $plugin_info;
				}
			}
		}

		/*
		 * latest release or default branch not yet downloaded
		 */
		$asset = null;

		/* if {plugin}.zip is an asset in the repository... */
		if (array_key_exists('assets', $this->release))
		{
			foreach ($this->release['assets'] as $asset)
			{
				foreach($maybe_files as $zipName)
				{
					if ($asset['name'] == $zipName) {
						$isZipball 		= false;
						$downloadName 	= $zipName; // filename of user download
						// maybe link directly to public github repository
						$plugin_info["download_link"] = $asset['browser_download_url'];
						break 2;
					}
				}
				$asset = null;
			}
		}

		/* download private asset or re-zip zipball to remove non-plugin github files */
		if ($this->isPrivate || $isZipball)
		{
			$download = ($asset) ? $asset['url'] : $plugin_info["download_link"];
			$context = $this->github_stream_context( $isZipball ? 'none' : 'zip' );
			if ($download = @file_get_contents($download,false,$context))
			{
				$zipFile = ($isZipball ? $this->TEMP_PATH : $this->LOCAL_PATH).'/'.$downloadName;
				$this->fs->put_contents($zipFile, $download, FS_CHMOD_FILE | 0660);
				$this->fs->touch($zipFile,strtotime($this->release['published_at']));
				$this->fs->put_contents($this->LOCAL_PATH.'/.github_last_update',$this->release['published_at'],FS_CHMOD_FILE | 0660);
				$plugin_info["download_link"] = $this->plugin_url."/{$downloadName}";
			}
		}

		/* we need to download and re-build zip file */
		if ($isZipball && $zipFile)
		{
			$plugin_info = $this->getPluginDownloadFile($plugin_info,$zipFile,$downloadName);
		}

		return $plugin_info;
	}


	/**
	 * getPluginDownloadFile - unzip the zip file and recreate for WordPress
	 *
	 * @param 	array	$plugin_info plugin info array from getPluginInfo()
	 * @param 	string	$zipFile the path to the downloaded zip file.
	 * @param 	string	$downloadName the name to use for the resulting zip file.
	 * @return 	array 	$plugin_info
	 */
	private function getPluginDownloadFile(array $plugin_info, string $zipFile, string $downloadName): array
	{

		/* unzip and remove files/folders from zipball */
		$zipballPath = $this->TEMP_PATH."/temp_".$this->plugin_slug.'_zipball';
		$zip = new \ZipArchive;
		if ($zip->open($zipFile)) {
			$zip->extractTo($zipballPath);
			$zip->close();
		} else {
			return $plugin_info;
		}
		$this->fs->delete($zipFile);

		/* get ignored files (match with regex) */
		if (is_file($zipballPath.'/.distignore')) {
			$distignore 	= file($zipballPath.'/.distignore',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
		} else {
			$distignore 	= array_map(function($v){return '/'.preg_quote($v);},$this->WP_ASSETS[0]);
			$distignore[] 	= '/\\.+';
		}
		$distignore = array_filter(array_map(function($ignore) {
			$ignore = ltrim($ignore,' \t');
			if (empty($ignore) || $ignore[0] == '#') {
				$ignore = null;
			}
			if ($ignore[0] == '/') {
				$ignore = '^'.$this->plugin_slug.$ignore;
			}
			return $ignore;
		},$distignore));

		$foldersToDelete 	= array($zipballPath);
		$filesToDelete 		= array();
		$repoPath 			= str_replace('/','-',$this->repository['full_name']);
		$zipballPath 		= realpath($zipballPath);

		/* create zip as temp file */
		$tempZipFile 		= $this->TEMP_PATH.'/'.$downloadName;
		$zip = new \ZipArchive;
		$zip->open($tempZipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

		/* Create recursive directory iterator */
		$files = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($zipballPath,\FilesystemIterator::SKIP_DOTS),
					// get leaves and directories
					\RecursiveIteratorIterator::LEAVES_ONLY | \RecursiveIteratorIterator::SELF_FIRST
				);

		/* process files, add to zip */
		foreach ($files as $filePath => $file)
		{
			if ($file->isDir()) {
				$foldersToDelete[] 	= $filePath;
			} else {
				$filesToDelete[] 	= $filePath;
			}

			// get relative path for current file
			$filename = preg_filter(
					"|^".$repoPath."-(\w{5,})/|", "/",	// remove github path ({owner}-{repo}-{sha})
					substr($filePath, strlen($zipballPath) + 1)
			);
			if (empty($filename)) continue;				// empty {owner}-{repo}-{sha} folder
			$relativePath = $this->plugin_slug.$filename;

			// skip ignored files/folders
			foreach ($distignore as $ignore) {
				if (preg_match("|{$ignore}|", $relativePath)) {
					continue 2;
				}
			}

			if ($file->isDir()) {
				$zip->addEmptyDir($relativePath);
			} else {
				$zip->addFile($filePath, $relativePath);
			}
		}
		$zip->close();

		/* copy file to our local folder */
		$downloadFile = $this->LOCAL_PATH.'/'.$downloadName;
		// copy temp file to get owner/group set
		$this->fs->copy($tempZipFile, $downloadFile, true);
		unlink($tempZipFile);

		$this->fs->chmod($downloadFile, FS_CHMOD_FILE | 0660);
		$this->fs->touch($downloadFile,strtotime($this->release['published_at']));
		$plugin_info["download_link"] = $this->plugin_url."/".$downloadName;

		/* Delete all files from delete list, this folder is created by web user, not fs user */
		foreach ($filesToDelete as $file) {
			unlink($file);
		}
		foreach (array_reverse(array_unique($foldersToDelete)) as $file) {
			rmdir($file);
		}

		return $plugin_info;
	}


	/**
	 * getPluginInfoUpdate - get plugin api array [wp_update_plugins()] from plugin_info array
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @param 	array	plugin info array from getPluginInfo()
	 * @return 	array
	 */
	private function getPluginInfoUpdate(\WP_REST_Request $request, array $plugin_info): array
	{
		$update = array(
				'slug'				=> $plugin_info['slug'],
				'plugin' 			=> $plugin_info['plugin'],
				'version'			=> $plugin_info['version'],
				'url'				=> $plugin_info['homepage'],
				'package'			=> $plugin_info['download_link'],
				'requires'			=> $plugin_info['requires'],
				'tested'			=> $plugin_info['tested'],
				'requires_php'		=> $plugin_info['requires_php'],
			//	'translations'		=> [],
		);
		foreach (['banners','banners_rtl','icons'] as $type)
		{
			if (!empty($plugin_info[$type]))
			{
				$update[$type] = [
					'1x' 			=> $plugin_info[$type]['low'],
					'2x' 			=> $plugin_info[$type]['high'],
				];
				if (isset($plugin_info[$type]['svg'])) {
					$update[$type]['svg'] = $plugin_info[$type]['svg'];
				}
			}
		}
		if (!empty($plugin_info['section']['upgrade_notice']))
		{
			$update['upgrade_notice'] = $plugin_info['section']['upgrade_notice'];
		}

		return $update;
	}


	/**
	 * getPluginInfoTrait - add this trait array
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @param 	array	plugin info array from getPluginInfo()
	 * @return 	array
	 */
	private function getPluginInfoTrait(\WP_REST_Request $request, array $plugin_info): array
	{
		return [
			'version'		=> 	$this->TRAIT_VERSION,
			'host'			=> 	$request->get_header('host'),
			'request'		=> 	$this->varServer('request_uri'),
			'package'		=> 	[
				'plugin'	=> 	$plugin_info['plugin'],
				'repository'=> 	$this->repository['full_name'],
				'source'	=> 	$this->options['source'],
				'tag_name'	=> 	$this->release['tag_name'],
				'published'	=> 	$this->release['published_at'],
			],
		];
	}


	/**
	 * get_proxy_download - download the plugin file via proxy read
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @param 	array	plugin info array from getPluginInfo()
	 * @return 	void
	 */
	public function get_proxy_download(\WP_REST_Request $request, $plugin_info)
	{
		nocache_headers();
		$basename	= basename($plugin_info["download_link"]);
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=$basename");
		header("Content-Type: application/zip");
		$context 	= $this->github_stream_context('zip');
		//	$data 		= @file_get_contents($plugin_info["download_link"],false,$context);
		//	$filesize 	= strlen($data);
		//	header("Content-Length: $filesize");
		//	echo $data;
		readfile($plugin_info["download_link"],false,$context);
		die();
	}


	/**
	 * get_redirect_download - download the plugin file via redirect
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @param 	array	plugin info array from getPluginInfo()
	 * @return 	void
	 */
	public function get_redirect_download(\WP_REST_Request $request, $plugin_info)
	{
		return new \WP_REST_Response(null,302,
			array_merge(
				wp_get_nocache_headers(),
				['Location' => $plugin_info['download_link']]
			)
		);
	//	nocache_headers
	//	wp_redirect($plugin_info['download_link']);
	//	die();
	}


	/**
	 * log response
	 *
	 * @param 	object	$request - WP_REST_Request Request object.
	 * @param 	array	plugin info array or wp_error
	 * @return 	array	$plugin_info
	 */
	public function log_response(\WP_REST_Request $request, $plugin_info)
	{
		if (!defined('EAC_GITHUB_API_LOG') || !is_string(EAC_GITHUB_API_LOG)) {
			return $plugin_info;
		}

		$logfile_path = trailingslashit($this->varServer('document_root')).trim(EAC_GITHUB_API_LOG,'/');

		$logfile = new \stdClass();
		if ($this->fs->exists($logfile_path)) {
			if ($logfile = $this->fs->get_contents($logfile_path)) {
				$logfile = json_decode($logfile);
			}
		}
		$result['request'] = [
			'time'				=> 	wp_date('c'),
			'request'			=> 	$this->varServer('request_uri'),
			'route'				=> 	[$this->route['type'] => $request->get_route()],
			'remote_addr'		=> 	$this->varServer('remote_addr'),
			'user_agent'		=> 	$request->get_header('user_agent'),
		];
		if (is_wp_error($plugin_info)) {
			$result['error'] = [
				'code'			=>	$plugin_info->get_error_code(),
				'message'		=>	$plugin_info->get_error_message(),
				'data'			=>	$plugin_info->get_error_data(),
			];
		} else {
			$default = $plugin_info['eac_github_hosting'];
			$result['response'] = $default['package'];
		}

		$datestr = '_'.time();
		$logfile->{$datestr} = $result;
		$this->fs->put_contents($logfile_path,json_encode($logfile,JSON_PRETTY_PRINT));

		return $plugin_info;
	}
}
