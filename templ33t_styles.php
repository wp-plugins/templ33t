<?php

// define paths
define('TEMPL33T_ASSETS_DIR', dirname(__FILE__).'/');

// require classes
require 'plugs/templ33t_plugin.php';
require 'plugs/templ33t_plugin_handler.php';
require 'plugs/templ33t_option_interface.php';
require 'plugs/templ33t_tab_interface.php';

// set headers
header('Content-Type: text/css; charset=UTF-8');
header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + 31536000 ) . ' GMT');
header("Cache-Control: public, max-age=31536000");

if(array_key_exists('load', $_GET) && !empty($_GET['load'])) {

	$plugins = explode(',', $_GET['load']);

	foreach($plugins as $plugin) {

		$obj = Templ33tPluginHandler::instantiate($plugin);
		$obj->style(false, false);

	}

}

?>
