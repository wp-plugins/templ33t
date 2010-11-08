<?php

abstract class Templ33tPlugin {
	
	var $id;
	var $slug;
	var $label;
	var $description;
	var $value;

	static $load_js;
	static $print_js;
	static $custom_panel;

	function __construct() {

		

	}

	function hasCustomPanel() {

		return self::$custom_panel;

	}

}

?>
