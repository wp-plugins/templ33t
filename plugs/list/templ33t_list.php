<?php

class Templ33tList {

	static $load_js = false;
	static $custom_panel = true;

	var $slug;
	var $label;
	var $description;
	var $id;
	var $value;

	function __construct() {

	}

	function hasCustomPanel() {

		return self::$custom_panel;

	}

	function display() {

		return '<p>THIS IS A LIST EDITOR</p>';

	}

}

?>
