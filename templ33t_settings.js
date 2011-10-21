

jQuery(document).ready(
	function() {

		jQuery('div#templ33t_control ul li a').click(templ33tSwitchTab);

		jQuery('form#templ33t_new_template').submit(
			function() {

				if(jQuery('input.templ33t_template').hasClass('templ33t_light') && jQuery('input.templ33t_template').val() == 'mytemplate.php')
					jQuery('input.templ33t_template').val('');

				if(jQuery('input.templ33t_main_label').hasClass('templ33t_light') && jQuery('input.templ33t_main_label').val() == 'Main Content')
					jQuery('input.templ33t_main_label').val('');

				if(jQuery('input.templ33t_main_description').hasClass('templ33t_light') && jQuery('input.templ33t_main_description').val() == 'This is the main content section.')
					jQuery('input.templ33t_main_description').val('');

			}
		);

		jQuery('form#templ33t_all_block').submit(
			function() {

				if(jQuery('form#templ33t_all_block input.templ33t_block').hasClass('templ33t_light') && jQuery('form#templ33t_all_block input.templ33t_block').val() == 'My Custom Area')
					jQuery('form#templ33t_all_block input.templ33t_block').val('');

				if(jQuery('input.templ33t_block_description').hasClass('templ33t_light') && jQuery('input.templ33t_block_description').val() == 'Content entered here will be displayed in a custom block.')
					jQuery('input.templ33t_block_description').val('');

			}
		);

		jQuery('form#templ33t_new_block').submit(
			function() {

				if(jQuery('form#templ33t_new_block input.templ33t_block').hasClass('templ33t_light') && jQuery('form#templ33t_new_block input.templ33t_block').val() == 'My Custom Area')
					jQuery('form#templ33t_new_block input.templ33t_block').val('');

				if(jQuery('input.templ33t_block_description').hasClass('templ33t_light') && jQuery('input.templ33t_block_description').val() == 'Content entered here will be displayed in a custom block.')
					jQuery('input.templ33t_block_description').val('');

			}
		);

		var liselected = jQuery('div.templ33t_themes ul li.selected');
		var pos = liselected.index() * liselected.innerHeight();

		if(liselected.parent().parent().height() < pos)
			liselected.parent().parent().scrollTop(pos);

		jQuery('input.templ33t_template')
			.focusin(function(){ if(jQuery(this).val() == 'mytemplate.php') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('mytemplate.php').addClass('templ33t_light'); })
			.val('mytemplate.php')
			.addClass('templ33t_light');

		jQuery('input.templ33t_main_label')
			.focusin(function(){ if(jQuery(this).val() == 'Main Content') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('Main Content').addClass('templ33t_light'); })
			.val('Main Content')
			.addClass('templ33t_light');

		jQuery('textarea.templ33t_main_description')
			.focusin(function(){ if(jQuery(this).val() == 'This is the main content section.') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('This is the main content section.').addClass('templ33t_light'); })
			.val('This is the main content section.')
			.addClass('templ33t_light');

		jQuery('input.templ33t_block')
			.focusin(function(){ if(jQuery(this).val() == 'My Custom Area') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('My Custom Area').addClass('templ33t_light'); })
			.val('My Custom Area')
			.addClass('templ33t_light');

		jQuery('textarea.templ33t_block_description')
			.focusin(function(){ if(jQuery(this).val() == 'Content entered here will be displayed in a custom block.') jQuery(this).val('').removeClass('templ33t_light'); })
			.focusout(function() { if(jQuery(this).val() == '') jQuery(this).val('Content entered here will be displayed in a custom block.').addClass('templ33t_light'); })
			.val('Content entered here will be displayed in a custom block.')
			.addClass('templ33t_light');

		
	}
);

function templ33tSwitchTab() {

	jQuery('li.templ33t_template_box').hide();
	jQuery('div#templ33t_control ul li').removeClass('selected');

	jQuery('li.templ33t_template_box[rel="'+jQuery(this).attr('rel')+'"]').show();
	jQuery(this).parent().addClass('selected');
	
}

function getBlockConfig() {
	
	jQuery.ajax(
		{
			url: ajaxurl,
			type: 'post',
			data: jQuery('form#templ33t_block_settings').serialize(),
			dataType: 'json',
			success: function(d) {
				
				jQuery('tbody#block-config').empty();
				jQuery('tbody#block-config').html(d.config);
				alert(d['default']);
			},
			error: function(d) {
				
				alert('There was an error: '+d.responseText);
				
			}
		}
	);
	
}