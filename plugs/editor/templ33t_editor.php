<?php

class Templ33tEditor {

	static $load_js = true;
	static $custom_panel = true;

	var $slug;
	var $label;
	var $description;
	var $id;
	var $value;

	function __construct() {

		
	}
	
	function display() {


		$str = '';

		$str .= '
			<div class="editor-toolbar">
				<div class="zerosize"><input accesskey="e" type="button" onclick="switchEditors.go(\'templ33t_editor_editor_'.$this->id.'\')"></div>
					<a id="edButtonHTML" class="hide-if-no-js" onclick="switchEditors.go(\'templ33t_editor_editor_'.$this->id.'\', \'html\');">HTML</a>
					<a id="edButtonPreview" class="active hide-if-no-js" onclick="switchEditors.go(\'templ33t_editor_editor_'.$this->id.'\', \'tinymce\');">Visual</a>
				<div id="media-buttons" class="hide-if-no-js">';
		
		ob_start();
		media_buttons();
		$str .= ob_get_clean();
		
		$str .= '		</div>
			</div>
			<textarea rows="10" id="templ33t_editor_editor_'.$this->id.'" class="templ33t_editor_editor" cols="40" name="meta['.$this->id.'][value]" tabindex="2" id=""></textarea>
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
