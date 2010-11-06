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

	var $use_site_option;

	var $area_defaults = array(
		'type' => 'editor',
		'description' => '',
		'optional' => false,
		'bindable' => false,
	);

	var $plugin_attributes = array(
		'main',
		'optional',
		'bindable',
	);
	
	function __construct() {

		if(empty(self::$wp_content_url)) $this->fillPaths();

		$this->use_site_option = function_exists('add_site_option');

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

	function mustUse() {

		if(strpos(dirname(__FILE__), 'mu-plugins') !== false) return true;
		else return false;

	}

	function multiSite() {

		return (function_exists('is_multisite') && is_multisite());

	}

	function parseTheme($theme = null) {



	}

	function parseTemplate($template = null) {

		// catch invalid file
		if(empty($template) || !file_exists($template)) return false;
		$template_data = implode( '', file( $template ));
		
		// parse retrieved data
		$config = $this->parseConfig($template_data);
		$config['options'] = $this->parseOptions($template_data);
		
		// return template configuration
		return $config;

	}

	function parseOptions($template_str = null) {

		// grab options config string from file
		$data = '';
		if ( preg_match( '|Templ33t Options:(.*)$|mi', $template_str, $data ) )
			$data = _cleanup_header_comment($data[1]);

		if(empty($data)) return array();

		$data = explode(',', $data);
		$options = array();

		foreach($data as $key => $val) {

			$title = trim(chop($val));
			$area = array();

			// check for attributes
			if(preg_match('/\[(.+)\]$/i', $title, $attrstr)) {

				$title = trim(chop(substr($title, 0, (strlen($title) - (strlen($attrstr[1])+2)))));
				$type = $attrstr[1];

				if(strpos($type, '=') !== false) {
					$pieces = explode('=', $type);
					$type = $pieces[0];
					$config = $pieces[1];
				} else {
					$config = null;
				}

			} else {

				$type = 'text';
				$config = null;

			}

			$slug = $this->slug($title);

			$options[$slug] = array(
				'label' => $title,
				'slug' => $slug,
				'type' => $type,
				'config' => $config
			);

		}

		return $options;

	}

	function parseConfig($template_str = null) {

		// grab block config string from file
		$blocks = '';
		if ( preg_match( '|Templ33t Blocks:(.*)$|mi', $template_str, $blocks ) )
			$blocks = _cleanup_header_comment($blocks[1]);

		// catch empty configuration (not a templ33t template)
		if(empty($blocks)) return array();

		// grab description data
		$descriptions = array();
		if(preg_match_all('|Templ33t Description (.*\:.*)$|mi', $template_str, $descriptions))
			$descriptions = $descriptions[1];

		// set up config array
		$blocks = explode(',', $blocks);
		$config = array(
			'main' => 'Page Content',
			'description' => '',
			'blocks' => array(),
		);

		if(!empty($descriptions))
			$descriptions = $this->parseDescriptions($descriptions);

		foreach($blocks as $key => $val) {

			$title = trim(chop($val));
			$area = array();

			// check for attributes
			if(preg_match('/\[(.+)\]$/i', $val, $attrstr)) {
				
				$attributes = explode('|', $attrstr[1]);

				$title = trim(chop(substr($title, 0, (strlen($title) - (strlen($attrstr[1])+2)))));
				$slug = Templ33t::slug($title);

				foreach($attributes as $attr) {
					if(in_array($attr, $this->plugin_attributes)) {
						if($attr == 'main') {
							$config['main'] = $title;
							$config['description'] = array_key_exists($slug, $descriptions) ? $descriptions[$slug] : '';
						} else {
							$area[$attr] = true;
							$area['description'] = array_key_exists($slug, $descriptions) ? $descriptions[$slug] : '';
						}
					} else {
						if(strpos($attr, '=') !== false) {
							$pieces = explode('=', $attr);
							$area['type'] = $prices[0];
							$area['config'] = $prices[1];
						} else {
							$area['type'] = $attr;
							$area['config'] = null;
						}
						$area['description'] = array_key_exists($slug, $descriptions) ? $descriptions[$slug] : '';
					}
				}

			} else {

				$slug = Templ33t::slug($title);
				$area['description'] = array_key_exists($slug, $descriptions) ? $descriptions[$slug] : '';

			}

			$area['label'] = $title;
			$area['slug'] = $slug;

			if($config['main'] != $title) {

				$config['blocks'][$slug] = array_merge($this->area_defaults, $area);
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

}

?>
