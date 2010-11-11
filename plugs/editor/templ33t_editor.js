
/*
jQuery(document).ready(
	function() {

		if(jQuery('div#templ33t_control').length) {
			
			jQuery('textarea.templ33t_editor_editor').each(
				function() {
					jQuery(this).addClass('mceEditor');
					if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
						tinyMCE.execCommand("mceAddControl", false, jQuery(this).attr('id'));
					}
				}
			);


			jQuery('div.templ33t_editor #media-buttons a').click(
				function(){

					templ33t_editor_focus = tinyMCE.get(jQuery(this).parent().parent().parent().find('textarea').attr('id'));

				}
			);

			jQuery('#postdivrich #media-buttons a').click(
				function(){
					
					templ33t_editor_focus = null;

				}
			);
		}

	}
);

function templ33tEdToolbar(mid) {
	alert('testing 1');
	var elem_str = '<div id="ed_toolbar">';
	for (var i = 0; i < edButtons.length; i++) {
		elem_str += templ33tEdShowButton(edButtons[i], i);
	}
	elem_str += '<input type="button" id="ed_spell" class="ed_button" onclick="edSpell(edCanvas);" title="' + quicktagsL10n.dictionaryLookup + '" value="' + quicktagsL10n.lookup + '" />';
	elem_str += '<input type="button" id="ed_close" class="ed_button" onclick="edCloseAllTags();" title="' + quicktagsL10n.closeAllOpenTags + '" value="' + quicktagsL10n.closeTags + '" />';
	elem_str += '</div>';

	jQuery('.templ33t_editor_quicktags_'+mid).html(elem_str);

}

function templ33tEdShowButton(button, i) {
	alert('testing 2');
	if (button.id == 'ed_img') {
		return '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" class="ed_button" onclick="edInsertImage(edCanvas);" value="' + button.display + '" />';
	}
	else if (button.id == 'ed_link') {
		return '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" class="ed_button" onclick="edInsertLink(edCanvas, ' + i + ');" value="' + button.display + '" />';
	}
	else {
		return '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" class="ed_button" onclick="edInsertTag(edCanvas, ' + i + ');" value="' + button.display + '"  />';
	}
}

function templ33tSwitchEditors(mid, mode) {

	alert('testing 3');

	var oldCanvas = edCanvas;

	edCanvas = document.getElementById('templ33t_editor_editor_'+mid);

	switchEditors.go('templ33t_editor_editor_'+mid, mode);

	edCanvas = oldCanvas;

}
*/