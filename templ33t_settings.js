

jQuery(document).ready(
	function() {
		
		jQuery('div.templ33t_themes ul li').click(templ33t_configTheme);

		jQuery('input.templ33t_template')
			.focusin(function(){ if(jQuery(this).val() == 'Template Filename') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('Template Filename').addClass('templ33t_light'); })
			.val('Template Filename')
			.addClass('templ33t_light');

		jQuery('input.templ33t_main_label')
			.focusin(function(){ if(jQuery(this).val() == 'Main Label') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('Main Label').addClass('templ33t_light'); })
			.val('Main Label')
			.addClass('templ33t_light');

	}
);

function templ33t_configTheme() {

	jQuery('div.templ33t_blocks div.templ33t_active').removeClass('templ33t_active').addClass('templ33t_hidden');
	jQuery('div.templ33t_blocks div.templ33t_block_list_'+jQuery(this).attr('rel')).removeClass('templ33t_hidden').addClass('templ33t_active');

	jQuery('div.templ33t_themes li.selected').removeClass('selected');
	jQuery(this).addClass('selected');
	
	return false;

}