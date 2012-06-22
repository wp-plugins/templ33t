<?php

$messages = array(
	'saved' => 'Changes saved.',
);

?>

<style type="text/css">
	label {display: inline-block; padding-bottom: 6px; font-weight: bold;}
	div.postbox div.inside {padding: 12px;}
	.templ33t-customize-modal-bg {
		background: #303030;
		bottom: 0px;
		display: none;
		left: 0px;
		position: absolute;
		right: 0px;
		top: 0px;
		z-index: 100;
		-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";
		filter: alpha(opacity=50);
		-moz-opacity:0.5;
		-khtml-opacity: 0.5;
		opacity: 0.5;
	}
	.templ33t-customize-modal {
		background: #FFF;
		border: 1px solid #909090;
		-moz-border-radius: 9px;
		-webkit-border-radius: 9px;
		-khtml-border-radius: 9px;
		border-radius: 9px;
		-moz-box-shadow: 0px 0px 24px #000;
		-webkit-box-shadow: 0px 0px 24px #000;
		box-shadow: 0px 0px 24px #000;
		height: 400px;
		left: 50%;
		margin: -200px 0px 0px -250px;
		padding: 24px;
		position: absolute;
		top: 50%;
		width: 500px;
		z-index: 101;
	}
	.templ33t-customize-modal > div {
		height: 360px;
		overflow: auto;
	}
</style>

<script type="text/javascript">
	function tempCustomizeModal(slug) {
		
		jQuery('.templ33t-customize-modal').hide();
		jQuery('.templ33t-customize-modal-bg').show();
		jQuery('#'+slug).show();
		
	}
	function tempHideCustomizeModal() {
		
		jQuery('.templ33t-customize-modal-bg').hide();
		jQuery('.templ33t-customize-modal').hide();
		
	}
</script>

<div class="wrap">
	
	<div id="icon-themes" class="icon32"><br></div>
	<h2>Customize Theme</h2>

	<?php if(array_key_exists('m', $_GET)) { ?>
	<p style="background: greenyellow;"><?php echo $messages[$_GET['m']]; ?></p>
	<?php } ?>
	
	<br/>
	
	<?php if(!empty($groups)) { ?>
	
	<p>
		Your chosen theme has some customizable content areas that may be important. Please
		take a moment to set the default content for your theme by clicking the links below.
	</p>

	<?php foreach($groups as $group => $blocks) { ?>
	<h3><?php echo $group; ?></h3>

	<div>

		<ul style="margin-left: 18px;">
			<?php foreach($blocks as $slug => $block) { ?>
			<li><a href="javascript:tempCustomizeModal('<?php echo $slug; ?>');"><?php echo $block->label; ?></a></li>
			<?php } ?>
		</ul>

	</div>

	<?php } ?>
	
	<?php } else { ?>
	
	<p>No default content required for this theme.</p>
	
	<?php } ?>

</div>


<div class="templ33t-customize-modal-bg"></div>
	
	<?php foreach($groups as $group => $blocks) { foreach($blocks as $slug => $block) { ?>
	<div id="<?php echo $slug; ?>" class="templ33t-customize-modal" style="display: none;">
		<h2><?php echo $block->label; ?></h2>
		<div>
			<form method="post" action="?page=templ33t_customize">
				<p><?php echo $block->customize_page_description ? $block->customize_page_description : ($block->description ? $block->description : 'Please enter content below.'); ?></p>
				<div>
					<?php echo str_replace('meta[][value]', 'block[value]', str_replace('meta[][key]', 'block[slug]', str_replace('templ33t_', '', $block->displayPanel()))); ?>
				</div>
				<p>
					<input type="button" value="Cancel" onclick="tb_remove(); tempHideCustomizeModal();" />
					<input type="submit" value="Save" />
				</p>
			</form>
		</div>
	</div>
	<?php } } ?>
<script type="text/javascript">jQuery('.templ33t-customize-modal, .templ33t-customize-modal-bg').prependTo('body');</script>

