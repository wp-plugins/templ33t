<?php

class Templ33tImage extends Templ33tPlugin implements Templ33tTab {
	
	static $custom_panel = true;
	
	static $load_js = true;
	
	static $media_captured = false;
	
	var $outputID = '';
	
	var $outputClass = '';
	
	function __construct() {
		
		// set up media_send_to_editor and image_send_to_editor_url filters
		// reference function image_media_send_to_editor() to see how it's done
		
		//add_action()
		
		
		
	}
	
	function displayConfig() {
		
		$str = '
			
			<tr>
				<td valign="top"><label for="optional">Optional:</label>&nbsp;</td>
				<td valign="top">
					<input type="hidden" name="templ33t_block_config[optional]" value="0" />
					<input type="checkbox" id="optional" name="templ33t_block_config[optional]" value="1"';
		
		if($this->optional) $str .= ' checked="checked"';
		
		$str .= '/>
				</td>
			</tr>
			<tr>
				<td valign="top"><label for="bindable">Bindable:</label>&nbsp;</td>
				<td valign="top">
					<input type="hidden" name="templ33t_block_config[bindable]" value="0" />
					<input type="checkbox" id="bindable" name="templ33t_block_config[bindable]" value="1"';
		
		if($this->bindable) $str .= ' checked="checked"';
		
		$str .= '/>
				</td>
			</tr>
			<tr>
				<td valign="top"><label for="searchable">Searchable:</label>&nbsp;</td>
				<td valign="top">
					<input type="hidden" name="templ33t_block_config[searchable]" value="0" />
					<input type="checkbox" id="searchable" name="templ33t_block_config[searchable]" value="1"';
		
		if($this->searchable) $str .= ' checked="checked"';
		
		$str .= '/>
				</td>
			</tr>
			<tr>
				<td valign="top"><label for="">Class:</label>&nbsp;</td>
				<td valign="top"><input type="text" name="templ33t_block_config[outputClass]" value="'.$this->outputClass.'"></td>
			</tr>
			<tr>
				<td valign="top"><label for="">ID:</label>&nbsp;</td>
				<td valign="top"><input type="text" name="templ33t_block_config[outputID]" value="'.$this->outputClass.'"></td>
			</tr>

		';
		
		$str .= '<tr><td valign="top"><label for="default">Default Value:</label>&nbsp;</td><td valign="top">';
		$str .= '<input type="hidden" name="templ33t_block_config[default]" id="templ33t_image_value_0" value="'.$this->default.'" />';
		$str .= '<div class="image-default" id="templ33t_image_preview_0">'.(!empty($this->default) ? '<img src="'.stripslashes($this->default).'" />' : 'no image').'</div>';
		
		$str .= '<script type="text/javascript">';
		$str .= 'function send_to_editor(i, h) {
			alert(i+' - '+h)
			if(h != \'\') {
				var c = jQuery(h);
			
				if(c.is(\'a\')) {
					c = c.find(\'img\');
				}
			
				jQuery(\'div.image-default\').empty()
					.append(c)
					.siblings(\'input\').val(c.prop(\'src\'));
			}
			tb_remove();

		}';
		$str .= '</script>';
		
		$str .= '<p><a href="'.admin_url('media-upload.php?type=image&from_templ33t_settings=1&TB_iframe=1').'" class="thickbox" onClick="templ33t_set_media_target(\'0\', templ33t_capture_image);">Choose Image</a></p>';
		$str .= '</td></tr>';
		
		return $str;
		
	}
	
	function displayPanel() {
		
		global $post;
		
		$str = '<div class="postbox"><div class="inside">';
		$str .= '<input type="hidden" name="meta['.$this->id.'][key]" value="templ33t_'.$this->slug.'" />';
		$str .= '<input type="hidden" id="templ33t_image_value_'.$this->id.'" name="meta['.$this->id.'][value]" value="" />';
		$str .= '<p><a href="'.admin_url('media-upload.php?post_id='.$post->ID.'&type=image&TB_iframe=1').'" class="thickbox" onClick="templ33t_set_media_target('.$this->id.', templ33t_capture_image);">Choose Image</a></p>';
		
		$str .= '<div id="templ33t_image_preview_'.$this->id.'">'.(!empty($this->value) ? '<img id="'.$this->outputID.'" class="'.$this->outputClass.'" src="'.$this->value.'" />' : '').'</div>';
		
		$str .= '</div></div>';
		
		return $str;
		
	}
	
	function output($ret = false) {
		
		$val = !empty($this->value) ? $this->value : $this->default;
		
		$str = !empty($val) ? '<img id="'.$this->outputID.'" class="'.$this->outputClass.'" src="'.$val.'" />' : '';
		
		if(!$ret) {
			echo $str;
		} else {
			return $str;
		}
		
	}
	
	function style() {
		
		
		
	}
	
	function js($return = false, $wrap = true) {
		
		$str = '';
		
		if($wrap) {
			$str .= '<script type="text/javascript">';
		}
		
		$str .= '
			
			function templ33t_capture_image(i, h) {
				
				var c = jQuery(h);

				if(!c.is(\'img\')) {
					c = c.find(\'img\');
					if(!c.length) {
						alert(\'You must choose an image.\');
						return;
					}
				}
				
				jQuery(\'#templ33t_image_value_\'+i).val(c.prop(\'src\'));
				jQuery(\'div#templ33t_image_preview_\'+i).empty().append(c);
				
			}
			
		';
		
		if($wrap) {
			$str .= '</script>';
		}
		
		if(!$return) {
			echo $str;
		} else {
			return $str;
		}
		
	}
	
}

?>
