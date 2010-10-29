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
				<div id="media-buttons" class="hide-if-no-js">
		Upload/Insert <a href="media-upload.php?post_id=46&amp;type=image&amp;TB_iframe=1" onclick="templ33t_editor_focus = tinyMCE.get(\'templ33t_editor_editor_'.$this->id.'\');" id="add_image" class="thickbox" title="Add an Image"><img src="http://technosis.is-a-geek.net/demo/wpmu3.0.1/wp-admin/images/media-button-image.gif?ver=20100531" alt="Add an Image"></a><a href="media-upload.php?post_id=46&amp;type=video&amp;TB_iframe=1" id="add_video" class="thickbox" title="Add Video"><img src="http://technosis.is-a-geek.net/demo/wpmu3.0.1/wp-admin/images/media-button-video.gif?ver=20100531" alt="Add Video"></a><a href="media-upload.php?post_id=46&amp;type=audio&amp;TB_iframe=1" id="add_audio" class="thickbox" title="Add Audio"><img src="http://technosis.is-a-geek.net/demo/wpmu3.0.1/wp-admin/images/media-button-music.gif?ver=20100531" alt="Add Audio"></a><a href="media-upload.php?post_id=46&amp;TB_iframe=1" id="add_media" class="thickbox" title="Add Media"><img src="http://technosis.is-a-geek.net/demo/wpmu3.0.1/wp-admin/images/media-button-other.gif?ver=20100531" alt="Add Media"></a>		</div>
			</div>
		';

		return $str . '<textarea rows="10" id="templ33t_editor_editor_'.$this->id.'" class="templ33t_editor_editor" cols="40" name="meta['.$this->id.'][value]" tabindex="2" id=""></textarea>';

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
