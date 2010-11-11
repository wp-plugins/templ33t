<?php

class Templ33tList extends Templ33tPlugin implements Templ33tTab {
	
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

	function displayPanel() {

		return '<p>THIS IS A LIST EDITOR</p>';

	}

}

?>
