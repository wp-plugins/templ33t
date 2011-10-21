<?php

class Templ33tVideo extends Templ33tPlugin implements Templ33tTab {

	static $load_js = false;
	static $custom_panel = true;

	var $slug;
	var $label;
	var $description;
	var $id;
	var $value;

	function __construct() {
		
	}
	
	function displayConfig() {
		
		return '';
		
	}

	function displayPanel() {

		return '<p>THIS IS A VIDEO EDITOR</p>';

	}

	function output($ret = false) {



	}

}

?>
