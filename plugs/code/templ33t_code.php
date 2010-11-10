<?php

class Templ33tCode extends Templ33tPlugin implements Templ33tTab {
	
	static $custom_panel = true;

	function __construct() {

	}

	function init() {

		if($this->bindable)
			add_shortcode($this->slug, array(&$this, 'handleShortcode'));

	}

	function displayPanel() {

		$str = '<div class="postbox"><div class="inside">';
		//$str .= '<p class="templ33t_description">Enter your code below. It can be added to your page by using the shortcode <strong>['.$this->slug.']</strong> in your editor.</p>';
		$str .= '<input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_'.$this->slug.'"><textarea name="meta['.$this->id.'][value]">'.$this->value.'</textarea>';
		$str .= '</div></div>';

		return $str;
		
	}

	function handleShortcode() {

		return $this->value;

	}

}

?>
