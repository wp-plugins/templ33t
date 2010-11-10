<?php

class Templ33tSize extends Templ33tPlugin implements Templ33tOption {

	static $custom_post = true;
	
	function __construct() {
		
	}

	function displayOption() {

		$str = '<tr><td><label>'.$this->label.':</label>';
		$str .= '<input type="hidden" name="templ33t_meta['.$this->slug.'][id]" value="'.$this->id.'"> </td><td>';
		$str .= '<input type="input" name="templ33t_meta['.$this->slug.'][value]" value="'.$this->value.'" size="2" /><br/>';
		$str .= '<input type="radio" name="templ33t_meta['.$this->slug.'][size_type]" value="%" /> Percent ';
		$str .= '<input type="radio" name="templ33t_meta['.$this->slug.'][size_type]" value="px" /> Pixels ';
		$str .= '</td></tr>';

		return $str;

	}

	function handlePost() {

		

	}

}

?>
