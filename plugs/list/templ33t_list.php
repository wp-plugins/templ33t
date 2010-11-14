<?php

class Templ33tList extends Templ33tPlugin implements Templ33tTab {
	
	static $custom_panel = true;
	static $custom_post = true;
	static $load_styles = true;

	var $class = null;
	var $renderAs = 'text';

	function __construct() {

	}

	function init() {

		$this->parseValue();

	}

	function parseConfig($config = null) {

		parent::parseConfig($config);

		if(!empty($this->config)) {

			$carr = array();
			parse_str($this->config, $carr);

			foreach($carr as $key => $val) {
				$this->$key = $val;
			}

		}

	}

	function parseValue($value = null) {

		if(!empty($value)) $this->value = $value;

		if(!empty($this->value) && !is_array($this->value)) {
			$this->value = unserialize($this->value);
		}

	}

	function displayPanel() {

		$str = '<input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_'.$this->slug.'" /><ul class="templ33t_list">';
		foreach($this->value as $key => $val) {
			$str .= '<li><input type="text" name="meta['.$this->id.'][value][]" value="'.$val.'" /></li>';
		}
		$str .= '<li><input type="text" name="meta['.$this->id.'][value][]" value="" /></li>';
		$str .= '</ul>';

		return $str;

	}

	function handlePost() {

		

	}

	function js() {

		

	}

	function styles() {

		$str = '<style type="text/css">';
		$str .= 'ul.templ33t_list li input[type=text] { display: block; width: 100%; }';
		$str .= '</style>';

		return $str;

	}

}

?>
