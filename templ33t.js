/*
/--------------------------------------------------------------------\
|                                                                    |
| License: GPL                                                       |
|                                                                    |
| Templ33t - Adds tabs to edit page for custom content blocks in     |
| WordPress page templates                                           |
| Copyright (C) 2010, Ryan Willis,                                   |
| http://www.totallyryan.com                                         |
| All rights reserved.                                               |
|                                                                    |
| This program is free software; you can redistribute it and/or      |
| modify it under the terms of the GNU General Public License        |
| as published by the Free Software Foundation; either version 2     |
| of the License, or (at your option) any later version.             |
|                                                                    |
| This program is distributed in the hope that it will be useful,    |
| but WITHOUT ANY WARRANTY; without even the implied warranty of     |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
| GNU General Public License for more details.                       |
|                                                                    |
| You should have received a copy of the GNU General Public License  |
| along with this program; if not, write to the                      |
| Free Software Foundation, Inc.                                     |
| 51 Franklin Street, Fifth Floor                                    |
| Boston, MA  02110-1301, USA                                        |
|                                                                    |
\--------------------------------------------------------------------/
*/

var ctemp = 0;
var ctmode = 'set';

jQuery(document).ready(
	function() {

		//alert(TL33T_def.toString());

		// save current template index
		ctemp = jQuery('select#page_template').attr('selectedIndex');

		// set current mode
		ctmode = getUserSetting('editor') == 'tinymce' ? false : true;

		// add event listener to template select
		jQuery('select#page_template').change(templ33t_switchTemplate);

		// set up templ33t
		if(jQuery('div#templ33t_control').length) {

			// move tab elements
			templ33t_placeControl();

			// hide custom fields
			templ33t_hideCustomFields();

			// add rel to editor
			jQuery('#postdivrich').attr('rel', 'default');

			if(ctmode) templ33t_switchMode('html');

			// add tab event listeners
			jQuery('div#templ33t_control a').click(templ33t_switchEditor);

			// add cleanup to form submission
			jQuery('form#post').submit(templ33t_cleanup);
			
		}

	}
);

function templ33t_switchTemplate() {

	var newtemp = jQuery('select#page_template').val();

	var oldtemp = jQuery('select#page_template option:eq('+ctemp+')').attr('value');

	if(!oldtemp || oldtemp == 'default') oldtemp = 'page.php';

	if(newtemp in TL33T_def || oldtemp in TL33T_def) {

		var r = confirm('The page must be reloaded for this template. Any changes will be saved. Are you ready to proceed?');
		
		if(r) {
			
			ctemp = jQuery('select#page_template').attr('selectedIndex');

			templ33t_cleanup();

			if(jQuery('input#publish').attr('disabled')) {
				jQuery('form#post').submit();
			} else {
				jQuery('input#publish').click();
			}


			
		} else {

			jQuery('select#page_template').attr('selectedIndex', ctemp);

		}

	}

}

function templ33t_placeControl() {

	jQuery('#postdivrich').attr('rel','default');
	jQuery('#postdivrich').before(jQuery('div#templ33t_control'));
	jQuery('div#templ33t_control').show();
	jQuery('#postdivrich').before(jQuery('div#templ33t_descriptions'));
	jQuery('div#templ33t_descriptions').show();
	jQuery('#postdivrich').before(jQuery('div#templ33t_editors'));
	jQuery('div#templ33t_editors').show();

}

function templ33t_hideCustomFields() {

	jQuery('div#postcustomstuff table#list-table tr[id|="meta"]').has('input[value^="templ33t_"]').remove();

}

function templ33t_switchEditor(prel) {
	
	alert(prel);
	
	var ntmode = getUserSetting('editor') == 'tinymce' ? false : true;
	var crel = jQuery('div#templ33t_control li.selected a').attr('rel');
	var nobj = typeof(prel) == 'string' ? jQuery('div#templ33t_control li a[rel="'+prel+'"]') : jQuery(this);
	var nrel = nobj.attr('rel');
	var ccontent = ntmode ? jQuery('textarea#content').val() : jQuery('#content_ifr').contents().find('body').html();
	var ncontent;
	var fromcustom = (jQuery('#postdivrich').css('display') == 'none');
	var tocustom;
	if(nobj.parent().index() > 0)
		tocustom = TL33T_def[jQuery('select#page_template').val()].blocks[(nobj.parent().index() - 1)].custom;
	else
		tocustom = false;

	// store current tab value
	if(crel == 'default') {
		jQuery('div#templ33t_main_content').html(ccontent);
	} else if(!fromcustom) {
		jQuery('#templ33t_val_'+crel).val(ccontent);
	}

	// if mode has been switched, process content
	if(ntmode != ctmode) templ33t_switchMode(null);

	// load new tab value
	if(nrel == 'default') {
		ncontent = jQuery('div#templ33t_main_content').html();
		jQuery('textarea#content').val(ncontent);
		if(!ntmode) jQuery('#content_ifr').contents().find('body').html(ncontent);
	} else {
		if(!tocustom) {
			ncontent = jQuery('#templ33t_val_'+nrel).val();
			jQuery('textarea#content').val(ncontent);
			if(!ntmode) jQuery('#content_ifr').contents().find('body').html(ncontent);
		}
	}

	// hide all custom panels
	jQuery('div.templ33t_editor').hide();

	// set rel
	jQuery('#postdivrich').attr('rel', nrel);

	// mark selected
	jQuery('div#templ33t_control li.selected').removeClass('selected');
	nobj.parent().addClass('selected');

	// show description
	jQuery('div.templ33t_desc_'+crel).addClass('templ33t_hidden');
	jQuery('div.templ33t_desc_'+nrel).removeClass('templ33t_hidden');

	if(!tocustom) {

		jQuery('#postdivrich').show();
		//jQuery('#templ33t_descriptions').show();

	} else {
		
		jQuery('#postdivrich').hide();
		//jQuery('#templ33t_descriptions').hide();
		jQuery('div#templ33t_editor_'+nrel).show();

	}

	return false;

}

function templ33t_switchMode(switchTo) {

	if(ctmode == 'set')
		ctmode = getUserSetting('editor') == 'tinymce' ? false : true;

	jQuery('div#templ33t_control ul li').each(
		function() {

			var crel = null;
			var cval = null;
			var elem = jQuery(this);

			if(!elem.hasClass('selected') && !elem.hasClass('templ33t_settings')) {

				crel = jQuery('a:first', jQuery(this)).attr('rel');

				if(crel == 'default') {

					cval = jQuery('div#templ33t_main_content').html();
					if(cval) {
						if(switchTo == 'html' || !ctmode) {
							jQuery('div#templ33t_main_content').html(switchEditors._wp_Nop(cval));
							//alert('no_p: '+crel+' - '+cval);
						} else {
							jQuery('div#templ33t_main_content').html(switchEditors.wpautop(cval));
							//alert('auto_p: '+crel+' - '+cval);
						}
					}

				} else {

					if(!TL33T_def[jQuery('select#page_template').val()].blocks[(jQuery(this).index() - 1)].custom) {

						cval = jQuery('#templ33t_val_'+crel).val();
						if(cval) {
							if(switchTo == 'html' || !ctmode) {
								jQuery('#templ33t_val_'+crel).val(switchEditors._wp_Nop(cval));
								//alert('no_p: '+crel+' - '+cval);
							} else {
								jQuery('#templ33t_val_'+crel).val(switchEditors.wpautop(cval));
								//alert('auto_p: '+crel+' - '+cval);
							}
						}

					}

				}

			}

		}
	);

	ctmode = getUserSetting('editor') == 'tinymce' ? false : true;

}

function templ33t_append(scode) {
	
	if(!ctmode) {
		jQuery('textarea#content').val(jQuery('textarea#content').val()+"<p>"+scode+"</p>");
		jQuery('#content_ifr').contents().find('body').append("<p>"+scode+"</p>");
		jQuery('#content_ifr').attr('scrollTop', jQuery('#content_ifr').attr('scrollHeight'));
	} else {
		jQuery('textarea#content').val(jQuery('textarea#content').val()+"\n\n"+switchEditors._wp_Nop("<p>"+scode+"</p>"));
		jQuery('textarea#content').attr('scrollTop', jQuery('textarea#content').attr('scrollHeight'));
	}

}

function templ33t_cleanup() {

	if(jQuery('div#templ33t_control').length) {

		// force tinymcs visual mode
		//jQuery('a#edButtonPreview').click();

		// set to default tab
		jQuery('li#templ33t_default a').click();

	}
	
}

var templ33t_editor_focus;

function send_to_templ33t_field(h) {
	
	if ( templ33t_editor_focus ) ed = templ33t_editor_focus;
	else if ( typeof tinyMCE == "undefined" ) ed = document.getElementById('content');
	else {ed = tinyMCE.get('content');}
	if ( typeof tinyMCE != 'undefined') {
		ed.focus();
		if (tinymce.isIE)
			ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
		if ( h.indexOf('[caption') != -1 )
			h = ed.plugins.wpeditimage._do_shcode(h);
		ed.execCommand('mceInsertContent', false, h);
	} else {
		if ( templ33t_editor_focus ) edInsertContent(templ33t_editor_focus, h);
		else edInsertContent(edCanvas, h);
	}
	tb_remove();
	templ33t_editor_focus = undefined;

}