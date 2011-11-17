<?php

// grab selected template & block
$template = $wpdb->get_row(
		'SELECT `templ33t_template_id`, `template`, `config`
			FROM `' . $templates_table_name . '`
			WHERE `templ33t_template_id` = "' . $_GET['tid'] . '"', ARRAY_A
);

$template['config'] = unserialize($template['config']);

foreach ($template['config']['blocks'] as $key => $val) {
	if ($key == $_GET['block']) {
		$block = $val;
		break;
	}
}

if (!isset($block)) {
	die('Invalid Block');
}

$obj = Templ33tPluginHandler::instantiate($block['type'], $block);
$obj->init();

?>

<h2>Block</h2>

<form id="templ33t_block_settings" action="" method="post">
	
	<input type="hidden" name="action" value="templ33t_block_config" />
	<input type="hidden" name="templ33t_block_config[theme]" value="<?php echo $theme_selected; ?>" />
	<input type="hidden" name="templ33t_block_config[tid]" value="<?php echo $_GET['tid']; ?>" />
	<input type="hidden" name="templ33t_block_config[slug]" value="<?php echo $block['slug']; ?>" />

	<table>
		<tbody>
			<tr>
				<td valign="top"><label for="">Template:</label>&nbsp;</td>
				<td valign="top"><?php echo $template['template']; ?></td>
			</tr>
			<tr>
				<td valign="top"><label for="">Slug:</label>&nbsp;</td>
				<td valign="top"><?php echo $block['slug']; ?></td>
			</tr>
			<tr>
				<td valign="top"><label for="label">Label:</label>&nbsp;</td>
				<td valign="top"><input type="text" id="label" name="templ33t_block_config[label]" value="<?php echo $block['label']; ?>" /></td>
			</tr>
			<tr>
				<td valign="top"><label for="description">Description:</label>&nbsp;</td>
				<td valign="top"><textarea id="description" name="templ33t_block_config[description]"><?php echo $block['description']; ?></textarea></td>
			</tr>
			<!--
			<tr>
				<td valign="top"><label for="global">Global:</label>&nbsp;</td>
				<td valign="top">
					<input type="hidden" name="templ33t_block_config[global]" value="0" />
					<input type="checkbox" id="global" name="templ33t_block_config[global]" value="1"<?php echo $block['global'] ? ' checked="checked"' : ''; ?> />
				</td>
			</tr>
			-->
			<tr>
				<td valign="top"><label for="customize_page">On Customize Page:</label>&nbsp;</td>
				<td valign="top">
					<input type="hidden" name="templ33t_block_config[customize_page]" value="0" />
					<input type="checkbox" id="customize_page" name="templ33t_block_config[customize_page]" value="1"<?php echo $block['customize_page'] ? ' checked="checked"' : ''; ?> />
				</td>
			</tr>
			<tr>
				<td valign="top"><label for="customize_page_group">Customize Page Group:</label>&nbsp;</td>
				<td valign="top"><input type="text" id="weight" name="templ33t_block_config[customize_page_group]" value="<?php echo $block['customize_page_group']; ?>" /></td>
			</tr>
			<tr>
				<td valign="top"><label for="customize_page_description">Customize Page Description:</label>&nbsp;</td>
				<td valign="top"><input type="text" id="weight" name="templ33t_block_config[customize_page_description]" value="<?php echo $block['customize_page_description']; ?>" /></td>
			</tr>
			<tr>
				<td valign="top"><label for="weight">Sort order:</label>&nbsp;</td>
				<td valign="top"><input type="text" id="weight" name="templ33t_block_config[weight]" value="<?php echo $block['weight']; ?>" /></td>
			</tr>
			<tr>
				<td valign="top"><label for="type">Type:</label>&nbsp;</td>
				<td valign="top">
					<select id="type" name="templ33t_block_config[type]" onChange="getBlockConfig();">
						<?php foreach ($this->load_plugs as $plug) { ?>
							<option value="<?php echo $plug; ?>"<?php if ($block['type'] == $plug) echo ' selected="selected"'; ?>><?php echo ucwords(preg_replace('[\_\-]', ' ', $plug)); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		</tbody>
		<tbody id="block-config">
			<?php echo $obj->displayConfig(); ?>
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