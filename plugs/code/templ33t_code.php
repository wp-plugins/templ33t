<?php

class Templ33tCode {

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
		
		return '<input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_'.$this->slug.'"><textarea name="meta['.$this->id.'][value]">'.$this->value.'</textarea>';
		
	}

}

?>
