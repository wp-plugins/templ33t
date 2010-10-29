<?php

class Templ33t {
	
	var $version = '0.2';

	static $reserved_attributes = array(
		'main',
		'optional',
		'bindable',
	);

	static $area_defaults = array(
		'type' => 'editor',
		'description' => '',
		'optional' => false,
		'bindable' => false,
	);
	
	function __construct() {

		

	}

	function parseTheme($theme = null) {



	}

	function parseTemplate($template = null) {

		// catch invalid file
		if(empty($template) || !file_exists($template)) return false;

		// grab config data from file
		$template_data = implode( '', file( $template ));
		$config = '';
		if ( preg_match( '|Templ33t Areas:(.*)$|mi', $template_data, $config ) )
			$config = _cleanup_header_comment($config[1]);

		// grab description data
		$descriptions = array();
		if(preg_match_all('|Templ33t Description (.*\:.*)$|mi', $template_data, $descriptions))
			$descriptions = $descriptions[1];


		// return parsed config
		return $this->parseConfig($config, $descriptions);

	}

	function parseConfig($data = null, $descriptions = array()) {

		if(empty($data)) return array();

		$data = explode(',', $data);
		$config = array(
			'main' => 'Page Content',
			'description' => '',
			'blocks' => array(),
		);

		if(!empty($descriptions))
			$descriptions = $this->parseDescriptions($descriptions);

		foreach($data as $key => $val) {

			$title = trim(chop($val));
			$area = array();

			// check for attributes
			if(preg_match('/\[(.+)\]$/i', $val, $attrstr)) {
				
				$attributes = explode('|', $attrstr[1]);

				$title = trim(chop(substr($title, 0, (strlen($title) - (strlen($attrstr[1])+2)))));
				$slug = Templ33t::slug($title);

				foreach($attributes as $attr) {
					if(in_array($attr, self::$reserved_attributes)) {
						if($attr == 'main') {
							$config['main'] = $title;
							$config['description'] = array_key_exists($slug, $descriptions) ? $descriptions[$slug] : '';
						} else {
							$area[$attr] = true;
							$area['description'] = array_key_exists($slug, $descriptions) ? $descriptions[$slug] : '';
						}
					} else {
						$area['type'] = $attr;
						$area['description'] = array_key_exists($slug, $descriptions) ? $descriptions[$slug] : '';
					}
				}

			} else {

				$slug = Templ33t::slug($title);
				$area['description'] = array_key_exists($slug, $descriptions) ? $descriptions[$slug] : '';

			}

			$area['title'] = $title;

			if($config['main'] != $title) {

				$config['blocks'][$slug] = array_merge(self::$area_defaults, $area);
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
