<?php
// grab templates for selected theme
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

$plug_path = realpath(Templ33t::$assets_dir . 'plugs');
$ignore = array('.', '..', '.svn');
$plugs = scandir($plug_path);
foreach ($plugs as $key => $dir) {
	if (in_array($dir, $ignore) || !is_dir($plug_path . '/' . $dir)) {
		unset($plugs[$key]);
	} else {
		$class = Templ33tPluginHandler::load($dir);
		//echo $dir . ' - ' . ($class === false ? 'FALSE' : $class) . '<br/>';
		$obj = new $class;
		if(!($obj instanceOf Templ33tTab)) {
			unset($plugs[$key]);
		}
	}
}

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
			<tr>
				<td valign="top"><label for="type">Type:</label>&nbsp;</td>
				<td valign="top">
					<select id="type" name="templ33t_block_config[type]" onChange="getBlockConfig();">
						<?php foreach ($plugs as $plug) { ?>
							<option value="<?php echo $plug; ?>"<?php if ($block['type'] == $plug) echo ' selected="selected"'; ?>><?php echo ucwords(preg_replace('[\_\-]', ' ', $plug)); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		</tbody>
		<tbody id="block-config">

		</tbody>
		<tbody>
			<tr>
				<td valign="top"><label for="">Default:</label>&nbsp;</td>
				<td id="block-default" valign="top">

				</td>
			</tr>
			<tr>
				<td valign="top"><label for="optional">Optional:</label>&nbsp;</td>
				<td valign="top">
					<input type="hidden" name="templ33t_block_config[optional]" value="0" />
					<input type="checkbox" id="optional" name="templ33t_block_config[optional]" value="1"<?php if ($block['optional'])
							echo ' checked="checked"'; ?> />
				</td>
			</tr>
			<tr>
				<td valign="top"><label for="bindable">Bindable:</label>&nbsp;</td>
				<td valign="top">
					<input type="hidden" name="templ33t_block_config[bindable]" value="0" />
					<input type="checkbox" id="bindable" name="templ33t_block_config[bindable]" value="1"<?php if ($block['bindable'])
							echo ' checked="checked"'; ?> />
				</td>
			</tr>
			<tr>
				<td valign="top"><label for="searchable">Searchable:</label>&nbsp;</td>
				<td valign="top">
					<input type="hidden" name="templ33t_block_config[searchable]" value="0" />
					<input type="checkbox" id="searchable" name="templ33t_block_config[searchable]" value="1"<?php if ($block['searchable'])
							echo ' checked="checked"'; ?> />
				</td>
			</tr>
			<tr>
				<td colspan="2" align="right">
					<a href="<?php echo self::$settings_url; ?>&theme=<?php echo $theme_selected; ?>">Cancel</a>
					&nbsp;&nbsp;
					<input type="submit" value="Save Changes" />
				</td>
			</tr>
		</tbody>
	</table>