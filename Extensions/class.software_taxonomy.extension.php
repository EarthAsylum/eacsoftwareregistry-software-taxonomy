<?php
namespace EarthAsylumConsulting\Extensions;

/**
 * EarthAsylum Consulting {eac}SoftwareRegistration software product taxonomy
 *
 * @category	WordPress Plugin
 * @package		{eac}SoftwareRegistry
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.earthasylum.com>
 */

include('class.software_taxonomy.github_hosting.php');

class software_product_taxonomy extends \EarthAsylumConsulting\abstract_extension
{
	/**
	 * @trait methods for api
	 */
	use software_product_github_hosting;

	/**
	 * @var string extension version
	 */
	const VERSION	= '24.1123.1';

	/**
	 * @var string taxonomy name
	 */
	const TAXONOMY_NAME	= EAC_SOFTWARE_TAXONOMY;

	/**
	 * @var string help tab
	 */
	const HELPTAB_NAME	= 'Software Products';


	/**
	 * @var array additional text fields shown on taxonomy
	 */
	private $term_text_fields =
	[
	//	'taxonomy_text_field'	=> [
	//		'label' 		=> 'Taxonomy Text',
	//		'description'	=> "Text displayed below field.",
	//	],
	];

	/**
	 * @var array option meta data for taxonomy meta fields (set by register_options())
	 */
	private $term_option_fields = [];


	/**
	 * constructor method
	 *
	 * @param 	object	$plugin main plugin object
	 * @return 	void
	 */
	public function __construct($plugin)
	{
		parent::__construct($plugin, self::ALLOW_ADMIN | self::ALLOW_NON_PHP);

		// register our taxonimy when the software registry post type is registered
		add_action( 'registered_post_type_'.$this->plugin::CUSTOM_POST_TYPE,
																array($this, 'register_taxonomy') );

		if ($this->is_admin())
		{
			// add option meta data for term fields
			add_action( 'current_screen',						function($screen)
				{
					if ($screen->id == 'edit-' . self::TAXONOMY_NAME)
					{
						add_action( 'admin_enqueue_scripts',	array($this, 'add_inline_style') );
						add_action( 'admin_notices',			array($this->plugin, 'plugin_admin_notices'), 1 );
						$this->render_help($screen);
						$tag_id = $this->varRequest('tag_ID');
						$this->register_options($tag_id);
					}
				}
			);

			// customize the taxonomy list form
			add_action( self::TAXONOMY_NAME.'_pre_add_form',	array($this, 'taxonomy_list_description') );
			add_action( 'after-'.self::TAXONOMY_NAME.'-table', 	array($this, 'taxonomy_list_notes') );

			// adjust row actions
		//	add_filter( self::TAXONOMY_NAME.'_row_actions',		array($this, 'taxonomy_list_actions'), 10, 2 );
		//	add_action( 'admin_init',							array($this, 'taxonomy_list_actions_post') );

			// customize the taxonomy edit form/fields
			add_action( self::TAXONOMY_NAME.'_edit_form_fields',array($this, 'taxonomy_form_fields'), 20);
			add_action( self::TAXONOMY_NAME.'_edit_form',		array($this, 'taxonomy_form_table'), 10);

			// save custom taxonomy fields
			add_action( 'create_'.self::TAXONOMY_NAME,			array($this, 'create_taxonomy_term'), 10, 3);
			add_action( 'edit_'.self::TAXONOMY_NAME,			array($this, 'edit_taxonomy_term'), 10, 3);
			add_action( 'delete_'.self::TAXONOMY_NAME,			array($this, 'delete_taxonomy_term'), 10, 5);
		}
	}


	/**
	 * Add filters and actions - called from main plugin.
	 * Hook names are prefixed with main plugin name (eacSoftwareRegistry_)
	 *
	 * @return	void
	 */
	public function addActionsAndFilters()
	{
		// Add a new eacSoftwareRegistry plugin options
		$this->add_action( 'options_settings_page',				array($this, 'admin_options_settings') );

		// filter default registry api values
		$this->add_filter( 'registry_api_defaults',				array($this, 'registry_api_defaults'), 10, 3);
		// filter software registry api request array
		$this->add_filter( 'api_request_parameters',			array($this, 'api_request_parameters'), 50, 2);
		// filter software registry license limitations array
		$this->add_filter( 'api_license_limitations',			array($this, 'api_license_limitations'),10, 3);

		// filter registrar contact info
		foreach(['registrar_name','registrar_contact','registrar_phone','registrar_web'] as $option_name)
		{
			$this->add_filter( $option_name,					function($default,$slug) use($option_name)
				{
					return $this->get_software_options($default,$slug,$option_name);
				},
			10,2);
		}

		// filter client notifications
		foreach(['client_email_message','client_email_footer','client_api_message','client_api_supplemental','client_success_notice','client_error_notice'] as $option_name)
		{
			$this->add_filter( $option_name,					function($default,$registry,$post) use($option_name)
				{
					return $this->get_software_options($default,$registry['registry_product'],$option_name);
				},
			10,3);
		}

		// utility - get array options
		$this->add_filter('software_options',					array($this, 'get_software_options'), 10, 3);
	}


	/**
	 * register options on eacSoftwareRegistry options_settings_page
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options_settings()
	{
		$this->registerPluginOptions('registration_options',
			[
				'registrar_taxonomy_product'	=> array(
									'type'		=> 	'checkbox',
									'label'		=> 	'Force Software Taxonomy',
									'options'	=>	['Enabled'],
									'info'		=> 	'When enabled, all registration requests must match defined '.
													"<a href='".admin_url('edit-tags.php?taxonomy='.self::TAXONOMY_NAME.'&post_type='.$this->plugin::CUSTOM_POST_TYPE)."'>Software Products</a>, ".
													'otherwise, any product may be registered.'
								),
			]
		);
	}


	/**
	 * registry_api_defaults handler - set default registry values
	 *
	 * @param array		$defaults 	array of API default values.
	 * @param array		$request 	parameter array passed through the API.
	 * @param string 	$apiAction 	one of 'create', 'activate', 'revise', 'deactivate', 'verify' or 'update' (non-api)
	 * @return array
	 */
	public function registry_api_defaults(array $defaults, array $request, string $apiAction)
	{
		if ( ! isset($request['registry_product']) ) return $defaults;

		if ($product = sanitize_title($request['registry_product']))
		{
			if ($term = get_term_by( 'slug', $product, self::TAXONOMY_NAME))
			{
				$termMeta = $this->get_term_meta($term->term_id);

				// only override defaults if not already set (may be existing registration)

				if (empty($defaults['registry_product']))
				{
					$defaults['registry_product'] 		= $term->slug;
				}

				if (empty($defaults['registry_title']) || $defaults['registry_title'] == $request['registry_product'])
				{
					$defaults['registry_title'] 		= $term->name;
				}

				if (empty($defaults['registry_description']))
				{
					$defaults['registry_description'] 	= $term->description;
				}

				if (empty($defaults['registry_status']) || $apiAction == 'create')
				{
					$defaults['registry_status'] 		= $termMeta['registrar_status'];
				}

				if (empty($defaults['registry_expires']) || $apiAction == 'create')
				{
					$defaults['registry_expires'] 		=
						(in_array($defaults['registry_status'],['pending','trial']))
							? $termMeta['registrar_term']
							: $termMeta['registrar_fullterm'];
				}

				if (empty($defaults['registry_license']) || $apiAction == 'create')
				{
					$defaults['registry_license'] 		= $termMeta['registrar_license'];
				}
			}
		}

		return $defaults;
	}


	/**
	 * api_request_parameters handler
	 *
	 * @param array		$request 	parameter array passed through the API.
	 * @param string 	$apiAction 	one of 'create', 'activate', 'revise', 'deactivate', 'verify' or 'update' (non-api)
	 * @return array
	 */
	public function api_request_parameters(array $request, string $apiAction)
	{
		if ( ! isset($request['registry_product']) ) return $request;

		if ($product = sanitize_title($request['registry_product']))
		{
			if ($term = get_term_by( 'slug', $product, self::TAXONOMY_NAME))
			{
				$defaults = $this->get_term_meta($term->term_id);

				if (!isset($request['registry_title']) || empty($request['registry_title']))
				{
					$request['registry_title'] 			= $term->name;
				}

				if (!isset($request['registry_description']) || empty($request['registry_description']))
				{
					$request['registry_description'] 	= $term->description;
				}

				return $request;
			}
		}

		if ($this->is_option('registrar_taxonomy_product'))
		{
			return new \wp_error('400',__('invalid registry product','eacSoftwareRegistry'));
		}

		return $request;
	}


	/**
	 * api_license_limitations handler
	 *
	 * @param array		$limitations 	limitations array ['count'=> n, 'variations'=>n, 'options'=>n, 'domains'=>n, 'sites'=>n]
	 * @param string 	$license 		current license level (Ln)
	 * @param array		$request 		parameter array passed through the API.
	 * @return array
	 */
	public function api_license_limitations(array $limitations, string $license, array $request): array
	{
		if ( ! isset($request['registry_product']) ) return $limitations;

		if ($product = sanitize_title($request['registry_product']))
		{
			if ($term = get_term_by( 'slug', $product, self::TAXONOMY_NAME))
			{
				$defaults = $this->get_term_meta($term->term_id);

				$limitations = array_merge($limitations,[
					'count'			=> intval($defaults["registrar_{$license}_count"]) ?: null,
					'variations'	=> intval($defaults["registrar_{$license}_variations"]) ?: null,
					'options'		=> intval($defaults["registrar_{$license}_options"]) ?: null,
					'domains'		=> intval($defaults["registrar_{$license}_domains"]) ?: null,
					'sites'			=> intval($defaults["registrar_{$license}_sites"]) ?: null,
				]);
			}
		}
		return $limitations;
	}


	/**
	 * Registers taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy()
	{
		register_taxonomy(self::TAXONOMY_NAME,
			array($this->plugin::CUSTOM_POST_TYPE),
			array(
				'label'				=> esc_html__('Software Products', $this->plugin->PLUGIN_TEXTDOMAIN),
				'labels'			=> array(
					'name'					=> 	esc_html__('{eac}SoftwareRegistry Software Products', $this->plugin->PLUGIN_TEXTDOMAIN),
					'singular_name'			=> 	esc_html__('{eac}SoftwareRegistry Software Product', $this->plugin->PLUGIN_TEXTDOMAIN),
					'menu_name'				=> 	esc_html__('Software Products', $this->plugin->PLUGIN_TEXTDOMAIN),
					'search_items'			=> 	esc_html__('Search Software Products', $this->plugin->PLUGIN_TEXTDOMAIN),
					'all_items'				=> 	esc_html__('All Software Products', $this->plugin->PLUGIN_TEXTDOMAIN),
					'edit_item'				=> 	esc_html__('Edit Software Product', $this->plugin->PLUGIN_TEXTDOMAIN),
					'update_item'			=> 	esc_html__('Update Software Product', $this->plugin->PLUGIN_TEXTDOMAIN),
					'add_new_item'			=> 	esc_html__('Add New Software Product', $this->plugin->PLUGIN_TEXTDOMAIN),
					'new_item_name'			=> 	esc_html__('New Software Product', $this->plugin->PLUGIN_TEXTDOMAIN),
					'add_or_remove_items'	=> 	esc_html__('Add or Remove Products', $this->plugin->PLUGIN_TEXTDOMAIN),
					'not_found'				=> 	esc_html__('No products found', $this->plugin->PLUGIN_TEXTDOMAIN),
					'back_to_items'			=> 	esc_html__('&larr; Back to Software Products', $this->plugin->PLUGIN_TEXTDOMAIN),
					'name_field_description'=> '[<em>registry_title</em>] '.
												esc_html__('The Software Product display name.', $this->plugin->PLUGIN_TEXTDOMAIN),
					'slug_field_description'=> '[<em>registry_product</em>] '.
												esc_html__('The Software Product Id used to programmatically identify the registered product.', $this->plugin->PLUGIN_TEXTDOMAIN),
					'desc_field_description'=> '[<em>registry_description</em>] '.
												esc_html__('The Software Product Description.', $this->plugin->PLUGIN_TEXTDOMAIN),
				),
				'hierarchical'			=> false,
				'meta_box_cb'			=> false,
				'show_ui'				=> true,
				'show_in_nav_menus'		=> true,
				'query_var'				=> is_admin(),
				'rewrite'				=> false,
				'public'				=> false
			)
		);

		$this->github_hosting_construct();
	}


	/**
	 * Render contextual help
	 *
	 * @param object $screen current screen
	 * @return void
	 */
	public function render_help($screen)
	{
		require_once 'includes/software_taxonomy.help.php';
	}


	/**
	 * Register the taxonomy option field meta data
	 *
	 * @return void
	 */
	public function register_options($term_id = null)
	{
		require_once 'includes/software_taxonomy.options.php';
	}


	/**
	 * custom styles for term screen.
	 *
	 * @return void
	 */
	public function add_inline_style()
	{
		$styleId = $this->plugin->html_input_style(true,'table#software_product_details');

		ob_start();
		?>
			table#software_product_details {visibility: hidden;}
			th.column-posts, td.column-posts {display: none!important;}
			textarea#description { max-height: 4em; }
			table#software_product_details tr:not(:first-child) {
				border: solid .5px #ccc; border-top: none;
			}
			fieldset.client_notification {grid-template-columns: minmax(0, 1fr); grid-row-gap: 0.1em;}
			fieldset.client_notification div.settings-grid-item:nth-child(even)  {padding: 0.5em 3.5em;}
			fieldset.license_limitations {grid-template-columns: 9em minmax(0, 1fr) 9em minmax(0, 1fr);}
			.wp-editor-wrap {width:100%;}
		<?php
		$style = ob_get_clean();
		wp_add_inline_style( $styleId, str_replace("\t","",trim($style)) );
	}


	/**
	 * adds description to our taxonomy page.
	 *
	 * @return void
	 */
	public function taxonomy_list_description()
	{
		?>
		<div class="form-wrap edit-term-description">
			<p>
				Software Product Taxonomy is used to define, and set parameters for, the products
				to be registered through your Software Registration Server.
			</p>
		</div>
		<?php
	}


	/**
	 * adds notes to our taxonomy page.
	 *
	 * @return void
	 */
	public function taxonomy_list_notes()
	{
		?>
		<div class="form-wrap edit-term-notes">
			<p>
				<strong>Notes:</strong>
				<ul>
					<li>Changing or deleting a product here does not change existing registrations
					but may cause changes when a registration is updated or renewed.</li>
				</ul>
			</p>
		</div>
		<?php
	}


	/**
	 * adjust taxonomy list row actions (filter {taxonomy}_row_actions)
	 *
	 * @param array	 	$actions 	array of actions.
	 * @param object 	$term 		term object.
	 * @return array
	 */
	public function taxonomy_list_actions( $actions, $term )
	{
		if	( current_user_can('edit_term', $term->term_id) )
		{
		/*
			$addActions = [
				'Action Name' 	=> 'Action title',
			];
			foreach ($addActions as $action=>$title)
			{
				$actionKey = sanitize_title($action);
				$actions[$actionKey] = sprintf('<a href="%s" title="%s">%s</a>',
					wp_nonce_url(
						add_query_arg(['action'=>$actionKey,'term_id'=>$term->term_id],$_SERVER['REQUEST_URI']),
						self::TAXONOMY_NAME . absint( $term->term_id )
					),
					$title,
					str_replace(' ','&nbsp;',$action)
				);
			}
		*/
		}
		// add term id
		//	$actions = array_merge(['termid'=>'<span>ID: '.$term->term_id.'</span>'],$actions);
		// remove quick edit
		//	unset($actions['inline hide-if-no-js']);
		return $actions;
	}


	/**
	 * handle taxonomy list row actions (action admin_init)
	 *
	 * @return void
	 */
	public function taxonomy_list_actions_post()
	{
	/*
		if (isset( $_GET['action'], $_GET['term_id'], $_GET['_wpnonce'] ))
		{
			$term_id = absint( $_GET['term_id'] );
			if ( wp_verify_nonce( $_GET['_wpnonce'], self::TAXONOMY_NAME . $term_id ) &&
				current_user_can( 'edit_term', $term_id ) )
			{
				switch ($_GET['action'])
				{
					case 'action-name':
						break;
				}
			}
		}
	*/
	}


	/**
	 * adds fields to the taxonomy editor page (action {taxonomy}_edit_form_fields)
	 *
	 * @param int		$term 	term being edited
	 * @return void
	 */
	public function taxonomy_form_fields($term)
	{
		if  (empty($this->term_option_fields))
		{
			$this->register_options($term);
		}

		// term text fields
		foreach($this->term_text_fields as $fieldName => $fieldMeta )
		{
		?>
			<tr class="form-field term-<?php echo esc_attr($fieldName) ?>-wrap">
				<th scope="row">
					<label for='<?php echo esc_attr($fieldName) ?>'><?php echo esc_attr($fieldMeta['label']) ?></label>
				</th>
				<td>
					<?php
						$value = get_term_meta($term->term_id, $fieldName, true);
						echo "<input name='".esc_attr($fieldName)."' id='".esc_attr($fieldName)."' type='text' value='".esc_attr(wp_unslash($value))."' autocomplete='off'>";
					?>
					<p class="description"><?php echo esc_attr($fieldMeta['description']) ?></p>
				</td>
			</tr>
		<?php
			$this->plugin->html_input_help(self::HELPTAB_NAME, $fieldName, [
					'label'	=>	$fieldMeta['label'],
					'help'	=>	$fieldMeta['description']
				]
			);
		}
	}


	/**
	 * adds fields to the end of the taxonomy editor page (action {taxonomy}_edit_form)
	 *
	 * @param int		$term 	term being edited
	 * @return void
	 */
	public function taxonomy_form_table($term)
	{
		if  (empty($this->term_option_fields))
		{
			$this->register_options($term);
		}
		echo "<hr/>";
		echo "<table class='form-table' id='software_product_details'><tbody>\n";
		echo "<tr><td class='tab-container' style='padding: 0;'></td></tr>\n";

		$termMeta = $this->get_term_meta($term->term_id);

		$i = 0;
		foreach ($this->term_option_fields as $groupName => $optionMeta)
		{
			$width = (in_array($groupName,['registrar_contact','github_hosting'])) ? 50 : 15;
			echo "<tr><td style='padding: 0;'>\n";
			echo $this->plugin->html_input_section($groupName, $optionMeta);
			foreach ($optionMeta as $optionKey => $optionData)
			{
				$this->plugin->html_input_help(self::HELPTAB_NAME, $optionKey, $optionData);
				if ($optionData['type'] == 'help') continue;
				$optionValue = $termMeta[$optionKey] ?? $optionData['default'] ?? '';
				echo $this->plugin->html_input_block($optionKey, $optionData, $optionValue, $width);
				if ($groupName == 'license_limitations')
				{
					if ((++$i % 6) == 0) echo "<div style='grid-column: 1/-1'><hr></div>".
											  "<div style='grid-column: 1/-1'></div>";
				}
			}
			echo "</fieldset>\n";
			echo "</section>\n";
			echo "</td></tr>\n";
		}
		echo "</tbody></table>\n";
		echo "<hr/>";
	}


	/**
	 * Create new custom term meta (action create_{taxonomy})
	 *
	 * @param int		$term 		term created
	 * @param int		$tt_id 		term taxonomy ID
	 * @param array 	$args 		arguments passed to wp_insert_term().
	 * @return void
	 */
	public function create_taxonomy_term($term_id, $tt_id, $args=null)
	{
/*
		if  (empty($this->term_option_fields))
		{
			$this->register_options($term_id);
		}
*/

		// set new term options to plugin defaults
		$termMeta = [];
		foreach ($this->term_option_fields as $groupName => $optionMeta)
		{
			foreach(array_keys($optionMeta) as $optionKey)
			{
				if ($value = $this->get_option($optionKey))
				{
					$termMeta[$optionKey] = $value;
				}
			}
		}
		$this->update_term_meta($term_id, $termMeta);
	}


	/**
	 * Saves custom term meta when data is edited (action edit_{taxonomy})
	 *
	 * @param int		$term_id 	term id being edited
	 * @param int		$tt_id 		term taxonomy ID
	 * @param array 	$args 		arguments passed to wp_insert_term().
	 * @return void
	 */
	public function edit_taxonomy_term($term_id, $tt_id, $args=null)
	{
/*
		if  (empty($this->term_option_fields))
		{
			$this->register_options($term_id);
		}
*/

		// save the text fields individually
		foreach(array_keys($this->term_text_fields) as $optionKey)
		{
			if (array_key_exists($optionKey,$_POST) )
			{
				$value = sanitize_text_field($_POST[$optionKey]);
				update_term_meta($term_id, $optionKey, $value);
			}
		}

		// save the option fields as an array
		$termMeta = $this->get_term_meta($term_id);
		foreach ($this->term_option_fields as $groupName => $optionMeta)
		{
			foreach ($optionMeta as $optionKey => $optionData)
			{
				if (array_key_exists($optionKey,$_POST) )
				{
					$value = $this->plugin->html_input_sanitize($_POST[$optionKey], $optionKey, $optionData);
					if (wp_unslash($_POST[$optionKey]) == $value)
					{
						$termMeta[$optionKey] = $value;
					}
					else
					{
						$this->plugin->add_option_error($optionKey,
							sprintf("%s : The value entered does not meet the criteria for this field.",$optionData['label'])
						);
					}
				}
			}
		}
		$this->update_term_meta($term_id, $termMeta);
	}


	/**
	 * Delete custom term meta (action delete_{taxonomy})
	 *
	 * @param int 		$term_id		Term ID.
	 * @param int 		$tt_id			Term taxonomy ID.
	 * @param string 	$taxonomy		Taxonomy slug.
	 * @param WP_Term 	$term			Copy of the already-deleted term.
	 * @param array 	$object_ids		List of term object IDs.
	 * @return void
	 */
	public function delete_taxonomy_term($term_id, $tt_id, $taxonomy, $term, $object_ids=null)
	{
	}


	/**
	 * Get custom term meta array
	 *
	 * @param int		$term_id		Term ID.
	 * @param string	$key			optional value key.
	 * @return array
	 */
	public function get_term_meta($term_id, $key=null)
	{
		$meta = maybe_unserialize( get_term_meta($term_id, 'sw_product_meta', true) ?: [] );
		if ($key)
		{
			return $meta[$key] ?? get_term_meta($term_id, $key, true);
		}
		return $meta;
	}


	/**
	 * Update custom term meta array
	 *
	 * @param int		$term_id		Term ID.
	 * @param mixed		$values			array of or single value.
	 * @param string	$key			optional value key.
	 * @return void
	 */
	public function update_term_meta($term_id, $values, $key=null)
	{
		if ($key)
		{
			$termMeta = $this->get_term_meta($term_id);
			$termMeta[$key] = $values;
		}
		else
		{
			$termMeta = $values;
		}
		update_term_meta($term_id, 'sw_product_meta', $termMeta);
	}


	/**
	 * get taxonomy options/meta (filter software_options)
	 *
	 * @param string 	$default 	option value default
	 * @param int|string $term_or_slug term id or slug
	 * @param string 	$key 		get a key from the array
	 * @return string|array
	 */
	public function get_software_options($default, $term_or_slug, $key=null)
	{
		$by = (is_numeric($term_or_slug)) ? 'id' : 'slug';
		if ($term = get_term_by( $by, sanitize_title($term_or_slug), self::TAXONOMY_NAME))
		{
			if ($options = $this->get_term_meta($term->term_id))
			{
				return ($key) ? ($options[$key] ?: $default) : $options;
			}
		}
		return $default;
	}
}

/**
 * return a new instance of this class
 */
return new software_product_taxonomy($this);
?>
