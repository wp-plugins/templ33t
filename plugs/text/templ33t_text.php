<?php

class Templ33tText extends Templ33tPlugin implements Templ33tTab, Templ33tOption {
	
	static $custom_panel = true;
	
	var $multiline = false;
	
	function __construct() {



	}
	
	function displayConfig() {
		
		if(is_array($this->default)) {
			$this->default = implode(', ', $this->default);
		}
		
		$str = '<tr><td><label for="multiline">Multi Line</label></td><td>';
		$str .= '<input type="hidden" name="templ33t_block_config[multiline]" value="0" />';
		$str .= '<input type="checkbox" id="multiline" name="templ33t_block_config[multiline]" value="1" />';
		$str .= '</td></tr><tr><td><label for="default">Default Value</label></td><td>';
		$str .= $this->multiline ? '<textarea name="templ33t_block_config[default]">'.$this->default.'</textarea>' : '<input type="text" name="templ33t_block_config[default]" value="'.$this->default.'" size="60" />';
		$str .= '</td></tr>';
		
		return $str;
		
	}

	function displayPanel() {

		$str = '<div class="postbox"><div class="inside">';
		
		$str .= '<input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_'.$this->slug.'" />';
		
		$str .= '<label>Text:</label> <br/> ';
		
		$str .= $this->multiline ? '<textarea name="meta['.$this->id.'][value]">'.$this->value.'</textarea>' : '<input type="text" name="meta['.$this->id.'][value]" value="'.$this->value.'" size="60" />';
		
		$str .= '</div></div>';
		
		return $str;

	}

	function output($ret = false) {

		if(!$ret) {
			echo $this->multiline ? nl2br($this->value) : $this->value;
		} else {
			return $this->multiline ? nl2br($this->value) : $this->value;
		}

	}

	function displayOption() {

		$str = '<tr><td>'.$this->label.'</td><td><input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_option_'.$this->slug.'" /><input type="text" name="meta['.$this->id.'][value]" value="'.$this->value.'" /></td></tr>';

		return $str;

	}

	function getValue() {

		

	}

}

?>
