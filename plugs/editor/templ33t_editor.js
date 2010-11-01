
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

