<?php

$messages = array(
	'saved' => 'Changes saved.',
);

?>

<div class="wrap">
	
	<div id="icon-themes" class="icon32"><br></div>
	<h2>Customize Theme</h2>

	<?php if(array_key_exists('m', $_GET)) { ?>
	<p style="background: greenyellow;"><?php echo $messages[$_GET['m']]; ?></p>
	<?php } ?>

	<p>
		Your chosen theme has some customizable content areas that may be important. Please
		take a moment to set the default content for your theme now.
	</p>

	<?php foreach($groups as $group => $blocks) { ?>
	<h3><?php echo $group; ?></h3>

	<div>

		<ul>
			<?php foreach($blocks as $slug => $block) { ?>
			<li><a href="#TB_inline?width=400&height=400&inlineId=<?php echo $slug; ?>&modal=true" class="thickbox"><?php echo $block->label; ?></a></li>
			<?php } ?>
		</ul>

	</div>

	<?php } ?>



	<?php foreach($groups as $group => $blocks) { foreach($blocks as $slug => $block) { ?>
	<div id="<?php echo $slug; ?>" style="display: none;">
		<form method="post" action="?page=templ33t_customize">
			<h3><?php echo $block->label; ?></h3>
			<p><?php echo $block->customize_page_description ? $block->customize_page_description : ($block->description ? $block->description : 'Please enter content below.'); ?></p>
			<div>
				<?php echo str_replace('meta[][value]', 'block[value]', str_replace('meta[][key]', 'block[slug]', str_replace('templ33t_', '', $block->displayPanel()))); ?>
			</div>
			<p>
				<input type="button" value="Cancel" onclick="tb_remove(); return false;" />
				<input type="submit" value="Save" />
			</p>
		</form>
	</div>
	<?php } } ?>

</div>

