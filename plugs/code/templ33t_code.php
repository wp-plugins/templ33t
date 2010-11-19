<?php

class Templ33tCode extends Templ33tPlugin implements Templ33tTab {
	
	static $custom_panel = true;

	static $dependencies = array('jquery');

	var $class = null;

	function __construct() {

	}

	function init() {

		if($this->bindable)
			add_shortcode($this->slug, array(&$this, 'handleShortcode'));

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

	function displayPanel() {

		global $templ33t, $post;

		$str = '<div class="postbox"><div class="inside">';
		$str .= '<input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_'.$this->slug.'"><textarea name="meta['.$this->id.'][value]">'.$this->value.'</textarea>';
		
		if($this->bindable) {
			$str .= '<p class="templ33t_description">';
			$links = array('<a href="#" onclick="templ33t_switchEditor(\'default\'); templ33t_append(\'['.$this->slug.']\'); return false;">'.$templ33t->map[$post->page_template]['main'].'</a>');
			foreach($templ33t->block_objects as $slug => $block) {
				if($block->type == 'editor') $links[] = '<a href="#" onclick="templ33t_switchEditor(\''.$block->id.'\'); templ33t_append(\'['.$this->slug.']\'); return false;">'.$block->label.'</a>';
			}
			if(!empty($links)) $str .= 'Add to '.implode(' | ', $links) . ' -OR- ';
			$str .= 'Use this element with the <strong>['.$this->slug.']</strong> shortcode in your editor.</p>';
		}

		$str .= '</div></div>';

		return $str;
		
	}

	function handleShortcode() {

		return $this->value;

	}

	function output($ret = false) {

		if($ret) return $this->value();
		else echo $this->value();

	}

}

?>
