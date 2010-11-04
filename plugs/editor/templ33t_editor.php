<?php

class Templ33tEditor {

	static $load_js = true;
	static $custom_panel = false;

	var $slug;
	var $label;
	var $description;
	var $id;
	var $value;

	function __construct() {

		
	}

	function hasCustomPanel() {

		return self::$custom_panel;

	}
	
	function display() {

		$str = '
			<div class="editor-toolbar">
				<div class="zerosize"><input accesskey="e" type="button" onclick="templ33tSwitchEditors(\''.$this->id.'\', null)"></div>
					<a id="edButtonHTML'.$this->id.'" class="hide-if-no-js" onclick="templ33tSwitchEditors(\''.$this->id.'\', \'html\');">HTML</a>
					<a id="edButtonPreview'.$this->id.'" class="active hide-if-no-js" onclick="templ33tSwitchEditors(\''.$this->id.'\', \'tinymce\');">Visual</a>
				<div id="media-buttons" class="hide-if-no-js">';
		
		ob_start();
		media_buttons();
		$str .= ob_get_clean();

		$str .= '
			<div id="quicktags" class="templ33t_editor_quicktags_'.$this->id.'">
			<script type="text/javascript">templ33tEdToolbar(\''.$this->id.'\');</script>
			</div>
		';


		$str .= '		</div>
			</div>
			<input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_'.$this->slug.'" />
			<textarea rows="10" id="templ33t_editor_editor_'.$this->id.'" class="templ33t_editor_editor" cols="40" name="meta['.$this->id.'][value]" tabindex="2" id="">'.$this->value.'</textarea>
		';
		
		return $str;

	}

	/*
	function display() {


		ob_start();
		the_editor($this->value);
		$editor = ob_get_clean();

		$editor = preg_replace('/(\<script.*\>.*\<\/script\>)/mi', '', $editor);
		
		return $editor;
	}
	*/
}

?>
