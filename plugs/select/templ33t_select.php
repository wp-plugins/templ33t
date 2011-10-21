<?php

class Templ33tSelect extends Templ33tPlugin implements Templ33tTab, Templ33tOption {

	var $options = array();
	var $renderAs = 'select';
	var $multiple = false;

	function __construct() {

		

	}

	function parseConfig($config = null) {

		parent::parseConfig($config);

		if(!empty($this->config)) {

			$carr = array();
			parse_str($this->config, $carr);

			foreach($carr as $key => $val) {
				$this->$key = $val;
			}

			// parse options
			if(!is_array($this->options)) {
				$options = array();
				$vals = explode(',', preg_replace('/,[\s]+/', ',', $this->options));
				foreach($vals as $val) {
					$options[Templ33t::slug($val)] = htmlspecialchars($val, ENT_QUOTES);
				}
				$this->options = $options;
			}

		}

	}

	function init() {

		

	}
	
	function displayConfig() {
		
		return '';
		
	}

	function displayPanel() {



	}

	function output($ret = false) {

		

	}

	function displayOption() {

		$str = '<tr><td>'.$this->label.'</td><td><input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_option_'.$this->slug.'" />';
		switch($this->renderAs) {
			case 'checkbox':
				$str .= $this->genCheckbox();
				break;
			case 'radio':
				$str .= $this->genRadio();
				break;
			case 'select':
			default:
				$str .= $this->genSelect();
				break;
		}
		$str .= '</td></tr>';

		return $str;

	}

	function genSelect() {

		$str = '<select name="meta['.$this->id.'][value]"><option value="">-- Choose --</option>';
		if(!empty($this->options)) {
			foreach($this->options as $key => $val) {
				$str .= '<option value="'.$key.'"'.($this->value == $key ? ' selected="selected"' : '').'>'.$val.'</option>';
			}
		}
		$str .= '</select>';

		return $str;
		
	}

	function genCheckbox() {



	}

	function genRadio() {



	}

	function getValue() {

		return $this->value;

	}

}
