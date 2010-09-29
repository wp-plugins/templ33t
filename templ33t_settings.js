

jQuery(document).ready(
	function() {
		
		jQuery('div.templ33t_themes ul li').click(templ33t_configTheme);

	}
);

function templ33t_configTheme() {

	jQuery('div.templ33t_blocks div.templ33t_active').removeClass('templ33t_active').addClass('templ33t_hidden');
	jQuery('div.templ33t_blocks div.templ33t_block_list_'+jQuery(this).attr('rel')).removeClass('templ33t_hidden').addClass('templ33t_active');

	jQuery('div.templ33t_themes li.selected').removeClass('selected');
	jQuery(this).addClass('selected');
	
	return false;

}