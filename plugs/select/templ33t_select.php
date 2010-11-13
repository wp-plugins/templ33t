<?php

class Templ33tSelect extends Templ33tPlugin implements Templ33tTab, Templ33tOption {

	var $options = array();

	function __construct() {

		

	}

	function parseConfig($config = null) {

		parent::parseConfig($config);

		if(!empty($this->config)) {

			

		}

	}

	function init() {

		

	}

	function displayPanel() {



	}

	function displayOption() {

		$str = '<tr><td>'.$this->label.'</td><td><input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_option_'.$this->slug.'" />';
		$str .= $this->genSelect();
		$str .= '</td></tr>';

		return $str;

	}

	function genSelect() {

		$str = '<select name="meta['.$this->id.'][value]"><option value="">-- Choose --</option>';
		if(!empty($this->options)) {
			foreach($this->options as $key => $val) {
				$str .= '<option value="'.$key.'">'.$val.'</option>';
			}
		}
		$str .= '</select>';

		return $str;
		
	}

	function genCheckbox() {



	}

	function genRadio() {



	}

}
