<?php

class Templ33t {
	
	static $version				= '0.2';
	static $db_version			= '0.2';
	static $wp_content_url;
	static $wp_content_dir;
	static $wp_plugin_url;
	static $wp_plugin_dir;
	static $wpmu_plugin_url;
	static $wpmu_plugin_dir;
	static $assets_url;
	static $assets_dir;
	static $settings_url;
	
	var $tab_pages = array(
		'page.php',
		'page-new.php',
		'post.php',
		'post-new.php'
	);
	
	var $config_defaults = array(
		'slug' => '',
		'type' => 'editor',
		'description' => '',
		'config' => '',
		'group' => null,
		'optional' => false,
		'bindable' => false,
		'searchable' => false,
	);

	var $plugin_attributes = array(
		'main',
		'optional',
		'bindable',
		'searchable',
		'group',
	);

	var $active = true;
	var $render = false;

	var $use_site_option;
	
	var $map = array();
	var $templates = array();
	var $meta = array();
	var $block_objects = array();
	var $option_objects = array();
	var $groups = array();
	var $menu_parent = null;
	var $errors = array();
	
	function __construct() {
		
		global $wp_version;
		
		// generate paths
		if(empty(self::$wp_content_url)) $this->fillPaths();

		// check existence of site options
		$this->use_site_option = function_exists('add_site_option');

		// grab theme name
		$theme = get_stylesheet();

		// get theme map
		$map = unserialize($this->getOption('templ33t_map'));
		if(is_array($map) && array_key_exists($theme, $map)) {
			$this->map = $map[$theme];
		} else {
			$this->active = false;
		}
		
		// install & uninstall
		register_activation_hook(__FILE__, array($this, 'install'));
		register_deactivation_hook(__FILE__, array($this, 'uninstall'));
		
		// actions & filters
		add_action('admin_init', array($this, 'init'), 1);
		if($wp_version < '3.1.0')
			add_action('admin_menu', array($this, 'menu'));
		else
			add_action('network_admin_menu', array($this, 'menu'));
		add_filter('the_content', array($this, 'search_results'), 1);
		add_action('wp', array($this, 'prepare_meta'), 1);
		
	}

	function fillPaths() {

		if ( version_compare( get_bloginfo( 'version' ) , '3.0' , '<' ) && is_ssl() ) {
			self::$wp_content_url = str_replace( 'http://' , 'https://' , get_option( 'siteurl' ) );
		} else {
			self::$wp_content_url = get_option( 'siteurl' );
		}

		self::$wp_content_url .= '/wp-content';
		self::$wp_content_dir = ABSPATH . 'wp-content';
		self::$wp_plugin_url = self::$wp_content_url . '/plugins';
		self::$wp_plugin_dir = self::$wp_content_dir . '/plugins';
		self::$wpmu_plugin_url = self::$wp_content_url . '/mu-plugins';
		self::$wpmu_plugin_dir = self::$wp_content_dir . '/mu-plugins';

		if($this->mustUse()) {
			self::$assets_url = self::$wp_content_url.'/mu-plugins/templ33t/';
			self::$assets_dir = self::$wp_content_dir.'/mu-plugins/templ33t/';
		} else {
			self::$assets_url = self::$wp_content_url.'/plugins/templ33t/';
			self::$assets_dir = self::$wp_content_dir.'/plugins/templ33t/';
		}

	}
	
	function install() {
		
		global $wpdb;
		
		$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
		$template_table_name = $the_prefix . "templ33t_templates";
		$block_table_name = $the_prefix . "templ33t_blocks";

		if($wpdb->get_var("SHOW TABLES LIKE '$template_table_name'") != $template_table_name) {

			$sql = 'CREATE TABLE `'.$template_table_name.'` (
				`templ33t_template_id` int(11) NOT NULL AUTO_INCREMENT,
				`theme` varchar(50) DEFAULT NULL,
				`template` varchar(255) DEFAULT NULL,
				`config` text NULL,
				PRIMARY KEY  (`templ33t_template_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			dbDelta($sql);

			$this->addOption('templ33t_db_version', Templ33t::$db_version);
			$this->addOption('templ33t_map_pub', 0);
			$this->addOption('templ33t_map_dev', 0);
			$this->addOption('templ33t_map', serialize(array()));
			if($this->multiSite()) $this->addOption('templ33t_blogs', 1);

		} elseif($this->multiSite()) {

			$count = $this->getOption('templ33t_blogs');
			$count++;
			$this->updateOption('templ33t_blogs', $count);

		}
		
	}
	
	function uninstall() {
		
		global $wpdb;
		
		// make sure uninstall only happens from last site
		$uninstall = true;

		if($this->multiSite()) {
			$count = $this->getOption('templ33t_blogs');
			if(($count - 1) > 0) $uninstall = false;
			$count--;
			$this->updateOption('templ33t_blogs', $count);
		}

		if($uninstall) {

			// set table names
			$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
			$template_table_name = $the_prefix . "templ33t_templates";
			$block_table_name = $the_prefix . "templ33t_blocks";

			// drop blocks table
			$sql_blocks = 'DROP TABLE IF EXISTS `'.$block_table_name.'`;';
			$wpdb->query($sql_blocks);

			// drop templates table
			$sql_templates = 'DROP TABLE IF EXISTS `'.$template_table_name.'`;';
			$wpdb->query($sql_templates);

			// remove db version option
			$this->deleteOption('templ33t_db_version');
			$this->deleteOption('templ33t_map_pub');
			$this->deleteOption('templ33t_map_dev');
			$this->deleteOption('templ33t_map');
			if($this->multiSite()) $this->deleteOption('templ33t_blogs');

		}
		
	}
	
	function init() {

		global $templ33t, $templ33t_multisite, $templ33t_menu_parent, $templ33t_settings_url, $templ33t_tab_pages, $templ33t_templates, $user_ID, $wpdb, $wp_version;

		// check db version or create tables if no version
		$installed_version = $this->getOption('templ33t_db_version');
		if($installed_version != self::$db_version) {
			$this->uninstall();
			$this->install();
		}
		
		// register styles & scripts
		wp_register_style('templ33t_styles', self::$assets_url.'templ33t.css');
		wp_register_script('templ33t_scripts', self::$assets_url.'templ33t.js');
		wp_register_script('templ33t_settings_scripts', self::$assets_url.'templ33t_settings.js');

		// initialize tabs & content filters
		if(in_array(basename($_SERVER['PHP_SELF']), $this->tab_pages)) {

			// add hooks
			//add_action('posts_selection', 'templ33t_handle_meta', 1);
			add_action('posts_selection', array($this, 'prepare_meta'), 1);
			add_action('admin_print_styles', array($this, 'styles'), 10);
			add_action('admin_print_scripts', array($this, 'scripts'), 10);
			add_filter('the_editor_content', array($this, 'strip_comment'), 1);
			add_filter('content_save_pre', array($this, 'add_comment'), 10);
			add_action('edit_page_form', array($this, 'tab_elements'), 1);

			if(!empty($_POST)) $this->save_options();

			// create new page with forced template if from_template passed
			if(in_array(basename($_SERVER['PHP_SELF']), array('page-new.php', 'post-new.php')) && array_key_exists('from_template', $_GET)) {

				$has_title = (array_key_exists('post_title', $_POST) && !empty($_POST['post_title']));
				$has_template = (array_key_exists('page_template', $_POST) && !empty($_POST['page_template']));

				if($has_title && $has_template) {

					// post data
					$insert = array(
						'post_type' => 'page',
						'post_title' => $_POST['post_title'],
						'post_content' => '',
						'post_status' => 'draft',
						'post_author' => $user_ID,
					);

					// save post as draft
					$new_post = wp_insert_post($insert);

					if($new_post > 0) {

						// set template
						update_post_meta($new_post, '_wp_page_template', $_POST['page_template']);

						// redirect to edit page
						$redirect_to = $wp_version{0} < 3 ? 'page.php?action=edit&post='.$new_post : 'post.php?action=edit&post='.$new_post;
						wp_redirect($redirect_to);

					}

				}

			}

			// grab theme name
			$theme = get_stylesheet();

			// grab template map
			$this->map = unserialize($this->getOption('templ33t_map'));

			// get theme map
			$this->templates = array_key_exists($theme, $this->map) ? $this->map[$theme] : array();

		} elseif(basename($_SERVER['PHP_SELF']) == $this->menu_parent && $_GET['page'] == 'templ33t_settings') {

			// add styles & scripts
			add_action('admin_print_styles', array($this, 'settings_styles'), 1);
			add_action('admin_print_scripts', array($this, 'settings_scripts'), 1);

			// handle settings page post
			$this->save_settings();

		} elseif(basename($_SERVER['PHP_SELF']) == 'media-upload.php') {

			//add_filter( 'media_send_to_editor', 'templ33t_intercept_media', 15 );

		}

	}
	
	function menu() {
		
		global $wp_version;

		// set menu parent and settings url
		if($this->multiSite()) {
			if($wp_version < '3.0.0')
				$this->menu_parent = 'wpmu-admin.php';
			elseif($wp_version < '3.2.0')
				$this->menu_parent = 'ms-admin.php';
			else
				$this->menu_parent = 'settings.php';
			self::$settings_url = $this->menu_parent.'?page=templ33t_settings';
		} else {
			$this->menu_parent = 'options-general.php';
			self::$settings_url = $this->menu_parent.'?page=templ33t_settings';
		}

		add_submenu_page($this->menu_parent, 'Templ33t Settings', 'Templ33t', 'edit_themes', 'templ33t_settings', array($this, 'settings'));
		
	}

	function getOption($key) {
		
		if($this->use_site_option) return get_site_option($key);
		else return get_option($key);

	}

	function addOption($key, $val) {

		if($this->use_site_option) return add_site_option($key, $val);
		else return add_option($key, $val);

	}

	function updateOption($key, $val) {

		if($this->use_site_option) return update_site_option($key, $val);
		else return update_option($key, $val);

	}

	function deleteOption($key) {

		if($this->use_site_option) return delete_site_option($key);
		else return delete_option($key);

	}

	function mustUse() {

		if(strpos(dirname(__FILE__), 'mu-plugins') !== false) return true;
		else return false;

	}

	function multiSite() {

		return (function_exists('is_multisite') && is_multisite());

	}

	function parseTheme($theme = null) {

		$tdir = self::$wp_content_dir . '/themes/' . $theme . '/';

		$files = scandir($tdir);

		$ignore = array('.', '..', 'style.css', 'header.php', 'footer.php', 'comments.php');

		$templates = array();

		foreach($files as $tfile) {

			if(!in_array($tfile, $ignore) && !is_dir($tfile) && strpos($tfile, '.php')) {

				if($conf = $this->parseTemplate($tdir.$tfile))
					$templates[$tfile] = $conf;

			}

		}

		return $templates;

	}

	function parseTemplate($template = null) {

		// catch invalid file
		if(empty($template) || !file_exists($template)) return false;
		$template_data = implode( '', file( $template ));
		
		// parse retrieved data
		$config = $this->parseConfig($template_data);
		$options = $this->parseOptions($template_data);


		if(!empty($config) || !empty($options)) {

			$config = array_merge(
				array(
					'main' => 'Page Content',
					'description' => '',
					'blocks' => array(),
					'options' => array(),
				),
				$config,
				$options
			);

			return $config;

		} else {

			return false;

		}

	}

	function parseOptions($template_str = null) {

		// grab options config string from file
		$data = '';
		if ( preg_match( '|Templ33t Options:(.*)$|mi', $template_str, $data ) )
			$data = _cleanup_header_comment($data[1]);

		if(empty($data)) return array();

		// grab option string
		$matches = array();
		preg_match_all('/\s*(([^,\[]+)\s*(\[[^\]]+\])*),*/i', $data, $matches);

		$options = array();
		
		if(!empty($matches[1])) {

			// grab description strings
			$descriptions = array();
			if(preg_match_all('|Templ33t Description (.*\:.*)$|mi', $template_str, $descriptions))
				$descriptions = $this->parseDescriptions($descriptions[1]);

			foreach($matches[1] as $key => $val) {

				$opt = array('label' => $matches[2][$key], 'slug' => $this->slug($matches[2][$key]));
				$opt['description'] = array_key_exists($opt['slug'], $descriptions) ? $descriptions[$opt['slug']] : '';

				if(!empty($matches[3][$key])) {

					$cstr = trim(chop($matches[3][$key]));
					$cstr = trim(chop(substr($cstr, 1, (strlen($cstr) - 2))));
					
					if(strpos($cstr, '?') !== false) {
						list($opt['type'], $opt['config']) = explode('?', $cstr);
					} else {
						$opt['type'] = $cstr;
					}

				} else {

					$opt['type'] = 'text';

				}

				$options[$opt['slug']] = array_merge(
					$this->config_defaults,
					$opt
				);

			}

		}

		return array('options' => $options);

	}

	function parseConfig($template_str = null) {

		// grab block config string from file
		$blockstr = '';
		if ( preg_match( '|Templ33t Blocks:(.*)$|mi', $template_str, $blockstr ) )
			$blockstr = _cleanup_header_comment($blockstr[1]);

		// catch empty configuration (not a templ33t template)
		if(empty($blockstr)) return array();

		// set up config array
		$config = array(
			'main' => 'Page Content',
			'description' => '',
			'blocks' => array(),
			'options' => array(),
		);

		$matches = array();
		preg_match_all('/\s*(([^,\[]+)\s*(\[[^\]]+\])*),*/i', $blockstr, $matches);

		if(!empty($matches[1])) {

			// grab description data
			$descriptions = array();
			if(preg_match_all('|Templ33t Description (.*\:.*)$|mi', $template_str, $descriptions))
				$descriptions = $this->parseDescriptions($descriptions[1]);

			foreach($matches[1] as $key => $val) {

				$block = array('label' => $matches[2][$key], 'slug' => $this->slug($matches[2][$key]));
				$block['description'] = array_key_exists($block['slug'], $descriptions) ? $descriptions[$block['slug']] : '';
				
				if(!empty($matches[3][$key])) {

					$cstr = trim(chop($matches[3][$key]));
					$cstr = trim(chop(substr($cstr, 1, (strlen($cstr) - 2))));

					$attributes = explode('|', $cstr);

					foreach($attributes as $akey => $aval) {

						if($aval == 'main') {
							$config['main'] = $block['label'];
							$config['description'] = array_key_exists($block['slug'], $descriptions) ? $descriptions[$block['slug']] : '';
							break;
						}

						if(strpos($aval, '?') !== false) {
							list($block['type'], $block['config']) = explode('?', $aval);
						} else {
							if(in_array($aval, $this->plugin_attributes)) {
								$block[$aval] = true;
							} else {
								$block['type'] = $aval;
							}
						}

					}

					if($aval == 'main') continue;

				}

				$config['blocks'][$block['slug']] = array_merge(
					$this->config_defaults,
					$block
				);

			}
			
		}

		return $config;

	}
	
	function parseDescriptions($descriptions = array()) {
		
		if(is_numeric(current(array_keys($descriptions)))) {

			$new = array();
			foreach($descriptions as $desc) {
				list($title, $desc) = explode(':', $desc);
				$new[self::slug($title)] = trim(chop($desc));
			}

			$descriptions = $new;

		}

		return $descriptions;
		
	}

	public static function slug($key = null) {

		$slug = strtolower(str_replace(' ', '_', trim(chop(preg_replace('/([^a-z0-9]+)/i', ' ', $key)))));

		return $slug;

	}

	public function prepare_meta() {

		global $wpdb, $post;

		if(!$this->active || !empty($this->meta)) return;

		// cleanse default page name
		if(empty($post->page_template) || $post->page_template == 'default') $post->page_template = basename(get_page_template());

		if(array_key_exists($post->page_template, $this->map)) {

			// grab meta
			$all_meta = $wpdb->get_results(
				$wpdb->prepare('SELECT meta_key, meta_value, meta_id, post_id FROM '.$wpdb->postmeta.' WHERE post_id = %d', $post->ID),
				ARRAY_A
			);

			// filter out unrelated
			foreach($all_meta as $key => $val) {
				if(strpos($val['meta_key'], 'templ33t_option_') !== false) {
					$slug = str_replace('templ33t_option_', '', $val['meta_key']);
					$this->meta[$slug] = array_merge($this->config_defaults, array('id' => $val['meta_id'], 'value' => $val['meta_value']));
				} elseif(strpos($val['meta_key'], 'templ33t_') !== false) {
					$slug = str_replace('templ33t_', '', $val['meta_key']);
					$this->meta[$slug] = array_merge($this->config_defaults, array('id' => $val['meta_id'], 'value' => $val['meta_value']));
				}
			}

			// prepare option meta
			if(!empty($this->map[$post->page_template]['options'])) {

				foreach($this->map[$post->page_template]['options'] as $slug => $opt) {

					// create any non-existent custom fields
					if(!array_key_exists($slug, $this->meta)) {

						if(add_post_meta($post->ID, 'templ33t_option_'.$slug, '', true)) {
							$opt['id'] = $wpdb->insert_id;
							$opt['value'] = '';
						}
						$this->meta[$slug] = array_merge($this->config_defaults, $opt);

					// add meta ID and value to existing custom fields
					} else {

						$this->meta[$slug] = array_merge($this->meta[$slug], $opt);

					}

					// instantiate plugin
					$option_handler = Templ33tPluginHandler::instantiate($opt['type'], $this->meta[$slug]);

					// init plugin
					$option_handler->init();

					// store object
					$this->option_objects[$slug] = $option_handler;
					
				}

			}

			// prepare block meta
			if(!empty($this->map[$post->page_template]['blocks'])) {

				foreach($this->map[$post->page_template]['blocks'] as $slug => $block) {

					// create any non-existent custom fields
					if(!array_key_exists($slug, $this->meta)) {

						if(add_post_meta($post->ID, 'templ33t_'.$slug, '', true)) {
							$block['id'] = $wpdb->insert_id;
							$block['value'] = '';
						}
						$this->meta[$slug] = array_merge($this->config_defaults, $block);

					// add meta ID and value to existing custom fields
					} else {

						$this->meta[$slug] = array_merge($this->meta[$slug], $block);

					}

					// instantiate plugin
					$block_handler = Templ33tPluginHandler::instantiate($block['type'], $this->meta[$slug]);

					// init plugin
					$block_handler->init();

					// store object
					$this->block_objects[$slug] = $block_handler;
					
				}

			}

			if(!empty($this->option_objects) || !empty($this->block_objects)) $this->render = true;

		}

	}
	
	/**
	 * Enqueue Templ33t stylesheet
	 */
	function styles() {

		wp_enqueue_style('templ33t_styles');

		$load = array();

		// get option styles
		foreach($this->option_objects as $key => $val) {
			$cname = Templ33tPluginHandler::classify($val->type);
			if($cname::$load_style) $load[] = $val->type;
		}

		// get block styles
		foreach($this->block_objects as $key => $val) {
			$cname = Templ33tPluginHandler::classify($val->type);
			if($cname::$load_style) $load[] = $val->type;
		}


		// enqueue plugin scripts
		if(!empty($load)) {
			wp_enqueue_style('templ33t_plug_styles', Templ33t::$assets_url . 'templ33t_styles.php?load=' . implode(',', $load));
		}

	}
	
	/**
	 * Enqueue Templ33t tab scripts and generate js tab map object
	 * @global object $post
	 */
	function scripts() {

		global $templ33t, $templ33t_plugins, $post;

		// disable auto-save
		if($this->render) {
			wp_deregister_script('autosave');
			echo '<!-- autosave disabled by templ33t: '.basename(__FILE__).' - '.__FUNCTION__.'() -->';
		}

		// enqueue templ33t script file
		wp_enqueue_script('templ33t_scripts');

		// output templ33t js data
		$this->script_obj();


		$load = array();
		$dependencies = array();

		// get option js
		foreach($this->option_objects as $key => $val) {
			$cname = Templ33tPluginHandler::classify($val->type);
			if($cname::$load_js) {
				$load[] = $val->type;
				if(!empty($cname::$dependencies)) {
					foreach($cname::$dependencies as $d) {
						$dependencies[$c] = $c;
					}
				}
			}
		}

		// get block js
		foreach($this->block_objects as $key => $val) {
			$cname = Templ33tPluginHandler::classify($val->type);
			if($cname::$load_js) {
				$load[] = $val->type;
				if(!empty($cname::$dependencies)) {
					foreach($cname::$dependencies as $d) {
						$dependencies[$d] = $d;
					}
				}
			}
		}


		// enqueue plugin scripts
		if(!empty($load)) {
			wp_enqueue_script('templ33t_plug_scripts', self::$assets_url . 'templ33t_scripts.php?load=' . implode(',', $load), $dependencies);
		} elseif(!empty($dependencies)) {
			foreach($dependencies as $d) {
				wp_enqueue_script($d);
			}
		}

	}
	
	/**
	 * Outputs templ33t javascript template map object.
	 *
	 * @global array $templ33t_templates
	 */
	function script_obj() {

		global $templ33t, $templ33t_templates, $templ33t_plugins, $post;

		echo '<script type="text/javascript">
			/* <![CDATA[ */
			var TL33T_current = { template: "'.$post->page_template.'", assets: "'.Templ33t::$assets_url.'" };
			';

		// output js template map
		if(!empty($this->templates)) {

			$arr = array();
			foreach($this->templates as $template => $config) {

				$blocks = array();

				foreach($config['blocks'] as $key => $val) {

					// grab class name, load unloaded classes
					$cname = Templ33tPluginHandler::load($val['type']);

					$blocks[] = '{label: "'.$val['label'].'", custom: '.(!empty($cname) ? ($cname::$custom_panel ? 'true' : 'false') : 'false').'}';

				}

				// add settings block
				if(!empty($config['options'])) {
					$blocks[] = '{label: "Settings", custom: true}';
				}

				$str = '"'.$template.'": {main: "'.htmlspecialchars($config['main'], ENT_QUOTES).'", blocks: ['
					.(!empty($blocks) ? implode(', ', $blocks) : '').']}';
				$arr[] = $str;
			}

			$defstr = 'var TL33T_def = {'.implode(', ', $arr).'}; ';
			echo $defstr;

		} else {

			echo 'var TL33T_def = {}; ';

		}

		echo '
			/* ]]> */
			</script>';

	}
	
	/**
	 * Append templ33t field content to main content in comment form for searchability.
	 * @global array $templ33t_templates
	 * @param string $content
	 * @return string
	 */
	function templ33t_add_comment($content = null) {

		global $templ33t, $templ33t_templates, $wpdb, $post;

		if(array_key_exists('meta', $_POST) && !empty($_POST['meta'])) {

			// get template from post
			$template = array_key_exists('page_template', $_POST) && !empty($_POST['page_template']) ? $_POST['page_template'] : basename(get_page_template());
			if($template == 'default') $template = basename(get_page_template());

			// grab searchable block slugs
			$searchable = array();
			if(array_key_exists($template, $this->map)) {
				foreach($this->map[$template]['blocks'] as $slug => $block) {
					if($block['searchable']) $searchable[] = $slug;
				}
			}

			if(!empty($searchable)) {

				// grab meta ids
				$metadata = array();
				foreach($_POST['meta'] as $key => $data) {
					$metadata[$data['key']] = array('id' => $key, 'value' => $data['value']);
				}

				// strip existing comment
				$content = $this->strip_comment($content);

				// generate apend string
				$append = '<!-- TEMPL33T[ ';
				foreach($searchable as $slug) {

					if(!array_key_exists($slug, $this->block_objects)) {

						$block = $this->map[$template]['blocks'][$slug];
						$block['slug'] = $slug;
						$block['id'] = $metadata['templ33t_'.$slug]['id'];
						$block['value'] = $metadata['templ33t_'.$slug]['value'];

						$this->block_objects[$slug] = Templ33tPluginHandler::instantiate($block['type'], $block);

					}

					//$append .= '  ::' . $slug . ':: ' . preg_replace('/(<!--.+?-->)/mi', '', preg_replace('/([\r\n]+)/mi', ' ', $templ33t->block_objects[$slug]->value));
					$append .= '  ::' . $slug . ':: ' . strip_tags(nl2br($this->block_objects[$slug]->value));

				}
				$append .= ' ]END_TEMPL33T -->';

				$content .= '  '.$append;

			}

		}

		return $content;

	}
	
	/**
	 * Remove templ33t field comment from end of page content before editing.
	 * @param string $content
	 * @return string
	 */
	function strip_comment($content = null) {

		$content = preg_replace('/(<!--\ TEMPL33T\[.+?\]END\_TEMPL33T\ -->)/mi', '', $content);

		return $content;

	}
	
	/**
	 * Adds the tab bar to the edit page.
	 *
	 * @global array $templ33t_templates
	 * @global mixed $templ33t_meta
	 * @global boolean $templ33t_render
	 * @global object $post
	 */
	function tab_elements() {

		global $templ33t, $templ33t_templates, $templ33t_options, $templ33t_meta, $templ33t_render, $post;

		// keep track of selected template
		echo '<input type="hidden" name="templ33t_template" value="'.$post->page_template.'" />';

		if($this->render) {

			// grab main label
			if(array_key_exists($post->page_template, $this->map)) {
				$main_label = $this->map[$post->page_template]['main'];
				$main_desc = $this->map[$post->page_template]['description'];
			} elseif(array_key_exists('ALL', $this->map)) {
				$main_label = $this->map['ALL']['main'];
				$main_desc = $this->map['ALL']['description'];
			} else {
				$main_label = 'Page Content';
				$main_desc = 'Enter your page content here.';
			}

			// set up item lists
			$tabs = '';
			$descs = '';
			$editors = '';

			// add default tab
			$tabs .= '<li id="templ33t_default" class="selected"><a href="#" rel="default">'.$main_label.'</a><div id="templ33t_main_content"></div></li>';
			$descs .= '<div class="templ33t_description templ33t_desc_default"><p>'.$main_desc.'</p></div>';


			if(!empty($this->block_objects)) {
				//foreach($templ33t_templates[$post->page_template]['blocks'] as $slug => $block) {
				foreach($this->block_objects as $slug => $block) {

					//$cname = Templ33tPluginHandler::load($block['type']);
					//$instance = $block['instance'];

					// get class name
					$cname = Templ33tPluginHandler::load($block->type);

					$tabs .= '<li><a href="#" rel="'.$block->id.'">'.$block->label.'</a></li>';
					$descs .= '<div class="templ33t_description templ33t_desc_'.$block->id.' templ33t_hidden"><p>'.$block->description.'</p></div>';

					if($cname::$custom_panel) {
						$editors .= '<div id="templ33t_editor_'.$block->id.'" class="templ33t_editor" style="display: none;">';
						if($block instanceOf Templ33tTab)
							$editors .= $block->displayPanel();
						else
							$editors .= '<div id="templ33t_editor_'.$block->id.'" class="templ33t_editor templ33t_hidden"><input type="hidden" name="meta['.$block->id.'][key]" value="templ33t_'.$block->slug.'" /><textarea id="templ33t_val_'.$block->id.'" name="meta['.$block->id.'][value]">'.$block->value.'</textarea></div>';
						$editors .= '</div>';
					} else {
						$editors .= '<div id="templ33t_editor_'.$block->id.'" class="templ33t_editor templ33t_hidden"><input type="hidden" name="meta['.$block->id.'][key]" value="templ33t_'.$block->slug.'" /><textarea id="templ33t_val_'.$block->id.'" name="meta['.$block->id.'][value]">'.$block->value.'</textarea></div>';
					}

				}
			}

			/*
			// grab theme-wide tabs
			if(array_key_exists('ALL', $templ33t_templates)) {
				foreach($templ33t_templates['ALL']['blocks'] as $slug => $block) {
					$tabs .= '<li><a href="#" rel="'.$templ33t_meta[$slug]['id'].'">'.$block['label'].'</a></li>';
					$descs .= '<div class="templ33t_description templ33t_desc_'.$templ33t_meta[$slug]['id'].' templ33t_hidden"><p>'.$block['description'].'</p></div>';
					$editors .= '<div id="templ33t_editor_'.$templ33t_meta[$slug]['id'].'" class="templ33t_editor">';
					if($block['type'] == 'editor') {
						$editors .= '<textarea rows="10" class="theEditor" cols="40" name="content" tabindex="2" id="content"></textarea>';
					} else {
						$editors .= '<p>THIS BLOCK IS A: '.$block['type'].'</p>';
					}
					$editors .'</div>';
				}
			}
			*/

			// add settings tab if exists
			if(!empty($this->option_objects)) {

				$tabs .= '<li class="templ33t_settings"><a href="#" rel="settings">Settings</a></li>';
				$descs .= '<div class="templ33t_description templ33t_desc_settings templ33t_hidden"><p>These are the settings available for this template.</p></div>';
				$editors .= '<div id="templ33t_editor_settings" class="templ33t_editor postbox" style="display: none;"><div class="inside"><p class="templ33t_description">These are the settings available for this page.</p><table border="0">';
				foreach($this->option_objects as $slug => $option) {
					if($option instanceOf Templ33tOption)
						$editors .= $option->displayOption();
					else
						$editors .= '<tr><td>'.$option->label.'</td><td><input type="hidden" name="meta['.$option->id.'][key]" value="templ33t_option_'.$option->slug.'" /><input type="text" name="meta['.$option->id.'][value]" value="'.$option->value.'" /></td></tr>';
				}
				$editors .= '</table></div></div>';

			}

			// output tab bar & descriptions
			echo '<div id="templ33t_control" style="display: none;"><ul>';
			echo $tabs;
			echo '</ul></div><div id="templ33t_descriptions" style="display: none;">';
			echo $descs;
			echo '</div><div id="templ33t_editors" class="templ33t_editors" style="display: none;">';
			echo $editors;
			echo '</div>';

		// make sure current template is known in case of switch
		}



	}
	
	function save_options() {

		if(array_key_exists($_POST['templ33t_template'], $this->map)) {

			if(array_key_exists('meta', $_POST) && !empty($_POST['meta'])) {

				foreach($_POST['meta'] as $id => $data) {

					$slug = str_replace('templ33t_', '', str_replace('templ33t_option_', '', $data['key']));

					// handle blocks
					if(array_key_exists($slug, $this->map[$_POST['templ33t_template']]['blocks'])) {

						$block = $this->map[$_POST['templ33t_template']]['blocks'][$slug];
						$block['slug'] = $slug;
						$block['id'] = $id;
						$block['value'] = $data['value'];

						$cname = Templ33tPluginHandler::load($block['type']);

						if($cname::$custom_post) {

							$instance = Templ33tPluginHandler::instantiate($block['type'], $block);

							$instance->handlePost();

							$_POST['meta'][$instance->id] = array(
								'key' => 'templ33t_'.$instance->slug,
								'value' => $instance->value,
							);

							$this->block_objects[$slug] = $instance;

						}
					}

					// handle options
					if(array_key_exists($slug, $this->map[$_POST['templ33t_template']]['options'])) {

						$option = $this->map[$_POST['templ33t_template']]['options'][$slug];
						$option['slug'] = $slug;
						$option['id'] = $id;
						$option['value'] = $data['value'];

						$cname = Templ33tPluginHandler::load($option['type']);

						if($cname::$custom_post) {

							$instance = Templ33tPluginHandler::instantiate($option['type'], $option);

							$instance->handlePost();

							$_POST['meta'][$instance->id] = array(
								'key' => 'templ33t_option_'.$instance->slug,
								'value' => $instance->value,
							);

							$this->option_objects[$slug] = $instance;

						}

					}

				}

			}

			if(array_key_exists('templ33t_meta', $_POST)) {

				foreach($_POST['templ33t_meta'] as $slug => $data) {

					// handle blocks
					if(array_key_exists($slug, $this->map[$_POST['templ33t_template']]['blocks'])) {

						$block = $this->map[$_POST['templ33t_template']]['blocks'][$slug];
						$block['slug'] = $slug;
						$block['id'] = $data['id'];
						$block['value'] = $data['value'];

						$instance = Templ33tPluginHandler::instantiate($block['type'], $block);
						$instance->handlePost();
						$_POST['meta'][$instance->id] = array(
							'key' => 'templ33t_'.$instance->slug,
							'value' => $instance->value,
						);

						$this->block_objects[$slug] = $instance;

					}

					// handle options
					if(array_key_exists($slug, $this->map[$_POST['templ33t_template']]['options'])) {

						$option = $this->map[$_POST['templ33t_template']]['options'][$slug];
						$option['slug'] = $slug;
						$option['id'] = $data['id'];
						$option['value'] = $data['value'];

						$instance = Templ33tPluginHandler::instantiate($option['type'], $option);
						$instance->handlePost();
						$_POST['meta'][$instance->id] = array(
							'key' => 'templ33t_option_'.$instance->slug,
							'value' => $instance->value,
						);

						$this->option_objects[$slug] = $instance;

					}

				}

			}

		}

	}
	
	/**
	 * Enqueue css for settings page
	 */
	function settings_styles() {

		wp_enqueue_style('templ33t_styles');
		wp_enqueue_style('thickbox');

	}

	/**
	 * Enqueue js for settings page
	 */
	function settings_scripts() {

		wp_deregister_script('jquery');
		wp_register_script('jquery', 'http://code.jquery.com/jquery-1.4.2.min.js');
		wp_enqueue_script('thickbox');
		wp_enqueue_script('templ33t_settings_scripts', null, array('jquery'));

	}
	
	/**
	 * Catch and act upon settings post & actions
	 * @global object $wpdb
	 */
	function save_settings() {

		global $templ33t, $templ33t_menu_parent, $templ33t_settings_url, $wpdb, $wp_content_dir;

		$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
		$template_table_name = $the_prefix . 'templ33t_templates';
		$block_table_name = $the_prefix . 'templ33t_blocks';

		// catch settings via post
		if(!empty($_POST)) {

			// add template to theme
			if(array_key_exists('templ33t_new_template', $_POST)) {

				// required (non-empty) fields array
				$required = array(
					'templ33t_theme',
					'templ33t_template',
					//'templ33t_main_label',
				);

				$errors = array();

				// check and sterilize
				foreach($required as $field) {
					if(!array_key_exists($field, $_POST) || empty($_POST[$field])) {
						$errors[] = str_replace('templ33t_', '', $field);
					} else {
						$_POST[$field] = htmlspecialchars($_POST[$field], ENT_QUOTES);
					}
				}

				$tfile = $wp_content_dir . '/themes/' . $_POST['templ33t_theme'] . '/' . $_POST['templ33t_template'];

				if(!file_exists($tfile))
					$errors[] = 'notemp';

				if(!empty($errors)) {

					// redirect with errors
					$redirect = self::$settings_url;
					if(array_key_exists('templ33t_theme', $_POST)) $redirect .= '&theme='.$_POST['templ33t_theme'];
					elseif(array_key_exists('theme', $_GET)) $redirect .= '&theme='.$_GET['theme'];
					$redirect .= '&error='.implode('|', $errors);
					wp_redirect($redirect);

				} else {

					// grab templ33t config
					$config = $this->parseTemplate($tfile);

					// set up insert array
					$i_arr = array(
						'theme' => $_POST['templ33t_theme'],
						'template' => $_POST['templ33t_template'],
						'config' => serialize($config),
					);

					// check for duplicates
					$check = $wpdb->get_row('SELECT * FROM `'.$template_table_name.'` WHERE `theme` = "'.$i_arr['theme'].'" AND `template` = "'.$i_arr['template'].'" LIMIT 1', ARRAY_A);

					if(empty($check)) {

						// insert record
						$insert = $wpdb->insert(
							$template_table_name,
							$i_arr
						);


						/*
						$tid = $wpdb->get_var('SELECT LAST_INSERT_ID() FROM '.$template_table_name.' LIMIT 1');

						foreach($config['blocks'] as $key => $val) {

							// set up insert array
							$i_arr = array(
								'theme' => $_POST['templ33t_theme'],
								'template_id' => $tid,
								'block_name' => $val['title'],
								'block_slug' => $key,
								'block_type' => $val['type'],
								'block_description' => $val['description'],
							);

							// save block
							$insert = $wpdb->insert(
								$block_table_name,
								$i_arr
							);

						}
						*/
						// update map dev version
						$t_dev = $this->getOption('templ33t_map_dev');
						$t_dev++;
						$this->updateOption('templ33t_map_dev', $t_dev);

						// return to settings page
						$redirect = self::$settings_url.'&theme='.$i_arr['theme'];
						wp_redirect($redirect);

					} else {

						// redirect with error
						$redirect = self::$settings_url.'&theme='.$i_arr['theme'].'&error=duptemp';
						wp_redirect($redirect);

					}

				}

			// add block to template
			} elseif(array_key_exists('templ33t_new_block', $_POST)) {

				// required (non-empty) fields array
				$required = array(
					'templ33t_theme',
					'templ33t_template',
					'templ33t_block'
				);

				$errors = array();

				// check and sterilize
				foreach($required as $field) {
					if(!array_key_exists($field, $_POST) || empty($_POST[$field])) {
						$errors[] = str_replace('templ33t_', '', $field);
					} else {
						$_POST[$field] = htmlspecialchars($_POST[$field], ENT_QUOTES);
					}
				}

				if(!empty($errors)) {

					// redirect with errors
					$redirect = self::$settings_url;
					if(array_key_exists('templ33t_theme', $_POST)) $redirect .= '&theme='.$_POST['templ33t_theme'];
					$redirect .= '&error='.implode('|', $errors);
					wp_redirect($redirect);

				} else {

					// set up insert array
					$i_arr = array(
						'theme' => $_POST['templ33t_theme'],
						'template_id' => $_POST['templ33t_template'],
						'block_name' => $_POST['templ33t_block'],
						'block_slug' => '',
						'block_description' => array_key_exists('templ33t_block_description', $_POST) ? htmlspecialchars($_POST['templ33t_block_description'], ENT_QUOTES) : '',
					);

					// remove template definition if set to all
					if($i_arr['template_id'] == 'ALL') unset($i_arr['template_id']);

					// generate slug
					$i_arr['block_slug'] = strtolower(str_replace(' ', '_', trim(chop(preg_replace('/([^a-z0-9]+)/i', ' ', $_POST['templ33t_block'])))));

					// set where conditions for template_id
					$t_where = '`template_id` IS NULL';
					if(array_key_exists('template_id', $i_arr))
						$t_where = '('.$t_where.' OR `template_id` = "'.$i_arr['template_id'].'")';

					// check for duplicates
					$check = $wpdb->get_row(
						'SELECT * FROM `'.$block_table_name.'`
						WHERE
							`theme` = "'.$i_arr['theme'].'"
							AND '.$t_where.'
							AND `block_slug` = "'.$i_arr['block_slug'].'"
						LIMIT 1',
						ARRAY_A
					);

					if(empty($check)) {

						// save block
						$insert = $wpdb->insert(
							$block_table_name,
							$i_arr
						);

						// update map dev version
						$t_dev = $this->getOption('templ33t_map_dev');
						$t_dev++;
						$this->updateOption('templ33t_map_dev', $t_dev);


						// return to settings page
						$redirect = self::$settings_url.'&theme='.$_POST['templ33t_theme'];
						wp_redirect($redirect);

					} else {

						// redirect with error
						$redirect = self::$settings_url.'&error=dupblock';
						wp_redirect($redirect);

					}

				}

			}

		}

		// catch actions sent via GET
		if(isset($_GET['t_action'])) {

			switch($_GET['t_action']) {

				// scan theme templates for configurations
				case 'scan':

					if(!empty($_GET['theme'])) {

						$theme = htmlspecialchars($_GET['theme'], ENT_QUOTES);

						// get old config
						$compare = array();
						$old = $wpdb->get_results('SELECT * FROM `'.$template_table_name.'` WHERE `theme` = "'.$theme.'"', ARRAY_A);
						foreach($old as $key => $val) {
							$compare[$val['template']] = unserialize($val['config']);
						}

						// get templates
						$templates = $this->parseTheme($theme);

						if($compare != $templates) {

							// delete previous records
							$wpdb->query('DELETE FROM `'.$template_table_name.'` WHERE `theme` = "'.$theme.'"');

							// update map dev version
							$t_dev = $this->getOption('templ33t_map_dev');
							$t_dev++;
							$this->updateOption('templ33t_map_dev', $t_dev);

							if(!empty($templates)) {

								// insert new data
								foreach($templates as $key => $val) {
									$wpdb->insert(
										$template_table_name,
										array(
											'theme' => $theme,
											'template' => $key,
											'config' => serialize($val)
										)
									);
								}

								// return to settings page
								$redirect = self::$settings_url.'&theme='.$theme;
								wp_redirect($redirect);

							} else {

								// return to settings page
								$redirect = self::$settings_url.'&theme='.$theme.'&error=nothemeconfig';
								wp_redirect($redirect);

							}

						} else {

							// return to settings page
							$redirect = self::$settings_url.'&theme='.$theme.'&error=nochange';
							wp_redirect($redirect);

						}

					} else {

						// return to settings page
						$redirect = self::$settings_url.'&error=notheme';
						wp_redirect($redirect);

					}


					break;

				// rescan template config
				case 'rescan':

					// grab template from db
					$temp = $wpdb->get_row('SELECT * FROM `'.$template_table_name.'` WHERE `templ33t_template_id` = "'.htmlspecialchars($_GET['tid'], ENT_QUOTES).'"', ARRAY_A);

					if(!empty($temp)) {

						$tfile = $wp_content_dir . '/themes/' . $temp['theme'] . '/' . $temp['template'];

						if(file_exists($tfile)) {

							// grab templ33t config
							$config = $this->parseTemplate($tfile);

							if(!empty($config)) {

								if(unserialize($temp['config']) != $config) {

									// save changes
									$wpdb->update($template_table_name, array('config' => serialize($config)), array('templ33t_template_id' => $temp['templ33t_template_id']));

									// update map dev version
									$t_dev = $this->getOption('templ33t_map_dev');
									$t_dev++;
									$this->updateOption('templ33t_map_dev', $t_dev);

									// return to settings page
									$redirect = self::$settings_url.'&theme='.$temp['theme'];
									wp_redirect($redirect);

								} else {

									// return to settings page
									$redirect = self::$settings_url.'&theme='.$temp['theme'].'&error=nochange';
									wp_redirect($redirect);

								}

							} else {

								// return to settings page
								$redirect = self::$settings_url.'&theme='.$temp['theme'].'&error=noconfig';
								wp_redirect($redirect);

							}

						} else {

							// return error if non-existent
							wp_redirect(self::$settings_url.'&theme='.$_GET['theme'].'&error=notemp');

						}

					} else {

						// return error if non-existent
						wp_redirect(self::$settings_url.'&theme='.$_GET['theme'].'&error=notemp');

					}

					break;

				// delete template
				case 'deltemp':

					// grab template
					$row = $wpdb->get_row('SELECT * FROM `'.$template_table_name.'` WHERE `templ33t_template_id` = "'.htmlspecialchars($_GET['tid'], ENT_QUOTES).'"', ARRAY_A);

					if(!empty($row)) {

						// delete if exists
						$sql_temp = 'DELETE FROM `'.$template_table_name.'` WHERE `templ33t_template_id` = '.$row['templ33t_template_id'].' LIMIT 1';
						$wpdb->query($sql_temp);

						$sql_block = 'DELETE FROM `'.$block_table_name.'` WHERE `template_id` = '.$row['templ33t_template_id'];
						$wpdb->query($sql_block);

						// update map dev version
						$t_dev = $this->getOption('templ33t_map_dev');
						$t_dev++;
						$this->updateOption('templ33t_map_dev', $t_dev);

						wp_redirect(self::$settings_url.'&theme='.$row['theme']);

					} else {

						// return error if non-existent
						wp_redirect(self::$settings_url.'&theme='.$_GET['theme'].'&error=notemp');

					}

					break;

				// delete block
				case 'delblock':

					// grab block
					$row = $wpdb->get_row('SELECT * FROM `'.$block_table_name.'` WHERE `templ33t_block_id` = "'.htmlspecialchars($_GET['bid'], ENT_QUOTES).'"', ARRAY_A);

					if(!empty($row)) {

						// delete if exists
						$sql = 'DELETE FROM `'.$block_table_name.'` WHERE `templ33t_block_id` = '.$row['templ33t_block_id'].' LIMIT 1';
						$wpdb->query($sql);

						// update map dev version
						$t_dev = $this->getOption('templ33t_map_dev');
						$t_dev++;
						$this->updateOption('templ33t_map_dev', $t_dev);


						wp_redirect(self::$settings_url.'&theme='.$row['theme']);

					} else {

						// return error if non-existent
						wp_redirect(self::$settings_url.'&theme='.$_GET['theme'].'&error=noblock');

					}

					break;

				// publish latest template map
				case 'publish':

					// get current configuration versions
					$pub = $this->getOption('templ33t_map_pub');
					$dev = $this->getOption('templ33t_map_dev');

					//if($pub < $dev) {


						$template_sql = 'SELECT `theme`, `template`, `config` FROM `'.$template_table_name.'`';

						// grab templates from the database
						$templates = $wpdb->get_results($template_sql, ARRAY_A);

						$this->map = array();

						// map templates and blocks
						if(!empty($templates)) {
							foreach($templates as $tmp) {

								// add theme to map
								if(!array_key_exists($tmp['theme'], $this->map))
									$this->map[$tmp['theme']] = array();

								/*
								// fill out default data for theme-wide blocks
								if(empty($tmp['template'])) {
									$tmp['template'] = 'ALL';
									$tmp['main_label'] = 'Main Content';
									$tmp['main_description'] = '';
								}

								// add template to map
								if(!array_key_exists($tmp['template'], $templ33t_map[$tmp['theme']])) {
									$templ33t_map[$tmp['theme']][$tmp['template']] = array(
										'main' => $tmp['main_label'],
										'main_description' => $tmp['main_description'],
										'blocks' => array()
									);
								}

								// add block to map
								$templ33t_map[$tmp['theme']][$tmp['template']]['blocks'][$tmp['block_slug']] = array(
									'type' => $tmp['block_type'],
									'label' => $tmp['block_name'],
									'description' => $tmp['block_description']
								);
								*/

								// add template
								$this->map[$tmp['theme']][$tmp['template']] = unserialize($tmp['config']);

							}
						}

						// save latest configuration version
						$this->updateOption('templ33t_map', serialize($this->map));
						$this->updateOption('templ33t_map_pub', $dev);

						wp_redirect(self::$settings_url.'&theme='.$_GET['theme']);

					//} else {

					//	wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=nopub');

					//}
					break;

				// return error on invalid action
				default:

					wp_redirect(self::$settings_url.'&theme='.$_GET['theme'].'&error=noaction');
					break;

			}

		}

	}
	
	/**
	 * Transform templ33t fields comment into appended text for search results
	 * @param string $content
	 * @return string
	 */
	function search_results($content = null) {

		if(is_search()) {

			$content = preg_replace('/(::[A-Z0-9\_\-]+::)/i', ' ... ', preg_replace('/(<!--\ TEMPL33T\[)(.+?)(\]END\_TEMPL33T\ -->)/mi', '$2', $content));

		} elseif(is_page()) {

			$content = templ33t_strip_comment($content);

		}

		return $content;

	}
	
	/**
	 * Output Templ33t settings page.
	 * @global object $wpdb
	 * @global array $templ33t_errors
	 */

	function settings() {

		global $templ33t, $templ33t_menu_parent, $templ33t_settings_url, $templ33t_errors, $wpdb;

		// get current configuration versions
		$pub = $this->getOption('templ33t_map_pub');
		$dev = $this->getOption('templ33t_map_dev');

		// set table names
		$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
		$templates_table_name = $the_prefix . 'templ33t_templates';
		$blocks_table_name = $the_prefix . 'templ33t_blocks';

		// grab theme list
		$themes = get_themes();

		// count themes
		$theme_count = count($themes);

		// select theme
		if(isset($_GET['theme']) && !empty($_GET['theme'])) {
			$theme_selected = htmlspecialchars($_GET['theme'], ENT_QUOTES);
		} else {
			$theme_selected = get_stylesheet();
		}

		/*
		// grab templates for selected theme
		$templates = $wpdb->get_results(
			'SELECT `templ33t_template_id`, `template`, `main_label`, `main_description`
			FROM `'.$templates_table_name.'`
			WHERE `theme` = "'.$theme_selected.'"',
			ARRAY_A
		);

		// grab blocks for theme
		$blocks = $wpdb->get_results(
			'SELECT *
			FROM `'.$blocks_table_name.'`
			WHERE `theme` = "'.$theme_selected.'"
			ORDER BY `block_name`',
			ARRAY_A
		);

		// map blocks to templates
		$block_map = array();
		if(!empty($blocks)) {
			foreach($blocks as $key => $val) {

				if(empty($val['template_id'])) $val['template_id'] = 'ALL';

				if(!array_key_exists($val['template_id'], $block_map)) {
					$block_map[$val['template_id']] = array($val['templ33t_block_id'] => $val);
				} else {
					$block_map[$val['template_id']][$val['templ33t_block_id']] = $val;
				}

			}
		}
		*/

		// grab templates for selected theme
		$templates = $wpdb->get_results(
			'SELECT `templ33t_template_id`, `template`, `config`
			FROM `'.$templates_table_name.'`
			WHERE `theme` = "'.$theme_selected.'"',
			ARRAY_A
		);

		// unserialize template config
		if(!empty($templates)) {
			foreach($templates as $key => $val) {
				$templates[$key]['config'] = unserialize($val['config']);
			}
		}

		//die(print_r($templates, true));

		// parse error message
		$error = null;
		if(isset($_GET['error'])) {
			if(strpos($_GET['error'], '|') !== false) {
				$error = explode('|', $_GET['error']);
				foreach($error as $key => $err) {
					$error[$key] = $this->errors[$err];
				}
				$error = implode('<br/>', $error);
			} else {
				$error = $this->errors[$_GET['error']];
			}
		}

		?>

		<h2>Templ33t Configuration</h2>

		<?php if($pub < $dev) { ?>
		<div id="templ33t_publish">
			<p>
				Your configuration has changed. Would you like to publish it for use?
				<a href="<?php echo self::$settings_url.'&theme='.$theme_selected.'&t_action=publish'; ?>">Publish Configuration</a>
			</p>
		</div>
		<?php } ?>

		<?php if(!empty($error)) { ?>
		<p class="templ33t_error">
			<?php echo $error; ?>
		</p>
		<?php } ?>

		<br/>

		<div id="templ33t_settings">

			<div class="templ33t_themes">

				<ul>
					<?php $x = 1; foreach($themes as $key => $val) { ?>
					<li class="<?php if($theme_selected == $val['Template']) echo 'selected'; if($x == 1) echo ' first'; elseif($x == $theme_count) echo ' last'; ?>" rel="<?php echo $val['Template']; ?>">
						<a href="<?php echo self::$settings_url; ?>&theme=<?php echo $val['Template']; ?>">
							<?php if(strlen($key) > 22) echo substr($key, 0, 22).'...'; else echo $key; ?>
						</a>
					</li>
					<?php $x++; } ?>
				</ul>

			</div>

			<div class="templ33t_blocks">

				<div>

					<div>

						<a href="#TB_inline?width=400&height=180&inlineId=templ33t_new_template_container&modal=true" class="thickbox">Add Template</a> |
						<a href="<?php echo self::$settings_url; ?>&t_action=scan&theme=<?php echo $theme_selected; ?>">Scan All Templates</a>

						<div id="templ33t_new_template_container" style="display: none; text-align: center;">
							<form id="templ33t_new_template" action="<?php echo self::$settings_url; ?>" method="post">
								<input type="hidden" name="templ33t_theme" value="<?php echo $theme_selected; ?>" />

								<h2>Scan a Template File</h2>

								<br/>

								<table border="0" width="100%">
									<tr>
										<td><label for="templ33t_template">Template File Name: </label></td>
										<td><input type="text" class="templ33t_template" name="templ33t_template" value="" size="30" /></td>
									</tr>
									<tr>
										<td colspan="2" align="center">
											<br/>
											<input type="button" value="Cancel" onclick="tb_remove(); return false;" />
											<input type="submit" name="templ33t_new_template" value="Add Template" />
										</td>
									</tr>
								</table>

							</form>
						</div>

					</div>

					<hr/>


					<!-- template navigation -->
					<?php if(!empty($templates)) { ?>
					<div id="templ33t_control">
						<ul>
							<?php $x = 0; foreach($templates as $tkey => $tval) { ?>
							<li<?php if($x == 0) { ?> class="selected"<?php } ?>><a href="#" rel="<?php echo $tval['templ33t_template_id']; ?>"><?php echo $tval['template']; ?></a></li>
							<?php $x++; } ?>
						</ul>
					</div>
					<?php } ?>


					<ul>
						<?php /*
						<li class="templ33t_all_box">
							<div class="templ33t_right">


								<a href="#TB_inline?width=400&height=220&inlineId=templ33t_all_block_container&modal=true" class="thickbox">Add Content Block</a>

								<div id="templ33t_all_block_container" style="display: none; text-align: center;">
									<form id="templ33t_new_template" action="<?php echo $templ33t_settings_url; ?>" method="post">
										<input type="hidden" name="templ33t_theme" value="<?php echo $theme_selected; ?>" />
										<input type="hidden" name="templ33t_template" value="ALL" />

										<h2>Add a Theme-Wide Content Block</h2>

										<p>This content block will be available to all templates within this theme.</p>

										<table border="0" width="100%">
											<tr>
												<td><label for="templ33t_main_label">Content Block Label: </label></td>
												<td><input type="text" class="templ33t_block" name="templ33t_block" value="" size="30" /></td>
											</tr>
											<tr>
												<td valign="top"><label for="templ33t_block_description">Content Block Description: </label></td>
												<td><textarea class="templ33t_block_description" name="templ33t_block_description"></textarea></td>
											</tr>
											<tr>
												<td colspan="2" align="center">
													<br/>
													<input type="button" value="Cancel" onclick="tb_remove(); return false;" />
													<input type="submit" name="templ33t_new_block" value="Add Block" />
												</td>
											</tr>
										</table>

									</form>
								</div>

							</div>

							<h2>Theme Wide / All Templates</h2>
							<hr/>
							<?php if(array_key_exists('ALL', $block_map) && !empty($block_map['ALL'])) { ?>
							<ul>
								<?php foreach($block_map['ALL'] as $bkey => $bval) { ?>
								<li>
									<a class="delblock" href="<?php echo $templ33t_settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=delblock&bid=<?php echo $bval['templ33t_block_id']; ?>" onclick="return confirm('Are you sure you want to remove this custom block?');">[X]</a>
									<strong><?php echo $bval['block_name']; ?></strong> (<?php echo $bval['block_slug']; ?>)<br/>
									<hr/>
									<p><?php echo $bval['block_description'] ? $bval['block_description'] : 'No Description'; ?></p>
								</li>
								<?php } ?>
							</ul>
							<?php } else { ?>
							<p>No Content Blocks</p>
							<?php } ?>
						</li>
						*/ ?>







						<?php if(!empty($templates)) { $x = 0; foreach($templates as $tkey => $tval) { ?>
						<li class="templ33t_template_box" rel="<?php echo $tval['templ33t_template_id']; ?>" <?php if($x > 0) { ?> style="display: none;"<?php } ?>>
							<div class="templ33t_right">


								<!--
								<a href="#TB_inline?width=400&height=220&inlineId=templ33t_new_block_container_<?php echo $tval['templ33t_template_id']; ?>&modal=true" class="thickbox">Add Content Block</a>

								<div id="templ33t_new_block_container_<?php echo $tval['templ33t_template_id']; ?>" style="display: none; text-align: center;">
									<form id="templ33t_new_template" action="<?php echo $templ33t_settings_url; ?>" method="post">
										<input type="hidden" name="templ33t_theme" value="<?php echo $theme_selected; ?>" />
										<input type="hidden" name="templ33t_template" value="<?php echo $tval['templ33t_template_id']; ?>" />

										<h2>Add a Theme-Wide Content Block</h2>

										<p>This content block will be available to all templates within this theme.</p>

										<table border="0" width="100%">
											<tr>
												<td><label for="templ33t_main_label">Content Block Label: </label></td>
												<td><input type="text" class="templ33t_block" name="templ33t_block" value="" size="30" /></td>
											</tr>
											<tr>
												<td valign="top"><label for="templ33t_block_description">Content Block Description: </label></td>
												<td><textarea class="templ33t_block_description" name="templ33t_block_description"></textarea></td>
											</tr>
											<tr>
												<td colspan="2" align="center">
													<br/>
													<input type="button" value="Cancel" onclick="tb_remove(); return false;" />
													<input type="submit" name="templ33t_new_block" value="Add Block" />
												</td>
											</tr>
										</table>

									</form>
								</div>
								-->

								<a href="<?php echo self::$settings_url; ?>&t_action=rescan&tid=<?php echo $tval['templ33t_template_id']; ?>">Rescan Template Configuration</a>

							</div>
							<h2><?php echo $tval['template']; ?></h2>
							<hr/>

							<h4>Blocks</h4>

							<ul>
								<li>
									<strong><?php echo $tval['config']['main']; ?></strong> - main content label
									<hr/>
									<p><?php echo $tval['config']['description'] ? $tval['config']['description'] : 'No Description'; ?></p>
								</li>
								<?php if(!empty($tval['config']['blocks'])) { foreach($tval['config']['blocks'] as $bkey => $bval) { ?>
								<li>
									<div class="templ33t_right">
										Type: <?php echo $bval['type']; ?>
									</div>
									<!-- <a class="delblock" href="<?php echo self::$settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=delblock&bid=<?php //echo $bval['templ33t_block_id']; ?>" onclick="return confirm('Are you sure you want to remove this custom block?');">[X]</a> -->
									<strong><?php echo $bval['label']; ?></strong> (<?php echo $bkey; ?>)<br/>
									<hr/>
									<p><?php echo $bval['description'] ? $bval['description'] : 'No Description'; ?></p>
								</li>
								<?php } } ?>
							</ul>
							<h4>Options</h4>
							<ul>
								<?php if(!empty($tval['config']['options'])) { foreach($tval['config']['options'] as $okey => $oval) { ?>
								<li>
									<div class="templ33t_right">
										Type: <?php echo $oval['type']; ?>
									</div>
									<strong><?php echo $oval['label']; ?></strong> (<?php echo $okey; ?>)<br/>
									<hr/>
									<p><?php echo $oval['description'] ? $oval['description'] : 'No Description'; ?></p>
								</li>
								<?php } } ?>
							</ul>
							<br/>
							<p class="templ33t_right">
								<a class="deltemp" href="<?php echo self::$settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=deltemp&tid=<?php echo $tval['templ33t_template_id']; ?>" onclick="return confirm('Are you sure you want to remove this template and all content blocks associated with it?');">Remove This Template</a>
							</p>
							<div class="templ33t_clear_right"></div>
						</li>
						<?php $x++; } } ?>
					</ul>


				</div>

				<div class="templ33t_clear"></div>

			</div>

			<div class="templ33t_clear"></div>

		</div>

		<?php

	}
	
}

?>