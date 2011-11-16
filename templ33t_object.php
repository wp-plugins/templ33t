<?php

class Templ33t {

	static $version = '0.2';
	static $db_version = '0.2';
	static $templates_table;
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
		'global' => false,
		'config' => '',
		'group' => null,
		'optional' => false,
		'bindable' => false,
		'searchable' => false,
		'weight' => '',
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
	var $default_template = 'page.php';
	var $meta = array();
	var $load_plugs = array();
	var $block_objects = array();
	var $option_objects = array();
	var $groups = array();
	var $menu_parent = null;
	var $errors = array(
		'theme' => 'Please choose a theme.',
		'template' => 'Please enter a template file name.',
		'mainlabel' => 'Please enter the main label for this template.',
		'block' => 'Please enter a custom block name.',
		'duptemp' => 'This template has already been added.',
		'dupblock' => 'This block already exists.',
		'notemp' => 'This template file does not exist in the chosen theme.',
		'notheme' => 'No theme chosen.',
		'noblock' => 'Invalid block.',
		'nopub' => 'The most recent configuration has already been published.',
		'noaction' => 'Invalid action.',
		'nochange' => 'No configuration changes detected.',
		'nothemeconfig' => 'No Templ33t configuration detected for this theme. The cached configuration can be removed.',
		'noconfig' => 'No Templ33t configuration detected for this template. The cached configuration can be removed.',
	);

	function __construct() {

		global $wp_version, $wpdb;

		// generate paths
		if (empty(self::$wp_content_url))
			$this->fillPaths();

		// set table names
		$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
		self::$templates_table = $the_prefix . 'templ33t_templates';

		// check existence of site options
		$this->use_site_option = function_exists('add_site_option');

		// grab theme name
		$theme = get_stylesheet();

		// get theme map
		$this->map = unserialize($this->getOption('templ33t_map'));
		if (is_array($this->map) && array_key_exists($theme, $this->map)) {
			$this->templates = $this->map[$theme];
			$this->active = true;
		} else {
			$this->templates = array();
			$this->active = false;
		}

		// install & uninstall
		register_activation_hook(__FILE__, array($this, 'install'));
		register_deactivation_hook(__FILE__, array($this, 'uninstall'));

		// actions & filters
		add_action('admin_init', array($this, 'init'), 1);
		add_action('admin_menu', array($this, 'menu'));
		if($wp_version > '3.1.0')
			add_action('network_admin_menu', array($this, 'adminMenu'));
		add_filter('the_content', array($this, 'searchResults'), 1);
		add_action('wp', array($this, 'prepareMeta'), 1);
	}

	function fillPaths() {

		self::$wp_content_url = is_ssl() ? str_replace('http://', 'https://', get_option('siteurl')) : get_option('siteurl');

		self::$wp_content_url .= '/wp-content';
		self::$wp_content_dir = ABSPATH . 'wp-content';
		self::$wp_plugin_url = self::$wp_content_url . '/plugins';
		self::$wp_plugin_dir = self::$wp_content_dir . '/plugins';
		self::$wpmu_plugin_url = self::$wp_content_url . '/mu-plugins';
		self::$wpmu_plugin_dir = self::$wp_content_dir . '/mu-plugins';

		if ($this->mustUse()) {
			self::$assets_url = self::$wp_content_url . '/mu-plugins/templ33t/';
			self::$assets_dir = self::$wp_content_dir . '/mu-plugins/templ33t/';
		} else {
			self::$assets_url = self::$wp_content_url . '/plugins/templ33t/';
			self::$assets_dir = self::$wp_content_dir . '/plugins/templ33t/';
		}
	}

	function install() {

		global $wpdb;

		$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
		$template_table_name = $the_prefix . "templ33t_templates";
		$block_table_name = $the_prefix . "templ33t_blocks";

		if ($wpdb->get_var("SHOW TABLES LIKE '$template_table_name'") != $template_table_name) {

			$sql = 'CREATE TABLE `' . $template_table_name . '` (
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
			if ($this->multiSite())
				$this->addOption('templ33t_blogs', 1);
		} elseif ($this->multiSite()) {

			$count = $this->getOption('templ33t_blogs');
			$count++;
			$this->updateOption('templ33t_blogs', $count);
		}
	}

	function uninstall() {

		global $wpdb;

		// make sure uninstall only happens from last site
		$uninstall = true;

		if ($this->multiSite()) {
			$count = $this->getOption('templ33t_blogs');
			if (($count - 1) > 0)
				$uninstall = false;
			$count--;
			$this->updateOption('templ33t_blogs', $count);
		}

		if ($uninstall) {

			// set table names
			$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
			$template_table_name = $the_prefix . "templ33t_templates";
			$block_table_name = $the_prefix . "templ33t_blocks";

			// drop blocks table
			$sql_blocks = 'DROP TABLE IF EXISTS `' . $block_table_name . '`;';
			$wpdb->query($sql_blocks);

			// drop templates table
			$sql_templates = 'DROP TABLE IF EXISTS `' . $template_table_name . '`;';
			$wpdb->query($sql_templates);

			// remove db version option
			$this->deleteOption('templ33t_db_version');
			$this->deleteOption('templ33t_map_pub');
			$this->deleteOption('templ33t_map_dev');
			$this->deleteOption('templ33t_map');
			if ($this->multiSite())
				$this->deleteOption('templ33t_blogs');
		}
	}

	function init() {

		global $templ33t, $templ33t_multisite, $templ33t_menu_parent, $templ33t_settings_url, $templ33t_tab_pages, $templ33t_templates, $user_ID, $wpdb, $wp_version;

		// check db version or create tables if no version
		$installed_version = $this->getOption('templ33t_db_version');
		if ($installed_version != self::$db_version) {
			$this->uninstall();
			$this->install();
		}

		// register styles & scripts
		wp_register_style('templ33t_styles', self::$assets_url . 'templ33t.css');
		wp_register_script('templ33t_scripts', self::$assets_url . 'templ33t.js');
		wp_register_script('templ33t_settings_scripts', self::$assets_url . 'templ33t_settings.js');

		// ajax functions
		add_action('wp_ajax_templ33t_block_config', array($this, 'blockConfig'));

		// initialize tabs & content filters
		if (in_array(basename($_SERVER['PHP_SELF']), $this->tab_pages)) {

			// add hooks
			//add_action('posts_selection', 'templ33t_handle_meta', 1);
			add_action('posts_selection', array($this, 'prepareMeta'), 1);
			add_action('admin_print_styles', array($this, 'styles'), 10);
			add_action('admin_head', array($this, 'ieStyles'), 90);
			add_action('admin_print_scripts', array($this, 'scripts'), 10);
			add_filter('the_editor_content', array($this, 'stripComment'), 1);
			add_filter('content_save_pre', array($this, 'addComment'), 10);
			add_action('edit_page_form', array($this, 'tabElements'), 1);

			if (!empty($_POST))
				$this->saveContent();

			// create new page with forced template if from_template passed
			if (in_array(basename($_SERVER['PHP_SELF']), array('page-new.php', 'post-new.php')) && array_key_exists('from_template', $_GET)) {

				$has_title = (array_key_exists('post_title', $_POST) && !empty($_POST['post_title']));
				$has_template = (array_key_exists('page_template', $_POST) && !empty($_POST['page_template']));

				if ($has_title && $has_template) {

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

					if ($new_post > 0) {

						// set template
						update_post_meta($new_post, '_wp_page_template', $_POST['page_template']);

						// redirect to edit page
						$redirect_to = $wp_version{0} < 3 ? 'page.php?action=edit&post=' . $new_post : 'post.php?action=edit&post=' . $new_post;
						wp_redirect($redirect_to);
					}
				}
			}

			// grab theme name
			$theme = get_stylesheet();

			// grab template map
			if (empty($this->map)) {
				$this->map = unserialize($this->getOption('templ33t_map'));
				if (array_key_exists($theme, $this->map)) {
					$this->templates = $this->map[$theme];
					$this->render = true;
				} else {
					$this->templates = array();
					$this->render = false;
				}
			} else {
				if (empty($this->templates))
					$this->render = false;
			}

			// get theme map
			//$this->templates = array_key_exists($theme, $this->map) ? $this->map[$theme] : array();
		} elseif (basename($_SERVER['PHP_SELF']) == $this->menu_parent && $_GET['page'] == 'templ33t_settings') {

			// add styles & scripts
			add_action('admin_print_styles', array($this, 'settingsStyles'), 1);
			add_action('admin_print_scripts', array($this, 'settingsScripts'), 1);

			// handle settings page post
			$this->saveSettings();

			// load plugin styles and scripts for block settings
			if (array_key_exists('subpage', $_GET) && $_GET['subpage'] == 'block') {

				$plug_path = realpath(self::$assets_dir . 'plugs');
				$ignore = array('.', '..', '.svn');
				$plugs = scandir($plug_path);
				foreach ($plugs as $key => $dir) {
					if (in_array($dir, $ignore) || !is_dir($plug_path . '/' . $dir)) {
						unset($plugs[$key]);
					} else {
						$class = Templ33tPluginHandler::load($dir);
						//echo $dir . ' - ' . ($class === false ? 'FALSE' : $class) . '<br/>';
						$obj = new $class;
						if (!($obj instanceOf Templ33tTab)) {
							unset($plugs[$key]);
						}
					}
				}

				$this->load_plugs = $plugs;
			}
		} elseif (basename($_SERVER['PHP_SELF']) == 'media-upload.php') {

			add_filter('media_send_to_editor', array($this, 'interceptMedia'), 15);
		}
	}

	function menu() {

		global $wp_version;
		
		add_submenu_page('themes.php', 'Customize Theme', 'Customize Theme', 'edit_themes', 'templ33t_customize', array($this, 'customize'));
		
		if($wp_version < '3.1.0')
			$this->adminMenu();
		
	}
	
	function adminMenu() {
		
		global $wp_version;
		
		// set menu parent and settings url
		if ($this->multiSite()) {

			if ($wp_version < '3.0.0')
				$this->menu_parent = 'wpmu-admin.php';
			elseif ($wp_version < '3.2.0')
				$this->menu_parent = 'ms-admin.php';
			else
				$this->menu_parent = 'settings.php';
			self::$settings_url = $this->menu_parent . '?page=templ33t_settings';
		} else {

			$this->menu_parent = 'options-general.php';
			self::$settings_url = $this->menu_parent . '?page=templ33t_settings';
		}
		
		add_submenu_page($this->menu_parent, 'Templ33t Settings', 'Templ33t', 'edit_themes', 'templ33t_settings', array($this, 'settings'));
		
	}

	function getOption($key) {

		if ($this->use_site_option)
			return get_site_option($key);
		else
			return get_option($key);
	}

	function addOption($key, $val) {

		if ($this->use_site_option)
			return add_site_option($key, $val);
		else
			return add_option($key, $val);
	}

	function updateOption($key, $val) {

		if ($this->use_site_option)
			return update_site_option($key, $val);
		else
			return update_option($key, $val);
	}

	function deleteOption($key) {

		if ($this->use_site_option)
			return delete_site_option($key);
		else
			return delete_option($key);
	}

	function mustUse() {

		if (strpos(dirname(__FILE__), 'mu-plugins') !== false)
			return true;
		else
			return false;
	}

	function multiSite() {

		return (function_exists('is_multisite') && is_multisite());
	}

	function parseTheme($theme = null) {

		$theme_data = get_theme_data(get_theme_root() . '/' . $theme . '/style.css');

		$theme_dirs = array(get_theme_root() . '/' . $theme . '/');

		if (!empty($theme_data['Template']) && strtolower($theme) != strtolower($theme_data['Template']))
			$theme_dirs[] = get_theme_root() . '/' . $theme_data['Template'] . '/';

		$ignore = array('.', '..', 'style.css');

		$all = array(
			'header.php',
			'footer.php',
			'sidebar.php',
			'comments.php'
		);

		$scanned = array();

		$templates = array();

		foreach ($theme_dirs as $tdir) {

			$files = scandir($tdir);

			foreach ($files as $tfile) {

				if (!in_array($tfile, $ignore) && !in_array($tfile, $scanned) && !is_dir($tfile) && strpos($tfile, '.php') !== false) {

					if ($conf = $this->parseTemplate($tdir . $tfile)) {

						if (!in_array($tfile, $all)) {

							$templates[$tfile] = $conf;
						} else {

							if (!array_key_exists('ALL', $templates)) {
								$templates['ALL'] = array(
									'main' => $conf['main'],
									'description' => $conf['description'],
									'weight' => $conf['weight'],
									'blocks' => array(),
									'options' => array(),
								);
							}

							$templates['ALL']['blocks'] += $conf['blocks'];
							$templates['ALL']['options'] += $conf['options'];
						}
					}
				}

				$scanned[] = $tfile;
			}
		}

		return $templates;
	}

	function parseTemplate($template = null) {

		// catch invalid file
		if (empty($template) || !file_exists($template))
			return false;
		$template_data = implode('', file($template));

		// get blocks
		$blocks = array();
		$block_matches = array();
		preg_match_all('/templ33t\_block\s*\(\s*[\'|\"]([^\']+)[\'|\"]/i', $template_data, $block_matches);
		if (!empty($block_matches[1])) {
			foreach ($block_matches[1] as $slug) {
				$blocks[$slug] = array_merge(
						$this->config_defaults, array(
					'slug' => $slug,
					'label' => ucwords(str_replace('_', ' ', str_replace('-', ' ', $slug)))
						)
				);
			}
		}

		// get options
		$options = array();
		$option_matches = array();
		preg_match_all('/templ33t\_option\s*\(\s*[\'|\"]([^\']+)[\'|\"]/i', $template_data, $option_matches);
		if (!empty($option_matches[1])) {
			foreach ($option_matches[1] as $slug) {
				$options[$slug] = array_merge(
						$this->config_defaults, array(
					'slug' => $slug,
					'label' => ucwords(str_replace('_', ' ', str_replace('-', ' ', $slug)))
						)
				);
			}
		}

		if (!empty($blocks) || !empty($options)) {

			$config = array(
				'main' => 'Page Content',
				'description' => '',
				'blocks' => $blocks,
				'options' => $options,
			);

			return $config;
		} else {

			return false;
		}
	}

	public static function slug($key = null) {

		$slug = strtolower(str_replace(' ', '_', trim(chop(preg_replace('/([^a-z0-9]+)/i', ' ', $key)))));

		return $slug;
	}

	public function prepareMeta() {

		global $wpdb, $post;

		if (!$this->active || !empty($this->meta))
			return;

		// cleanse default page name
		if (empty($post->page_template) || $post->page_template == 'default') {
			$post->page_template = $this->default_template = basename(get_page_template());
		}

		if (array_key_exists($post->page_template, $this->templates)) {

			// grab meta
			$all_meta = $wpdb->get_results(
					$wpdb->prepare('SELECT meta_key, meta_value, meta_id, post_id FROM ' . $wpdb->postmeta . ' WHERE post_id = %d', $post->ID), ARRAY_A
			);

			// filter out unrelated
			foreach ($all_meta as $key => $val) {
				if (strpos($val['meta_key'], 'templ33t_option_') !== false) {
					$slug = str_replace('templ33t_option_', '', $val['meta_key']);
					$this->meta[$slug] = array_merge($this->config_defaults, array('id' => $val['meta_id'], 'value' => $val['meta_value']));
				} elseif (strpos($val['meta_key'], 'templ33t_') !== false) {
					$slug = str_replace('templ33t_', '', $val['meta_key']);
					$this->meta[$slug] = array_merge($this->config_defaults, array('id' => $val['meta_id'], 'value' => $val['meta_value']));
				}
			}

			// add global blocks and options
			if (array_key_exists('ALL', $this->templates)) {
				$this->templates[$post->page_template]['blocks'] += $this->templates['ALL']['blocks'];
				$this->templates[$post->page_template]['options'] += $this->templates['ALL']['options'];
			}

			$x = 0;

			// prepare option meta
			if (!empty($this->templates[$post->page_template]['options'])) {

				foreach ($this->templates[$post->page_template]['options'] as $slug => $opt) {

					// create any non-existent custom fields
					if (!array_key_exists($slug, $this->meta)) {

						if (add_post_meta($post->ID, 'templ33t_option_' . $slug, '', true)) {
							$opt['id'] = $wpdb->insert_id;
							$opt['value'] = '';
						} else {
							$opt['id'] = 'Tl33T_NEW-' . $x;
							$opt['value'] = '';
							$x++;
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
			if (!empty($this->templates[$post->page_template]['blocks'])) {

				foreach ($this->templates[$post->page_template]['blocks'] as $slug => $block) {

					// create any non-existent custom fields
					if (!array_key_exists($slug, $this->meta)) {

						if (add_post_meta($post->ID, 'templ33t_' . $slug, '', true)) {
							$block['id'] = $wpdb->insert_id;
							$block['value'] = '';
						} else {
							$block['id'] = 'TL33T_NEW-' . $x;
							$block['value'] = '';
							$x++;
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

			if (!empty($this->option_objects) || !empty($this->block_objects))
				$this->render = true;
		}
	}

	/**
	 * Enqueue Templ33t stylesheet
	 */
	function styles() {

		wp_enqueue_style('templ33t_styles');

		$load = array();

		// get option styles
		foreach ($this->option_objects as $key => $val) {
			$cname = Templ33tPluginHandler::classify($val->type);
			if ($cname::$load_style)
				$load[] = $val->type;
		}

		// get block styles
		foreach ($this->block_objects as $key => $val) {
			$cname = Templ33tPluginHandler::classify($val->type);
			if ($cname::$load_style)
				$load[] = $val->type;
		}


		// enqueue plugin scripts
		if (!empty($load)) {
			wp_enqueue_style('templ33t_plug_styles', Templ33t::$assets_url . 'templ33t_styles.php?load=' . implode(',', $load));
		}
	}

	/**
	 * Output IE7 specific styles for tabs
	 */
	function ieStyles() {
		?>
		<!--[if IE 7]>
		<style type="text/css">
			div#templ33t_control ul li {
				display: inline;
			}
		</style>
		<![endif]-->
		<?php
	}

	/**
	 * Enqueue Templ33t tab scripts and generate js tab map object
	 * @global object $post
	 */
	function scripts() {

		global $templ33t, $templ33t_plugins, $post;

		// disable auto-save
		if ($this->render) {
			wp_deregister_script('autosave');
			echo '<!-- autosave disabled by templ33t: ' . basename(__FILE__) . ' - ' . __FUNCTION__ . '() -->';
		}

		// enqueue templ33t script file
		wp_enqueue_script('templ33t_scripts');

		// output templ33t js data
		$this->scriptObj();


		$load = array();
		$dependencies = array();

		// get option js
		foreach ($this->option_objects as $key => $val) {
			$cname = Templ33tPluginHandler::classify($val->type);
			if ($cname::$load_js) {
				$load[] = $val->type;
				if (!empty($cname::$dependencies)) {
					foreach ($cname::$dependencies as $d) {
						$dependencies[$c] = $c;
					}
				}
			}
		}

		// get block js
		foreach ($this->block_objects as $key => $val) {
			$cname = Templ33tPluginHandler::classify($val->type);
			if ($cname::$load_js) {
				$load[] = $val->type;
				if (!empty($cname::$dependencies)) {
					foreach ($cname::$dependencies as $d) {
						$dependencies[$d] = $d;
					}
				}
			}
		}


		// enqueue plugin scripts
		if (!empty($load)) {
			wp_enqueue_script('templ33t_plug_scripts', self::$assets_url . 'templ33t_scripts.php?load=' . implode(',', $load), $dependencies);
		} elseif (!empty($dependencies)) {
			foreach ($dependencies as $d) {
				wp_enqueue_script($d);
			}
		}
	}

	/**
	 * Outputs templ33t javascript template map object.
	 *
	 * @global array $templ33t_templates
	 */
	function scriptObj() {

		global $templ33t, $templ33t_templates, $templ33t_plugins, $post;

		echo '<script type="text/javascript">
			/* <![CDATA[ */
			var TL33T_current = { template: "' . $post->page_template . '", default_template: "' . $this->default_template . '", assets: "' . Templ33t::$assets_url . '", media_target: false, media_hook: false };
			';

		// output js template map
		if (!empty($this->templates)) {

			$arr = array();
			foreach ($this->templates as $template => $config) {

				$blocks = array();

				foreach ($config['blocks'] as $key => $val) {

					// grab class name, load unloaded classes
					$cname = Templ33tPluginHandler::load($val['type']);

					$blocks[] = '{label: "' . $val['label'] . '", custom: ' . (!empty($cname) ? ($cname::$custom_panel ? 'true' : 'false') : 'false') . '}';
				}

				// add settings block
				if (!empty($config['options'])) {
					$blocks[] = '{label: "Settings", custom: true}';
				}

				$str = '"' . $template . '": {main: "' . htmlspecialchars($config['main'], ENT_QUOTES) . '", blocks: ['
						. (!empty($blocks) ? implode(', ', $blocks) : '') . ']}';
				$arr[] = $str;
			}

			$defstr = 'var TL33T_def = {' . implode(', ', $arr) . '}; ';
			echo $defstr;
		} else {

			echo 'var TL33T_def = {}; ';
		}

		echo '
			/* ]]> */
			</script>';
	}

	/**
	 * Catch "insert into post" button
	 * @param type $html 
	 */
	function interceptMedia($html = '') {
		?>
		<script type="text/javascript">
					
			var TL33T_win = window.dialogArguments || opener || parent || top;
					
			if(TL33T_win.TL33T_current != undefined && TL33T_win.TL33T_current.media_target !== false && TL33T_win.TL33T_current.media_hook !== false) {
						
				var tid = TL33T_win.TL33T_current.media_target;
				var thook = TL33T_win.TL33T_current.media_hook;
						
				TL33T_win.TL33T_current.media_target = false;
				TL33T_win.TL33T_current.media_hook = false;
						
				thook(tid, '<?php echo addslashes($html); ?>');
						
			} else {
						
						
				if(TL33T_win.TL33T_current != undefined) {
					TL33T_win.TL33T_current.media_target = false;
					TL33T_win.TL33T_current.media_hook = false;
				}
						
				TL33T_win.send_to_editor('<?php echo addslashes($html); ?>');
						
			}
					
			TL33T_win.tb_remove();
					
		</script>
		<?php
	}

	/**
	 * Append templ33t field content to main content in comment form for searchability.
	 * @global array $templ33t_templates
	 * @param string $content
	 * @return string
	 */
	function addComment($content = null) {

		global $templ33t, $templ33t_templates, $wpdb, $post;

		if (array_key_exists('meta', $_POST) && !empty($_POST['meta'])) {

			// get template from post
			$template = array_key_exists('page_template', $_POST) && !empty($_POST['page_template']) ? $_POST['page_template'] : basename(get_page_template());
			if ($template == 'default')
				$template = basename(get_page_template());

			// grab searchable block slugs
			$searchable = array();
			if (array_key_exists($template, $this->templates)) {
				foreach ($this->templates[$template]['blocks'] as $slug => $block) {
					if ($block['searchable'])
						$searchable[] = $slug;
				}
			}

			if (!empty($searchable)) {

				// grab meta ids
				$metadata = array();
				foreach ($_POST['meta'] as $key => $data) {
					$metadata[$data['key']] = array('id' => $key, 'value' => $data['value']);
				}

				// strip existing comment
				$content = $this->stripComment($content);

				// generate apend string
				$append = '<!-- TEMPL33T[ ';
				foreach ($searchable as $slug) {

					if (!array_key_exists($slug, $this->block_objects)) {

						$block = $this->templates[$template]['blocks'][$slug];
						$block['slug'] = $slug;
						$block['id'] = $metadata['templ33t_' . $slug]['id'];
						$block['value'] = $metadata['templ33t_' . $slug]['value'];

						$this->block_objects[$slug] = Templ33tPluginHandler::instantiate($block['type'], $block);
					}

					//$append .= '  ::' . $slug . ':: ' . preg_replace('/(<!--.+?-->)/mi', '', preg_replace('/([\r\n]+)/mi', ' ', $templ33t->block_objects[$slug]->value));
					$append .= '  ::' . $slug . ':: ' . strip_tags(nl2br($this->block_objects[$slug]->value));
				}
				$append .= ' ]END_TEMPL33T -->';

				$content .= '  ' . $append;
			}
		}

		return $content;
	}

	/**
	 * Remove templ33t field comment from end of page content before editing.
	 * @param string $content
	 * @return string
	 */
	function stripComment($content = null) {

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
	function tabElements() {

		global $templ33t, $templ33t_templates, $templ33t_options, $templ33t_meta, $templ33t_render, $post;

		// keep track of selected template
		if (empty($post->page_template) || $post->page_template == 'default') {
			$post->page_template = $this->default_template = basename(get_page_template());
		}

		echo '<input type="hidden" name="templ33t_template" value="' . $post->page_template . '" />';

		if ($this->render) {

			// grab main label
			if (array_key_exists($post->page_template, $this->templates)) {
				$main_label = $this->templates[$post->page_template]['main'];
				$main_desc = $this->templates[$post->page_template]['description'];
			} elseif (array_key_exists('ALL', $this->templates)) {
				$main_label = $this->templates['ALL']['main'];
				$main_desc = $this->templates['ALL']['description'];
			} else {
				$main_label = 'Page Content';
				$main_desc = 'Enter your page content here.';
			}

			// set up item lists
			$tabs = '';
			$descs = '';
			$editors = '';

			// add default tab
			$tabs .= '<li id="templ33t_default" class="selected"><a href="#" rel="default">' . $main_label . '</a><div id="templ33t_main_content"></div></li>';
			$descs .= '<div class="templ33t_description templ33t_desc_default"><p>' . $main_desc . '</p></div>';


			if (!empty($this->block_objects)) {
				//foreach($templ33t_templates[$post->page_template]['blocks'] as $slug => $block) {
				foreach ($this->block_objects as $slug => $block) {

					//$cname = Templ33tPluginHandler::load($block['type']);
					//$instance = $block['instance'];
					// get class name
					$cname = Templ33tPluginHandler::load($block->type);

					$tabs .= '<li><a href="#" rel="' . $block->id . '">' . $block->label . '</a></li>';
					$descs .= '<div class="templ33t_description templ33t_desc_' . $block->id . ' templ33t_hidden"><p>' . $block->description . '</p></div>';

					if ($cname::$custom_panel) {
						$editors .= '<div id="templ33t_editor_' . $block->id . '" class="templ33t_editor" style="display: none;">';
						if ($block instanceOf Templ33tTab)
							$editors .= $block->displayPanel();
						else
							$editors .= '<div id="templ33t_editor_' . $block->id . '" class="templ33t_editor templ33t_hidden"><input type="hidden" name="meta[' . $block->id . '][key]" value="templ33t_' . $block->slug . '" /><textarea id="templ33t_val_' . $block->id . '" name="meta[' . $block->id . '][value]">' . $block->value . '</textarea></div>';
						$editors .= '</div>';
					} else {
						$editors .= '<div id="templ33t_editor_' . $block->id . '" class="templ33t_editor templ33t_hidden"><input type="hidden" name="meta[' . $block->id . '][key]" value="templ33t_' . $block->slug . '" /><textarea id="templ33t_val_' . $block->id . '" name="meta[' . $block->id . '][value]">' . $block->value . '</textarea></div>';
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
			if (!empty($this->option_objects)) {

				$tabs .= '<li class="templ33t_settings"><a href="#" rel="settings">Settings</a></li>';
				$descs .= '<div class="templ33t_description templ33t_desc_settings templ33t_hidden"><p>These are the settings available for this template.</p></div>';
				$editors .= '<div id="templ33t_editor_settings" class="templ33t_editor postbox" style="display: none;"><div class="inside"><p class="templ33t_description">These are the settings available for this page.</p><table border="0">';
				foreach ($this->option_objects as $slug => $option) {
					if ($option instanceOf Templ33tOption)
						$editors .= $option->displayOption();
					else
						$editors .= '<tr><td>' . $option->label . '</td><td><input type="hidden" name="meta[' . $option->id . '][key]" value="templ33t_option_' . $option->slug . '" /><input type="text" name="meta[' . $option->id . '][value]" value="' . $option->value . '" /></td></tr>';
				}
				$editors .= '</table></div></div>';
			}

			// output tab bar & descriptions
			echo '<div id="templ33t_control" style="display: none;"><a href="#" id="templ33t_prev" onclick="return templ33t_nav_prev();"><</a><a href="#" id="templ33t_next" onclick="return templ33t_nav_next();">></a><ul>';
			echo $tabs;
			echo '</ul></div> <div id="templ33t_descriptions" style="display: none;">';
			echo $descs;
			echo '</div> <div id="templ33t_editors" class="templ33t_editors" style="display: none;">';
			echo $editors;
			echo '</div>';

			// make sure current template is known in case of switch
		}
	}

	function saveContent() {

		global $wpdb;

		if (array_key_exists($_POST['templ33t_template'], $this->templates)) {

			if (array_key_exists('meta', $_POST) && !empty($_POST['meta'])) {

				foreach ($_POST['meta'] as $id => $data) {

					$slug = str_replace('templ33t_', '', str_replace('templ33t_option_', '', $data['key']));

					// handle blocks
					if (array_key_exists($slug, $this->templates[$_POST['templ33t_template']]['blocks'])) {

						// create post meta if new
						if (!is_numeric($id)) {
							$split = explode('-', $id);
							if ($split[0] == 'TL33T_NEW') {
								if (add_post_meta($_POST['post_ID'], 'templ33t_' . $slug, $data['value'], true)) {
									unset($_POST['meta'][$id]);
									$id = $wpdb->insert_id;
								}
							}
						}

						$block = $this->templates[$_POST['templ33t_template']]['blocks'][$slug];
						$block['slug'] = $slug;
						$block['id'] = $id;
						$block['value'] = $data['value'];

						$cname = Templ33tPluginHandler::load($block['type']);

						if ($cname::$custom_post) {

							$instance = Templ33tPluginHandler::instantiate($block['type'], $block);

							$instance->handlePost();

							$_POST['meta'][$instance->id] = array(
								'key' => 'templ33t_' . $instance->slug,
								'value' => $instance->value,
							);

							$this->block_objects[$slug] = $instance;
						}
					}

					// handle options
					if (array_key_exists($slug, $this->templates[$_POST['templ33t_template']]['options'])) {

						// create post meta if new
						if (!is_numeric($id)) {
							$split = explode('-', $id);
							if ($split[0] == 'TL33T_NEW') {
								if (add_post_meta($_POST['post_ID'], 'templ33t_option_' . $slug, $data['value'], true)) {
									unset($_POST['meta'][$id]);
									$id = $wpdb->insert_id;
								}
							}
						}

						$option = $this->templates[$_POST['templ33t_template']]['options'][$slug];
						$option['slug'] = $slug;
						$option['id'] = $id;
						$option['value'] = $data['value'];

						$cname = Templ33tPluginHandler::load($option['type']);

						if ($cname::$custom_post) {

							$instance = Templ33tPluginHandler::instantiate($option['type'], $option);

							$instance->handlePost();

							$_POST['meta'][$instance->id] = array(
								'key' => 'templ33t_option_' . $instance->slug,
								'value' => $instance->value,
							);

							$this->option_objects[$slug] = $instance;
						}
					}
				}
			}

			if (array_key_exists('templ33t_meta', $_POST)) {

				foreach ($_POST['templ33t_meta'] as $slug => $data) {

					// handle blocks
					if (array_key_exists($slug, $this->templates[$_POST['templ33t_template']]['blocks'])) {

						$block = $this->templates[$_POST['templ33t_template']]['blocks'][$slug];
						$block['slug'] = $slug;
						$block['id'] = $data['id'];
						$block['value'] = $data['value'];

						$instance = Templ33tPluginHandler::instantiate($block['type'], $block);
						$instance->handlePost();
						$_POST['meta'][$instance->id] = array(
							'key' => 'templ33t_' . $instance->slug,
							'value' => $instance->value,
						);

						$this->block_objects[$slug] = $instance;
					}

					// handle options
					if (array_key_exists($slug, $this->templates[$_POST['templ33t_template']]['options'])) {

						$option = $this->templates[$_POST['templ33t_template']]['options'][$slug];
						$option['slug'] = $slug;
						$option['id'] = $data['id'];
						$option['value'] = $data['value'];

						$instance = Templ33tPluginHandler::instantiate($option['type'], $option);
						$instance->handlePost();
						$_POST['meta'][$instance->id] = array(
							'key' => 'templ33t_option_' . $instance->slug,
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
	function settingsStyles() {

		wp_enqueue_style('templ33t_styles');
		wp_enqueue_style('thickbox');

		// load plugin styles and scripts for block settings
		if (array_key_exists('subpage', $_GET) && $_GET['subpage'] == 'block') {

			wp_enqueue_style('templ33t_plug_styles', self::$assets_url . 'templ33t_styles.php?load=' . implode(',', $this->load_plugs));
		}
	}

	/**
	 * Enqueue js for settings page
	 */
	function settingsScripts() {

		//wp_deregister_script('jquery');
		//wp_register_script('jquery', (is_ssl() ? 'https://' : 'http://') . 'code.jquery.com/jquery-1.4.2.min.js');
		wp_enqueue_script('jquery');
		wp_enqueue_script('thickbox');
		wp_enqueue_script('templ33t_settings_scripts', null, array('jquery'));

		// load plugin styles and scripts for block settings
		if (array_key_exists('subpage', $_GET) && $_GET['subpage'] == 'block' && !empty($this->load_plugs)) {

			$enqueue = array();
			foreach ($this->load_plugs as $plug) {
				$class = Templ33tPluginHandler::classify($plug);
				if (!empty($class::$dependencies)) {
					foreach ($class::$dependencies as $script) {
						wp_enqueue_script($script);
					}
				}
			}

			wp_enqueue_script('templ33t_plug_scripts', self::$assets_url . 'templ33t_scripts.php?load=' . implode(',', $this->load_plugs));
		}

		echo '<script type="text/javascript">var TL33T_current = {assets: \'' . self::$assets_url . '\'};</script>';
	}

	/**
	 * Catch and act upon settings post & actions
	 * @global object $wpdb
	 */
	function saveSettings() {

		global $templ33t, $templ33t_menu_parent, $templ33t_settings_url, $wpdb, $wp_content_dir;

		$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
		$template_table_name = $the_prefix . 'templ33t_templates';
		$block_table_name = $the_prefix . 'templ33t_blocks';

		// catch settings via post
		if (!empty($_POST)) {

			// add template to theme
			if (array_key_exists('templ33t_new_template', $_POST)) {

				// required (non-empty) fields array
				$required = array(
					'templ33t_theme',
					'templ33t_template',
						//'templ33t_main_label',
				);

				$errors = array();

				// check and sterilize
				foreach ($required as $field) {
					if (!array_key_exists($field, $_POST) || empty($_POST[$field])) {
						$errors[] = str_replace('templ33t_', '', $field);
					} else {
						$_POST[$field] = htmlspecialchars($_POST[$field], ENT_QUOTES);
					}
				}

				$tfile = $wp_content_dir . '/themes/' . $_POST['templ33t_theme'] . '/' . $_POST['templ33t_template'];

				if (!file_exists($tfile))
					$errors[] = 'notemp';

				if (!empty($errors)) {

					// redirect with errors
					$redirect = self::$settings_url;
					if (array_key_exists('templ33t_theme', $_POST))
						$redirect .= '&theme=' . $_POST['templ33t_theme'];
					elseif (array_key_exists('theme', $_GET))
						$redirect .= '&theme=' . $_GET['theme'];
					$redirect .= '&error=' . implode('|', $errors);
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
					$check = $wpdb->get_row('SELECT * FROM `' . $template_table_name . '` WHERE `theme` = "' . $i_arr['theme'] . '" AND `template` = "' . $i_arr['template'] . '" LIMIT 1', ARRAY_A);

					if (empty($check)) {

						// insert record
						$insert = $wpdb->insert(
								$template_table_name, $i_arr
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
						$redirect = self::$settings_url . '&theme=' . $i_arr['theme'];
						wp_redirect($redirect);
					} else {

						// redirect with error
						$redirect = self::$settings_url . '&theme=' . $i_arr['theme'] . '&error=duptemp';
						wp_redirect($redirect);
					}
				}

				// add block to template
			} elseif (array_key_exists('templ33t_new_block', $_POST)) {

				// required (non-empty) fields array
				$required = array(
					'templ33t_theme',
					'templ33t_template',
					'templ33t_block'
				);

				$errors = array();

				// check and sterilize
				foreach ($required as $field) {
					if (!array_key_exists($field, $_POST) || empty($_POST[$field])) {
						$errors[] = str_replace('templ33t_', '', $field);
					} else {
						$_POST[$field] = htmlspecialchars($_POST[$field], ENT_QUOTES);
					}
				}

				if (!empty($errors)) {

					// redirect with errors
					$redirect = self::$settings_url;
					if (array_key_exists('templ33t_theme', $_POST))
						$redirect .= '&theme=' . $_POST['templ33t_theme'];
					$redirect .= '&error=' . implode('|', $errors);
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
					if ($i_arr['template_id'] == 'ALL')
						unset($i_arr['template_id']);

					// generate slug
					$i_arr['block_slug'] = strtolower(str_replace(' ', '_', trim(chop(preg_replace('/([^a-z0-9]+)/i', ' ', $_POST['templ33t_block'])))));

					// set where conditions for template_id
					$t_where = '`template_id` IS NULL';
					if (array_key_exists('template_id', $i_arr))
						$t_where = '(' . $t_where . ' OR `template_id` = "' . $i_arr['template_id'] . '")';

					// check for duplicates
					$check = $wpdb->get_row(
							'SELECT * FROM `' . $block_table_name . '`
						WHERE
							`theme` = "' . $i_arr['theme'] . '"
							AND ' . $t_where . '
							AND `block_slug` = "' . $i_arr['block_slug'] . '"
						LIMIT 1', ARRAY_A
					);

					if (empty($check)) {

						// save block
						$insert = $wpdb->insert(
								$block_table_name, $i_arr
						);

						// update map dev version
						$t_dev = $this->getOption('templ33t_map_dev');
						$t_dev++;
						$this->updateOption('templ33t_map_dev', $t_dev);


						// return to settings page
						$redirect = self::$settings_url . '&theme=' . $_POST['templ33t_theme'];
						wp_redirect($redirect);
					} else {

						// redirect with error
						$redirect = self::$settings_url . '&error=dupblock';
						wp_redirect($redirect);
					}
				}
			}

			if (array_key_exists('action', $_POST)) {

				switch ($_POST['action']) {

					case 'templ33t_block_config':

						if (empty($_POST['templ33t_block_config'])) {
							die('No block data passed.');
						}

						$pub = $this->getOption('templ33t_map_pub');
						$dev = $this->getOption('templ33t_map_dev');

						$block = $_POST['templ33t_block_config'];

						$tid = $block['tid'];
						unset($block['tid']);

						$theme = $block['theme'];
						unset($block['theme']);

						// grab original template & block
						$template = $wpdb->get_row(
								'SELECT `templ33t_template_id`, `theme`, `template`, `config`
									FROM `' . self::$templates_table . '`
									WHERE `templ33t_template_id` = "' . $tid . '"', ARRAY_A
						);

						$template['config'] = unserialize($template['config']);

						$oblock = array();

						foreach ($template['config']['blocks'] as $key => $val) {
							if ($key == $_GET['block']) {
								$oblock = $val;
								break;
							}
						}
							
						$new = array_merge($this->config_defaults, $oblock, $block);
						
						$template['config']['blocks'][$new['slug']] = $new;
						
						
						// reorder
						$newblocks = array();
						//$weights = array('main' => $main['weight'].' aaa');
						$weights = array();
						foreach ($template['config']['blocks'] as $slug => $block) {
							$weights[$slug] = (array_key_exists('weight', $block) ? (int) $block['weight'] : 0) . ' ' . strtolower($slug);
						}
						asort($weights);
						foreach ($weights as $slug => $val) {
							$newblocks[$slug] = $template['config']['blocks'][$slug];
						}

						$template['config']['blocks'] = $newblocks;

						//print_r($template);
						//die();

						$wpdb->update(self::$templates_table, array('config' => serialize($template['config'])), array('templ33t_template_id' => $tid));

						if ($oblock != $new && $dev <= $pub) {
							$dev = $pub + 1;
							$pub = $this->updateOption('templ33t_map_dev', $dev);
						}

						// return to settings page
						$redirect = self::$settings_url . '&theme=' . $theme;
						wp_redirect($redirect);

						break;

					case 'templ33t_main_block_config':

						if (empty($_POST['templ33t_block_config'])) {
							die('No block data passed.');
						}

						$pub = $this->getOption('templ33t_map_pub');
						$dev = $this->getOption('templ33t_map_dev');

						$main = $_POST['templ33t_block_config'];

						$tid = $main['tid'];
						unset($main['tid']);

						$theme = $main['theme'];
						unset($main['theme']);

						// grab original template & block
						$template = $wpdb->get_row(
								'SELECT `templ33t_template_id`, `template`, `config`
									FROM `' . self::$templates_table . '`
									WHERE `templ33t_template_id` = "' . $tid . '"', ARRAY_A
						);

						$template['config'] = unserialize($template['config']);

						$old = $template['config'];

						$template['config']['main'] = $main['main'];
						$template['config']['description'] = $main['description'];
						$template['config']['weight'] = $main['weight'];

						$wpdb->update(self::$templates_table, array('config' => serialize($template['config'])), array('templ33t_template_id' => $tid));

						if (serialize($old) != $template['config'] && $dev <= $pub) {
							$dev = $pub + 1;
							$pub = $this->updateOption('templ33t_map_dev', $dev);
						}

						// return to settings page
						$redirect = self::$settings_url . '&theme=' . $theme;
						wp_redirect($redirect);

						break;
				}
			}
		}

		// catch actions sent via GET
		if (isset($_GET['t_action'])) {

			switch ($_GET['t_action']) {

				// scan theme templates for configurations
				case 'scan':

					if (!empty($_GET['theme'])) {

						$theme = htmlspecialchars($_GET['theme'], ENT_QUOTES);

						// get old config
						$compare = array();
						$old = $wpdb->get_results('SELECT * FROM `' . $template_table_name . '` WHERE `theme` = "' . $theme . '"', ARRAY_A);
						foreach ($old as $key => $val) {
							$compare[$val['template']] = unserialize($val['config']);
						}

						// get templates
						$templates = $this->parseTheme($theme);

						if ($compare != $templates) {

							// delete previous records
							$wpdb->query('DELETE FROM `' . $template_table_name . '` WHERE `theme` = "' . $theme . '"');

							// update map dev version
							$t_dev = $this->getOption('templ33t_map_dev');
							$t_dev++;
							$this->updateOption('templ33t_map_dev', $t_dev);

							if (!empty($templates)) {

								// insert new data
								foreach ($templates as $key => $val) {
									$wpdb->insert(
											$template_table_name, array(
										'theme' => $theme,
										'template' => $key,
										'config' => serialize($val)
											)
									);
								}

								// return to settings page
								$redirect = self::$settings_url . '&theme=' . $theme;
								wp_redirect($redirect);
							} else {

								// return to settings page
								$redirect = self::$settings_url . '&theme=' . $theme . '&error=nothemeconfig';
								wp_redirect($redirect);
							}
						} else {

							// return to settings page
							$redirect = self::$settings_url . '&theme=' . $theme . '&error=nochange';
							wp_redirect($redirect);
						}
					} else {

						// return to settings page
						$redirect = self::$settings_url . '&error=notheme';
						wp_redirect($redirect);
					}


					break;

				// rescan template config
				case 'rescan':

					// grab template from db
					$temp = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $template_table_name . '` WHERE `templ33t_template_id` = %d', $_GET['tid']), ARRAY_A);

					if (!empty($temp)) {

						$tfile = self::$wp_content_dir . '/themes/' . $temp['theme'] . '/' . $temp['template'];

						if (file_exists($tfile)) {

							// grab templ33t config
							$config = $this->parseTemplate($tfile);

							if (!empty($config)) {

								if (unserialize($temp['config']) != $config) {

									// save changes
									$wpdb->update($template_table_name, array('config' => serialize($config)), array('templ33t_template_id' => $temp['templ33t_template_id']));

									// update map dev version
									$t_dev = $this->getOption('templ33t_map_dev');
									$t_dev++;
									$this->updateOption('templ33t_map_dev', $t_dev);

									// return to settings page
									$redirect = self::$settings_url . '&theme=' . $temp['theme'];
									wp_redirect($redirect);
								} else {

									// return to settings page
									$redirect = self::$settings_url . '&theme=' . $temp['theme'] . '&error=nochange';
									wp_redirect($redirect);
								}
							} else {

								// return to settings page
								$redirect = self::$settings_url . '&theme=' . $temp['theme'] . '&error=noconfig';
								wp_redirect($redirect);
							}
						} else {

							// return error if non-existent
							wp_redirect(self::$settings_url . '&theme=' . $_GET['theme'] . '&error=notemp');
						}
					} else {

						// return error if non-existent
						wp_redirect(self::$settings_url . '&theme=' . $_GET['theme'] . '&error=notemp');
					}

					break;

				// delete template
				case 'deltemp':

					// grab template
					$row = $wpdb->get_row('SELECT * FROM `' . $template_table_name . '` WHERE `templ33t_template_id` = "' . htmlspecialchars($_GET['tid'], ENT_QUOTES) . '"', ARRAY_A);

					if (!empty($row)) {

						// delete if exists
						$sql_temp = 'DELETE FROM `' . $template_table_name . '` WHERE `templ33t_template_id` = ' . $row['templ33t_template_id'] . ' LIMIT 1';
						$wpdb->query($sql_temp);

						$sql_block = 'DELETE FROM `' . $block_table_name . '` WHERE `template_id` = ' . $row['templ33t_template_id'];
						$wpdb->query($sql_block);

						// update map dev version
						$t_dev = $this->getOption('templ33t_map_dev');
						$t_dev++;
						$this->updateOption('templ33t_map_dev', $t_dev);

						wp_redirect(self::$settings_url . '&theme=' . $row['theme']);
					} else {

						// return error if non-existent
						wp_redirect(self::$settings_url . '&theme=' . $_GET['theme'] . '&error=notemp');
					}

					break;

				// delete block
				case 'delblock':

					// grab block
					$row = $wpdb->get_row('SELECT * FROM `' . $block_table_name . '` WHERE `templ33t_block_id` = "' . htmlspecialchars($_GET['bid'], ENT_QUOTES) . '"', ARRAY_A);

					if (!empty($row)) {

						// delete if exists
						$sql = 'DELETE FROM `' . $block_table_name . '` WHERE `templ33t_block_id` = ' . $row['templ33t_block_id'] . ' LIMIT 1';
						$wpdb->query($sql);

						// update map dev version
						$t_dev = $this->getOption('templ33t_map_dev');
						$t_dev++;
						$this->updateOption('templ33t_map_dev', $t_dev);


						wp_redirect(self::$settings_url . '&theme=' . $row['theme']);
					} else {

						// return error if non-existent
						wp_redirect(self::$settings_url . '&theme=' . $_GET['theme'] . '&error=noblock');
					}

					break;

				// publish latest template map
				case 'publish':

					// get current configuration versions
					$pub = $this->getOption('templ33t_map_pub');
					$dev = $this->getOption('templ33t_map_dev');

					//if($pub < $dev) {


					$template_sql = 'SELECT `theme`, `template`, `config` FROM `' . $template_table_name . '`';

					// grab templates from the database
					$templates = $wpdb->get_results($template_sql, ARRAY_A);

					$this->map = array();

					// map templates and blocks
					if (!empty($templates)) {
						foreach ($templates as $tmp) {

							// add theme to map
							if (!array_key_exists($tmp['theme'], $this->map))
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

					wp_redirect(self::$settings_url . '&theme=' . $_GET['theme']);

					//} else {
					//	wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=nopub');
					//}
					break;

				case 'reset':

					$pub = $this->getOption('templ33t_map_pub');
					$dev = $this->getOption('templ33t_map_dev');
					$map = unserialize($this->getOption('templ33t_map'));

					$template_sql = 'DELETE FROM `' . $template_table_name . '`';

					$wpdb->query($template_sql);

					$insert_sql = 'INSERT INTO `' . $template_table_name . '` (`theme`, `template`, `config`) VALUES ';

					$records = array();

					if (!empty($map)) {
						foreach ($map as $theme => $templates) {
							foreach ($templates as $template => $config) {
								$records[] = $wpdb->prepare('(%s, %s, %s)', $theme, $template, serialize($config));
							}
						}
					}

					$wpdb->query($insert_sql . ' ' . implode(', ', $records));

					$this->updateOption('templ33t_map_dev', $pub);

					wp_redirect(self::$settings_url . '&theme=' . $_GET['theme']);

					break;

				// return error on invalid action
				default:

					wp_redirect(self::$settings_url . '&theme=' . $_GET['theme'] . '&error=noaction');
					break;
			}
		}
	}

	/**
	 * Transform templ33t fields comment into appended text for search results
	 * @param string $content
	 * @return string
	 */
	function searchResults($content = null) {

		if (is_search()) {

			$content = preg_replace('/(::[A-Z0-9\_\-]+::)/i', ' ... ', preg_replace('/(<!--\ TEMPL33T\[)(.+?)(\]END\_TEMPL33T\ -->)/mi', '$2', $content));
		} elseif (is_page()) {

			$content = $this->stripComment($content);
		}

		return $content;
	}

	/**
	 * Output Templ33t settings page.
	 * @global object $wpdb
	 * @global array $templ33t_errors
	 */
	function settings() {

		global $wpdb;

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
		if (isset($_GET['theme']) && !empty($_GET['theme'])) {
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

		// parse error message
		$error = null;
		if (isset($_GET['error'])) {
			if (strpos($_GET['error'], '|') !== false) {
				$error = explode('|', $_GET['error']);
				foreach ($error as $key => $err) {
					$error[$key] = $this->errors[$err];
				}
				$error = implode('<br/>', $error);
			} else {
				$error = $this->errors[$_GET['error']];
			}
		}

		$_GET['subpage'] = array_key_exists('subpage', $_GET) ? $_GET['subpage'] : 'theme';

		$cd = dirname(__FILE__);

		$pages = array(
			'theme' => '/inc/settings.php',
			'template' => '/inc/template_settings.php',
			'main_block' => '/inc/main_block_settings.php',
			'block' => '/inc/block_settings.php',
		);

		if (array_key_exists(strtolower($_GET['subpage']), $pages)) {

			include($cd . $pages[strtolower($_GET['subpage'])]);
		} else {

			include($cd . current($pages));
		}
	}

	function blockConfig() {

		global $wpdb;

		if (array_key_exists('templ33t_block_config', $_POST)) {

			$block = $_POST['templ33t_block_config'];

			unset($block['tid']);
			unset($block['theme']);

			if (!array_key_exists('default', $block)) {
				$block['default'] = '';
			}

			$resp = new StdClass;

			// instantiate plugin
			$obj = Templ33tPluginHandler::instantiate($block['type'], $block);
			$obj->init();

			echo $obj->displayConfig();
		} else {

			echo '<tr><td colspan="2"><em>No block configuration passed.</em></td></tr>';
		}

		die();
	}

	function customize() {
		
		$themes = get_themes();
		
		$theme = get_stylesheet();
		
		if(array_key_exists($theme, $this->map) && array_key_exists($theme, $themes)) {
			echo '<pre>'.print_r($this->map[$theme], true).'</pre>';
			echo '<pre>'.print_r($themes[$theme], true).'</pre>';
		}
		
	}

}
?>