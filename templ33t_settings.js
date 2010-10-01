

jQuery(document).ready(
	function() {

		jQuery('input.templ33t_template')
			.focusin(function(){ if(jQuery(this).val() == 'Template Filename') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('Template Filename').addClass('templ33t_light'); })
			.val('Template Filename')
			.addClass('templ33t_light');

		jQuery('input.templ33t_main_label')
			.focusin(function(){ if(jQuery(this).val() == 'Main Tab Label') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('Main Tab Label').addClass('templ33t_light'); })
			.val('Main Tab Label')
			.addClass('templ33t_light');

		jQuery('input.templ33t_block')
			.focusin(function(){ if(jQuery(this).val() == 'Block Name') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('Block Name').addClass('templ33t_light'); })
			.val('Block Name')
			.addClass('templ33t_light');

		jQuery('form#templ33t_new_template').submit(
			function() {

				if(jQuery('input.templ33t_template').val() == 'Template Filename')
					jQuery('input.templ33t_template').val('');

				if(jQuery('input.templ33t_main_label').val() == 'Main Tab Label')
					jQuery('input.templ33t_main_label').val('');

			}
		);

		jQuery('form#templ33t_all_block').submit(
			function() {

				if(jQuery('form#templ33t_all_block input.templ33t_block').val() == 'Block Name')
					jQuery('form#templ33t_all_block input.templ33t_block').val('');

			}
		);

		jQuery('form#templ33t_new_block').submit(
			function() {

				if(jQuery('form#templ33t_new_block input.templ33t_block').val() == 'Block Name')
					jQuery('form#templ33t_new_block input.templ33t_block').val('');

			}
		);

		var liselected = jQuery('div.templ33t_themes ul li.selected');
		var pos = liselected.index() * liselected.innerHeight();

		if(liselected.parent().parent().height() < pos)
			liselected.parent().parent().scrollTop(pos);

		
	}
);