<?php

// grab selected template & block
$template = $wpdb->get_row(
		'SELECT `templ33t_template_id`, `template`, `config`
			FROM `' . $templates_table_name . '`
			WHERE `templ33t_template_id` = "' . $_GET['tid'] . '"', ARRAY_A
);

$template['config'] = unserialize($template['config']);

print_r($template);

?>


<h2>Main Block</h2>

<form id="templ33t_block_settings" action="" method="post">
	
	<input type="hidden" name="action" value="templ33t_main_block_config" />
	<input type="hidden" name="templ33t_block_config[theme]" value="<?php echo $theme_selected; ?>" />
	<input type="hidden" name="templ33t_block_config[tid]" value="<?php echo $_GET['tid']; ?>" />

	<table>
		<tbody>
			<tr>
				<td valign="top"><label for="label">Label:</label>&nbsp;</td>
				<td valign="top"><input type="text" id="label" name="templ33t_block_config[label]" value="<?php echo $template['config']['main']; ?>" /></td>
			</tr>
			<tr>
				<td valign="top"><label for="description">Description:</label>&nbsp;</td>
				<td valign="top"><textarea id="description" name="templ33t_block_config[description]"><?php echo $template['config']['description']; ?></textarea></td>
			</tr>
			<tr>
				<td valign="top"><label for="weight">Sort Order:</label>&nbsp;</td>
				<td valign="top"><input type="text" name="templ33t_block_config[main_weight]" value="<?php echo $template['config']['main_weight']; ?>" /></td>
			</tr>
		</tbody>
		<tbody>
			<tr>
				<td colspan="2" align="right">
					<a href="<?php echo self::$settings_url; ?>&theme=<?php echo $theme_selected; ?>">Cancel</a>
					&nbsp;&nbsp;
					<input type="submit" value="Save Changes" />
				</td>
			</tr>
		</tbody>
	</table>
	
</form>
