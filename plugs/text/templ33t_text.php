<?php

class Templ33tText extends Templ33tPlugin implements Templ33tTab, Templ33tOption {

	function __construct() {



	}

	function displayPanel() {



	}

	function displayOption() {

		$str = '<tr><td>'.$this->label.'</td><td><input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_option_'.$this->slug.'" /><input type="text" name="meta['.$this->id.'][value]" value="'.$this->value.'" /></td></tr>';

		return $str;

	}

}

?>