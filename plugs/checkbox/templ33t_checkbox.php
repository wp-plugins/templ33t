<?php

class Templ33tCheckbox extends Templ33tPlugin implements Templ33tTab, Templ33tOption {
	
	static $custom_panel = true;
	
	var $positive_output = 'Yes';
	
	var $negative_output = 'No';
	
	function __construct() {



	}
	
	function displayConfig() {
		
		return '';
		
	}

	function displayPanel() {



	}

	function output($ret = false) {
		
		if(!$ret) {
			echo $this->value ? $this->positive_output : $this->negative_output;
		} else {
			return $this->value;
		}

	}

	function displayOption() {



	}

	function getValue() {

		

	}

}
