<?php

// grab templates for selected theme
$templates = $wpdb->get_results(
		'SELECT `templ33t_template_id`, `template`, `config`
			FROM `' . $templates_table_name . '`
			WHERE `theme` = "' . $theme_selected . '"', ARRAY_A
);

// unserialize template config & separate global blocks
$all = array();
if (!empty($templates)) {
	foreach ($templates as $key => $val) {
		//if($val['template'] == 'ALL') {
		//	$val['config'] = unserialize($val['config']);
		//	$all[$key] = $val;
		//	unset($templates[$key]);
		//} else {
		$templates[$key]['config'] = unserialize($val['config']);
		//}
	}
}

if(array_key_exists('template', $_GET)) {
	$passed_template = $_GET['template'];
}

?>
<h2>Templ33t Configuration</h2>

<?php if ($pub < $dev) { ?>
	<div id="templ33t_publish">
		<p>
			Your configuration has changed. Would you like to publish it for use?
			<a href="<?php echo self::$settings_url . '&theme=' . $theme_selected . '&t_action=publish'; ?>">Publish Configuration</a>
			or
			<a class="reset" href="<?php echo self::$settings_url . '&theme=' . $theme_selected . '&t_action=reset'; ?>">Reset Configuration</a>
		</p>
	</div>
<?php } ?>

<?php if (!empty($error)) { ?>
	<p class="templ33t_error">
	<?php echo $error; ?>
	</p>
	<?php } ?>

<br/>

<div id="templ33t_settings">

	<div class="templ33t_themes">

		<ul>
<?php $x = 1;
foreach ($themes as $key => $val) { ?>
				<li class="<?php if ($theme_selected == $val['Stylesheet'])
		echo 'selected'; if ($x == 1)
		echo ' first'; elseif ($x == $theme_count)
		echo ' last'; ?>" rel="<?php echo $val['Stylesheet']; ?>">
					<a href="<?php echo self::$settings_url; ?>&theme=<?php echo $val['Stylesheet']; ?>">
	<?php if (strlen($key) > 22)
		echo substr($key, 0, 22) . '...'; else
		echo $key; ?>
					</a>
				</li>
	<?php $x++;
} ?>
		</ul>

	</div>

	<div class="templ33t_blocks">

		<div>

			<div>

				<a href="#TB_inline?width=400&height=180&inlineId=templ33t_new_template_container&modal=true" class="thickbox">Add Template</a> |
				<a href="<?php echo self::$settings_url; ?>&t_action=scan&theme=<?php echo $theme_selected; ?>">Scan All Templates</a>

				<div id="templ33t_new_template_container" style="display: none; text-align: center;">
					<form id="templ33t_new_template" action="<?php echo self::$settings_url; ?>" method="post">
						<input type="hidden" name="templ33t_theme" value="<?php echo $theme_selected; ?>" />

						<h2>Scan a Template File</h2>

						<br/>

						<table border="0" width="100%">
							<tr>
								<td><label for="templ33t_template">Template File Name: </label></td>
								<td><input type="text" class="templ33t_template" name="templ33t_template" value="" size="30" /></td>
							</tr>
							<tr>
								<td colspan="2" align="center">
									<br/>
									<input type="button" value="Cancel" onclick="tb_remove(); return false;" />
									<input type="submit" name="templ33t_new_template" value="Add Template" />
								</td>
							</tr>
						</table>

					</form>
				</div>

			</div>

			<hr/>


			<!-- template navigation -->
					<?php if (!empty($templates)) { ?>
				<div id="templ33t_control">
					<ul>
				<?php
				
				
				
				$x = 0;
				foreach ($templates as $tkey => $tval) { ?>
							<li<?php if ((!isset($passed_template) && $x == 0) || ($passed_template == $tval['template'])) { ?> class="selected"<?php } ?>><a href="#" rel="<?php echo $tval['templ33t_template_id']; ?>"><?php echo $tval['template']; ?></a></li>
					<?php $x++;
				} ?>
					</ul>
				</div>
				<?php } ?>


			<ul>
				<?php /*
				  <li class="templ33t_all_box">
				  <div class="templ33t_right">


				  <a href="#TB_inline?width=400&height=220&inlineId=templ33t_all_block_container&modal=true" class="thickbox">Add Content Block</a>

				  <div id="templ33t_all_block_container" style="display: none; text-align: center;">
				  <form id="templ33t_new_template" action="<?php echo $templ33t_settings_url; ?>" method="post">
				  <input type="hidden" name="templ33t_theme" value="<?php echo $theme_selected; ?>" />
				  <input type="hidden" name="templ33t_template" value="ALL" />

				  <h2>Add a Theme-Wide Content Block</h2>

				  <p>This content block will be available to all templates within this theme.</p>

				  <table border="0" width="100%">
				  <tr>
				  <td><label for="templ33t_main_label">Content Block Label: </label></td>
				  <td><input type="text" class="templ33t_block" name="templ33t_block" value="" size="30" /></td>
				  </tr>
				  <tr>
				  <td valign="top"><label for="templ33t_block_description">Content Block Description: </label></td>
				  <td><textarea class="templ33t_block_description" name="templ33t_block_description"></textarea></td>
				  </tr>
				  <tr>
				  <td colspan="2" align="center">
				  <br/>
				  <input type="button" value="Cancel" onclick="tb_remove(); return false;" />
				  <input type="submit" name="templ33t_new_block" value="Add Block" />
				  </td>
				  </tr>
				  </table>

				  </form>
				  </div>

				  </div>

				  <h2>Theme Wide / All Templates</h2>
				  <hr/>
				  <?php if(array_key_exists('ALL', $block_map) && !empty($block_map['ALL'])) { ?>
				  <ul>
				  <?php foreach($block_map['ALL'] as $bkey => $bval) { ?>
				  <li>
				  <a class="delblock" href="<?php echo $templ33t_settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=delblock&bid=<?php echo $bval['templ33t_block_id']; ?>" onclick="return confirm('Are you sure you want to remove this custom block?');">[X]</a>
				  <strong><?php echo $bval['block_name']; ?></strong> (<?php echo $bval['block_slug']; ?>)<br/>
				  <hr/>
				  <p><?php echo $bval['block_description'] ? $bval['block_description'] : 'No Description'; ?></p>
				  </li>
				  <?php } ?>
				  </ul>
				  <?php } else { ?>
				  <p>No Content Blocks</p>
				  <?php } ?>
				  </li>
				 */ ?>







<?php if (!empty($templates)) {
	$x = 0;
	foreach ($templates as $tkey => $tval) { ?>
						<li class="templ33t_template_box" rel="<?php echo $tval['templ33t_template_id']; ?>" <?php if ((!isset($passed_template) && $x > 0) || (isset($passed_template) && $passed_template != $tval['template'])) { ?> style="display: none;"<?php } ?>>
							<div class="templ33t_right">


								<!--
								<a href="#TB_inline?width=400&height=220&inlineId=templ33t_new_block_container_<?php echo $tval['templ33t_template_id']; ?>&modal=true" class="thickbox">Add Content Block</a>

								<div id="templ33t_new_block_container_<?php echo $tval['templ33t_template_id']; ?>" style="display: none; text-align: center;">
									<form id="templ33t_new_template" action="<?php echo $templ33t_settings_url; ?>" method="post">
										<input type="hidden" name="templ33t_theme" value="<?php echo $theme_selected; ?>" />
										<input type="hidden" name="templ33t_template" value="<?php echo $tval['templ33t_template_id']; ?>" />

										<h2>Add a Theme-Wide Content Block</h2>

										<p>This content block will be available to all templates within this theme.</p>

										<table border="0" width="100%">
											<tr>
												<td><label for="templ33t_main_label">Content Block Label: </label></td>
												<td><input type="text" class="templ33t_block" name="templ33t_block" value="" size="30" /></td>
											</tr>
											<tr>
												<td valign="top"><label for="templ33t_block_description">Content Block Description: </label></td>
												<td><textarea class="templ33t_block_description" name="templ33t_block_description"></textarea></td>
											</tr>
											<tr>
												<td colspan="2" align="center">
													<br/>
													<input type="button" value="Cancel" onclick="tb_remove(); return false;" />
													<input type="submit" name="templ33t_new_block" value="Add Block" />
												</td>
											</tr>
										</table>

									</form>
								</div>
								-->

		<?php if ($tval['template'] != 'ALL') { ?> <a href="<?php echo self::$settings_url; ?>&t_action=rescan&tid=<?php echo $tval['templ33t_template_id']; ?>">Rescan Template Configuration</a><?php } ?>

							</div>
							<h2><?php echo $tval['template']; ?></h2>
							<hr/>

							<h4>Blocks</h4>

							<ul>
								<li>
									<div class="templ33t_right">
										<a href="<?php echo self::$settings_url; ?>&subpage=main_block&theme=<?php echo $theme_selected; ?>&tid=<?php echo $tval['templ33t_template_id']; ?>">Edit Main Tab</a>
									</div>
									<strong><?php echo $tval['config']['main']; ?></strong> - main content
									<hr/>
									<p><?php echo $tval['config']['description'] ? $tval['config']['description'] : 'No Description'; ?></p>
								</li>
		<?php if (!empty($tval['config']['blocks'])) {
			foreach ($tval['config']['blocks'] as $bkey => $bval) { ?>
										<li>
											<div class="templ33t_right">
												Type: <?php echo $bval['type']; ?>
												<a href="<?php echo self::$settings_url; ?>&subpage=block&theme=<?php echo $theme_selected; ?>&tid=<?php echo $tval['templ33t_template_id']; ?>&block=<?php echo $bval['slug']; ?>">Edit Block</a>
											</div>
											<!-- <a class="delblock" href="<?php echo self::$settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=delblock&bid=<?php //echo $bval['templ33t_block_id']; ?>" onclick="return confirm('Are you sure you want to remove this custom block?');">[X]</a> -->
											<strong><?php echo $bval['label']; ?></strong> (<?php echo $bkey; ?>)<br/>
											<hr/>
											<p><?php echo $bval['description'] ? $bval['description'] : 'No Description'; ?></p>
										</li>
			<?php }
		} ?>
							</ul>
							<h4>Options</h4>
							<ul>
		<?php if (!empty($tval['config']['options'])) {
			foreach ($tval['config']['options'] as $okey => $oval) { ?>
										<li>
											<div class="templ33t_right">
												Type: <?php echo $oval['type']; ?>
											</div>
											<strong><?php echo $oval['label']; ?></strong> (<?php echo $okey; ?>)<br/>
											<hr/>
											<p><?php echo $oval['description'] ? $oval['description'] : 'No Description'; ?></p>
										</li>
			<?php }
		} ?>
							</ul>
							<br/>
							<p class="templ33t_right">
								<a class="deltemp" href="<?php echo self::$settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=deltemp&tid=<?php echo $tval['templ33t_template_id']; ?>" onclick="return confirm('Are you sure you want to remove this template and all content blocks associated with it?');">Remove This Template</a>
							</p>
							<div class="templ33t_clear_right"></div>
						</li>
		<?php $x++;
	}
} ?>
			</ul>


		</div>

		<div class="templ33t_clear"></div>

	</div>

	<div class="templ33t_clear"></div>

</div>