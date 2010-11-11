<?php

class Templ33tSize extends Templ33tPlugin implements Templ33tOption {

	static $custom_post = true;

	var $size_type = '%';

	function __construct() {



	}

	function parseConfig($config = null) {

		parent::parseConfig($config);

		if(!empty($this->config)) {
			parse_str($this->config, $config);
			if(array_key_exists('default', $config)) $this->default = $config['default'];
		}

	}

	function parseValue($val = null) {

		$value = !empty($val) ? $val : $this->value;

		if(!empty($value)) {
			$size_type = strpos($value, '%') !== false ? '%' : 'px';
			$value = preg_replace('/([^0-9]+)/i', '', $value);
		} else {
			$size_type = $this->size_type;
		}

		// set to default if empty
		if(empty($value) && !empty($this->default)) {
			extract($this->parseValue($this->default));
		}

		if(!empty($val)) {
			return array('size_type' => $size_type, 'value' => $value);
		} else {
			$this->size_type = $size_type;
			$this->value = $value;
		}

	}

	function displayOption() {

		$this->parseValue();
		
		$str = '<tr><td><label>'.$this->label.':</label>';
		$str .= '<input type="hidden" name="templ33t_meta['.$this->slug.'][id]" value="'.$this->id.'"> </td><td>';
		$str .= '<input type="input" name="templ33t_meta['.$this->slug.'][value]" value="'.$this->value.'" size="2" />&nbsp; ';
		$str .= '<input type="radio" name="templ33t_meta['.$this->slug.'][size_type]" value="%"'.($this->size_type == '%' ? ' checked="checked"' : '').' /> % ';
		$str .= '<input type="radio" name="templ33t_meta['.$this->slug.'][size_type]" value="px"'.($this->size_type == 'px' ? ' checked="checked"' : '').' /> px ';
		$str .= '</td></tr>';

		return $str;

	}

	function handlePost() {

		if(array_key_exists($this->slug, $_POST['templ33t_meta'])) {

			$this->size_type = $_POST['templ33t_meta'][$this->slug]['size_type'];
			$this->value .= $this->size_type;

		}

	}

}

?>
