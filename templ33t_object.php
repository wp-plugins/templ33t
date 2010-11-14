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

	var $config_defaults = array(
		'slug' => '',
		'type' => 'editor',
		'description' => '',
		'config' => '',
		'optional' => false,
		'bindable' => false,
		'searchable' => false,
	);

	var $plugin_attributes = array(
		'main',
		'optional',
		'bindable',
		'searchable',
	);

	var $active = true;
	var $render = false;

	var $use_site_option;

	var $map = array();
	var $meta = array();
	var $block_objects = array();
	var $option_objects = array();
	
	function __construct() {

		// generate paths
		if(empty(self::$wp_content_url)) $this->fillPaths();

		// check existence of site options
		$this->use_site_option = function_exists('add_site_option');

		// grab theme name
		$theme = get_template();

		// get theme map
		$map = unserialize($this->getOption('templ33t_map'));
		if(array_key_exists($theme, $map)) {
			$this->map = $map[$theme];
		} else {
			$this->active = false;
		}

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

		//die(print_r($config, true));

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

	public function prepareMeta() {

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

}

?>
