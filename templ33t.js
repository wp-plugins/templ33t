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

jQuery(document).ready(
	function() {

		// save current template index
		ctemp = jQuery('select#page_template').attr('selectedIndex');
		
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

	jQuery('#postdivrich').before(jQuery('div#templ33t_control'));
	jQuery('#postdivrich').attr('rel','default');
	jQuery('div#templ33t_control').show();

}

function templ33t_hideCustomFields() {

	jQuery('div#templ33t_control a').each(
		function() {
			
			if(jQuery(this).attr('rel') != 'default')
				jQuery('tr#meta-'+jQuery(this).attr('rel')).addClass('templ33t_cf').hide();
			
		}
	);

}

function templ33t_switchEditor() {

	var crel = jQuery('div#templ33t_control li.selected a').attr('rel');
	var ccontent = jQuery('#content_ifr').contents().find('body').html();
	var nrel = jQuery(this).attr('rel');
	var ncontent;

	if(crel == 'default') {
		jQuery('div#templ33t_main_content').html(ccontent);
	} else {
		jQuery('tr#meta-'+crel+' textarea').val(ccontent);
	}

	if(nrel == 'default') {
		ncontent = jQuery('div#templ33t_main_content').html();
		jQuery('#content_ifr').contents().find('body').html(ncontent);
		jQuery('#editorcontainer textarea').text(ncontent);
	} else {
		ncontent = jQuery('tr#meta-'+nrel+' textarea').val();
		jQuery('#content_ifr').contents().find('body').html(ncontent);
		jQuery('#editorcontainer textarea').text(ncontent);
	}

	jQuery('#postdivrich').attr('rel', nrel);
	
	jQuery('div#templ33t_control li.selected').removeClass('selected');
	jQuery(this).parent().addClass('selected');

	return false;

}

function templ33t_cleanup() {

	if(jQuery('div#templ33t_control').length) {

		// force tinymcs visual mode
		jQuery('a#edButtonPreview').click();

		// set to default tab
		jQuery('li#templ33t_default a').click();

	}
	
}