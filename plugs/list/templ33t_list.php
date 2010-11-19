<?php

class Templ33tList extends Templ33tPlugin implements Templ33tTab {
	
	static $custom_panel = true;

	static $custom_post = true;

	static $load_style = true;
	
	static $load_js = true;

	static $dependencies = array('jquery-ui-sortable');

	var $class = null;

	var $element = 'ul';
	
	var $renderAs = 'text';

	function __construct() {

	}

	function init() {

		if($this->bindable)
			add_shortcode($this->slug, array(&$this, 'handleShortcode'));

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

		global $templ33t, $post;

		$str = '<div class="postbox"><div class="inside">';
		$str .= '<input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_'.$this->slug.'" /><ul id="templ33t_list_'.$this->id.'" class="templ33t_list">';
		if(empty($this->value)) $this->value[] = '';
		foreach($this->value as $key => $val) {
			$str .= '<li><div class="handle"></div><div class="actions"><a href="#" onclick="templ33t_delListItem(this); return false;"><img src="'.Templ33t::$assets_url.'img/delete.jpg"></a></div>';
			switch($this->renderAs) {
				case 'textarea':
					$str .= '<textarea name="meta['.$this->id.'][value][]">'.$val.'</textarea>';
					break;
				case 'text':
				default:
					$str .= '<input type="text" name="meta['.$this->id.'][value][]" value="'.$val.'" />';
					break;
			}
			$str .= '</li>';
		}
		//$str .= '<li><input type="text" name="meta['.$this->id.'][value][]" value="" /></li>';
		$str .= '</ul>';
		$str .= '<div class="templ33t_right"><input type="button" value="Add Item" onclick="templ33t_addListItem(\''.$this->id.'\', \''.$this->renderAs.'\')" /></div>';
		$str .= '<div class="templ33t_clear"></div>';

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

	function handlePost() {
		
		if(is_array($this->value)) {
			foreach($this->value as $key => $val) {
				if(empty($val)) unset($this->value[$key]);
			}
		}

	}

	function output($ret = false) {

		$this->parseValue();

		$str = '';
		
		if(!empty($this->value)) {

			$str .= '<'.$this->element.' class="'.$this->class.'">';
			foreach($this->value as $val) {
				$str .= '<li>'.$val.'</li>';
			}
			$str .= '</'.$this->element.'>';

			if($ret) return $str;
			else echo $str;

		} else {

			return false;

		}

	}

	function handleShortcode() {

		return $this->output(true);

	}

	function js($return = false, $wrap = true) {

		//wp_enqueue_script('jquery-ui-sortable');
		global $templ33t;

		if($return) ob_start();

		if($wrap) { ?>
		<script type="text/javascript">
		<?php } ?>
		
			function templ33t_addListItem(lid, ltype) {

				var list = jQuery('#templ33t_list_'+lid);
				
				var str = '<li>';

				if(!ltype) ltype = 'text';

				str += '<div class="handle"></div><div class="actions"><a href="#" onclick="templ33t_delListItem(this); return false;"><img src="'+TL33T_current.assets+'img/delete.jpg"></a></div>';

				switch(ltype) {
					case 'textarea':
						str += '<textarea name="meta['+lid+'][value][]"></textarea>';
						break;
					case 'text':
					default:
						str += '<input type="text" name="meta['+lid+'][value][]" />';
						break;
				}

				str += '</li>';

				list.append(str);

			}

			function templ33t_delListItem(aobj) {

				jQuery(aobj).parent().parent().remove();

			}

			jQuery(document).ready(
				function() {
					
					jQuery('ul.templ33t_list').sortable(
						{
							items: 'li',
							handle: 'div.handle',
							axis: 'y'
						}
					);
					
				}
			);

		<?php if($wrap) { ?>
		</script>
		<?php }

		if($return) {
			$ret = ob_get_clean ();
			return $ret;
		}

	}

	function style($return = false, $wrap = true) {

		global $templ33t;

		if($return) ob_start();

		if($wrap) { ?>
		<style type="text/css">
		<?php } ?>
			ul.templ33t_list, ul.templ33t_list li { position: relative; list-style: none; }
			ul.templ33t_list li div.handle {
				position: absolute;
				top: -1px;
				bottom: -1px;
				left: -2px;
				width: 15px;
				-webkit-border-radius: 4px 0px 0px 4px;
				-moz-border-radius: 4px 0px 0px 4px;
				-khtml-border-radius: 4px 0px 0px 4px;
				border-radius: 4px 0px 0px 4px;
				background: #dfdfdf url(img/handle.jpg) no-repeat top center;
				text-align: center;
				cursor: pointer;
			}
			ul.templ33t_list li div.actions {
				position: absolute;
				top: -1px;
				right: -2px;
				bottom: -1px;
				width: 15px;
				padding: 3px 2px;
				-webkit-border-radius: 0px 4px 4px 0px;
				-moz-border-radius: 0px 4px 4px 0px;
				-khtml-border-radius: 0px 4px 4px 0px;
				border-radius: 0px 4px 4px 0px;
				background: #dfdfdf;
				text-align: center;
			}
			ul.templ33t_list li div.handle a { float: right; color: #F00000; text-decoration: none; }
			ul.templ33t_list li input[type=text] { display: block; width: 100%; padding: 4px 20px; }
			ul.templ33t_list li textarea { display: block; height: auto; padding: 4px 20px; }
		<?php if($wrap) { ?>
		</style>
		<?php }

		if($return) {
			$ret = ob_get_clean();
			return $ret;
		}

	}

}

?>
