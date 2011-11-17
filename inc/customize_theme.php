<?php

?>

<h1>Customize Theme</h1>

<p>
	Your chosen theme has some customizable content areas that may be important. Please
	take a moment to set the default content for your theme now.
</p>

<?php foreach($groups as $group => $blocks) { ?>
<h3><?php echo $group; ?></h3>

<div class="">
	
	<ul>
		<?php foreach($blocks as $slug => $config) { ?>
		<li><a href=""><?php echo $config['label']; ?></a></li>
		<?php } ?>
	</ul>
	
</div>
	
<?php } ?>
