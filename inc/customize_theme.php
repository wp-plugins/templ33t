<?php

?>

<h1>Customize Theme</h1>

<p>
	Your chosen theme has some customizable content areas that may be important. Please
	take a moment to set the default content for your theme now.
</p>

<?php foreach($groups as $group => $blocks) { ?>
<h3><?php echo $group; ?></h3>

<div>
	
	<ul>
		<?php foreach($blocks as $slug => $block) { ?>
		<li><a href="#TB_inline?width=400&inlineId=<?php echo $slug; ?>&modal=true" class="thickbox"><?php echo $block->label; ?></a></li>
		<?php } ?>
	</ul>
	
</div>

<?php } ?>


	
<?php foreach($groups as $group => $blocks) { foreach($blocks as $slug => $block) { ?>
<div id="<?php echo $slug; ?>" style="display: none;">
	<h3><?php echo $block->label; ?></h3>
	<p><?php echo $block->customize_page_description ? $block->customize_page_description : ($block->description ? $block->description : 'Please enter content below.'); ?></p>
	<div>
		<?php echo $block->displayPanel(); ?>
	</div>
</div>
<?php } } ?>

