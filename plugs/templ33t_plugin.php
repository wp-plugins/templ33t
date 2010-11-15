<?php

abstract class Templ33tPlugin {

	/**
	 * Post meta ID
	 * @var integer
	 */
	var $id;

	/**
	 * Post meta value
	 * @var string
	 */
	var $value;

	/**
	 * Post meta key
	 * @var string
	 */
	var $slug;

	/**
	 * Tab/Option label (human readable version of slug)
	 * @var string
	 */
	var $label;

	/**
	 * Tab/Option description text
	 * @var string
	 */
	var $description;

	/**
	 * Tab/Option default value
	 * @var string
	 */
	var $default;

	/**
	 * Block optional (block wrappers will not be displayed if value is empty)
	 * @var boolean
	 */
	var $optional = true;

	/**
	 * Block is used as a shortcode
	 * @var boolean
	 */
	var $bindable = false;

	/**
	 * Block content is made searchable
	 * @var boolean
	 */
	var $searchable = false;

	/**
	 * Configuration string passed by template
	 * @var string
	 */
	var $config;

	/**
	 * Plugin type name
	 * @var string
	 */
	var $type;
	
	static $custom_panel = false;
	static $custom_post = false;
	static $load_js = false;
	static $dependencies = array();
	static $load_styles = false;
	static $res_loaded = false;
	
	public function __set($key, $val) {
		return $val;
	}

	public function __get($key) {
		return null;
	}

	public function configure($config = null) {

		if(!empty($config)) {

			if(is_array($config)) {

				foreach($config as $key => $val) {

					$this->$key = $val; 

				}

				$this->parseConfig();

			} else {

				$this->parseConfig($config);

			}

		} else {

			$this->parseConfig();

		}

	}

	public function parseConfig($config = null) {

		if(!empty($config)) $this->config = $config;

	}

	public function init() {
		
	}

	public function handlePost() {
		
	}

	public function js($return = false, $wrap = true) {
		
	}

	public function styles($return = false) {

	}

}

?>