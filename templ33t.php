<?php
/*
Plugin Name: Templ33t
Plugin URI: http://www.totallyryan.com/projects/templ33t
Description: Add tabs for custom content blocks to the edit page with enhanced theme-specific configuration and smart template switching. This plugin is based on <a href="http://blog.page.ly/multiedit-plugin/">Page.ly MultiEdit</a>. <a href="http://www.totallyryan.com/projects/Templ33t">Templ33t Home Page</a>
Version: 0.1
Author: Ryan Willis
Author URI: http://www.totallyryan.com
*/

/*
/--------------------------------------------------------------------\
|                                                                    |
| License: GPL                                                       |
|                                                                    |
| Templ33t - Adds tabs to edit page for custom content blocks in     |
| WordPress page templates                                           |
| Copyright (C) 2010, Ryan Willis,                                   |
| http://www.totallyryan.com                                         |
| All rights reserved.                                               |
|                                                                    |
| This program is free software; you can redistribute it and/or      |
| modify it under the terms of the GNU General Public License        |
| as published by the Free Software Foundation; either version 2     |
| of the License, or (at your option) any later version.             |
|                                                                    |
| This program is distributed in the hope that it will be useful,    |
| but WITHOUT ANY WARRANTY; without even the implied warranty of     |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
| GNU General Public License for more details.                       |
|                                                                    |
| You should have received a copy of the GNU General Public License  |
| along with this program; if not, write to the                      |
| Free Software Foundation, Inc.                                     |
| 51 Franklin Street, Fifth Floor                                    |
| Boston, MA  02110-1301, USA                                        |
|                                                                    |
\--------------------------------------------------------------------/
*/

/**
 * Backward Compatible Paths
 */
if ( ! function_exists( 'is_ssl' ) ) {
	function is_ssl() {
		if ( isset($_SERVER['HTTPS']) ) {
			if ( 'on' == strtolower($_SERVER['HTTPS']) )
				return true;
			if ( '1' == $_SERVER['HTTPS'] )
				return true;
		} elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}
		return false;
	}
}
if ( version_compare( get_bloginfo( 'version' ) , '3.0' , '<' ) && is_ssl() ) {
	$wp_content_url = str_replace( 'http://' , 'https://' , get_option( 'siteurl' ) );
} else {
	$wp_content_url = get_option( 'siteurl' );
}
$wp_content_url .= '/wp-content';
$wp_content_dir = ABSPATH . 'wp-content';
$wp_plugin_url = $wp_content_url . '/plugins';
$wp_plugin_dir = $wp_content_dir . '/plugins';
$wpmu_plugin_url = $wp_content_url . '/mu-plugins';
$wpmu_plugin_dir = $wp_content_dir . '/mu-plugins';

/**
 * Paths to plugin assets
 */
define ('TEMPL33T_ASSETS_URL', $wp_content_url.'/plugins/templ33t/');
define ('TEMPL33T_ASSETS_DIR', $wp_content_dir.'/plugins/templ33t/');

/**
 * Require templ33t objects & interfaces
 */
require_once(TEMPL33T_ASSETS_DIR . 'templ33t_object.php');
require_once(TEMPL33T_ASSETS_DIR . 'plugs/templ33t_plugin_handler.php');
require_once(TEMPL33T_ASSETS_DIR . 'plugs/templ33t_plugin.php');
require_once(TEMPL33T_ASSETS_DIR . 'plugs/templ33t_tab_interface.php');
require_once(TEMPL33T_ASSETS_DIR . 'plugs/templ33t_option_interface.php');

/**
 * Create Templ33t Instance
 */
$templ33t = new Templ33t;

/**
 * Detect multisite
 */
global $templ33t_multisite;
$templ33t_multisite = (function_exists('is_multisite') && is_multisite());

/**
 * Whether to use site option or blog option
 */
global $templ33t_site_option;
$templ33t_site_option = function_exists('add_site_option');

/**
 * Pages to initialize tab functionality
 */
$templ33t_tab_pages = array('page.php', 'page-new.php', 'post.php', 'post-new.php');

/**
 * Array of templates and configured blocks (set by templ33t_init)
 */
$templ33t_templates = array();

/**
 * Array of templ33t related page options
 */
$templ33t_options = array();

/**
 * Array of templ33t related custom fields (set by templ33t_handle_meta)
 */
$templ33t_meta = array();

/**
 * Flags whether the meta has been fetched. Used to keep duplicate action calls
 * from fetching meta data multiple times.
 */
$templ33t_meta_fetched = false;

/**
 * Array of templ33t plugins to load for this request
 */
$templ33t_plugins = array();

/**
 * Boolean value checked before rendering templ33t scripts/elements
 * (set by templ33t_handle_meta)
 */
$templ33t_render = false;

/**
 * Content blocks available for output on page view. (set by first call of
 * templ33t_block)
 */
$templ33t_available = array();

/**
 * Array of error message strings
 */
$templ33t_errors = array(
	'theme' => 'Please choose a theme.',
	'template' => 'Please enter a template file name.',
	'mainlabel' => 'Please enter the main label for this template.',
	'block' => 'Please enter a custom block name.',
	'duptemp' => 'This template has already been added.',
	'dupblock' => 'This block already exists.',
	'notemp' => 'This template file does not exist in the chosen theme.',
	'noblock' => 'Invalid block.',
	'nopub' => 'The most recent configuration has already been published.',
	'noaction' => 'Invalid action.',
	'nochange' => 'No configuration changes detected.',
	'noconfig' => 'No Templ33t configuration detected for this template. The cached configuration can be removed.',
);

/**
 * Where to put the menu. Set by templ33t_init. Value depends on multisite status.
 */
$templeet_menu_parent;

/**
 * Url for templ33t settings page. Set by templ33t_init. Value depends on multisite
 * status
 */
$templ33t_settings_url;

// add install hook
register_activation_hook(__FILE__, 'templ33t_install');

// add uninstall hook
register_deactivation_hook(__FILE__, 'templ33t_uninstall');

// initialize plugin
add_action('admin_init', 'templ33t_init', 1);

// add settings page
add_action('admin_menu', 'templ33t_menu');

// add content filter for search results
add_filter('the_content', 'templ33t_content_filter', 1);

// prepare meta for page view
add_action('wp', array(&$templ33t, 'prepareMeta'), 1);


/**
 * Create options and tables
 */
function templ33t_install() {

	global $templ33t, $wpdb;
die('INSTALL');
	$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
	$template_table_name = $the_prefix . "templ33t_templates";
	$block_table_name = $the_prefix . "templ33t_blocks";

	if($wpdb->get_var("SHOW TABLES LIKE '$template_table_name'") != $template_table_name) {

		$sql = 'CREATE TABLE `'.$template_table_name.'` (
			`templ33t_template_id` int(11) NOT NULL AUTO_INCREMENT,
			`theme` varchar(50) DEFAULT NULL,
			`template` varchar(255) DEFAULT NULL,
			`config` text NULL,
			PRIMARY KEY  (`templ33t_template_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql);

		$templ33t->addOption('templ33t_db_version', Templ33t::$db_version);
		$templ33t->addOption('templ33t_map_pub', 0);
		$templ33t->addOption('templ33t_map_dev', 0);
		$templ33t->addOption('templ33t_map', serialize(array()));
		if($templ33t->multiSite()) $templ33t->addOption('templ33t_blogs', 1);

	} elseif($templ33t->multiSite()) {
		
		$count = $templ33t->getOption('templ33t_blogs');
		$count++;
		$templ33t->updateOption('templ33t_blogs', $count);

	}

	/*
	$installed_version = get_option( "templ33t_db_version" );

	if($installed_version != $templ33t_db_version) {

		$sql = 'CREATE TABLE `'.$template_table_name.'` (
			`templ33t_template_id` int(11) NOT NULL AUTO_INCREMENT,
			`theme` varchar(50) DEFAULT NULL,
			`template` varchar(255) DEFAULT NULL,
			`config` text NULL,
			PRIMARY KEY  (`templ33t_template_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql);

		templ33t_update_option('templ33t_db_version', $templ33t_db_version);

	}
	*/
}

/**
 * Remove plugin options and tables
 * @global object $wpdb
 */
function templ33t_uninstall() {

	global $templ33t, $wpdb;
die('UNINSTALL');
	// make sure uninstall only happens from last site
	$uninstall = true;

	if($templ33t_multisite) {
		$count = $templ33t->getOption('templ33t_blogs');
		if(($count - 1) > 0) $uninstall = false;
		$count--;
		$templ33t->updateOption('templ33t_blogs', $count);
	}

	if($uninstall) {
		
		// set table names
		$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
		$template_table_name = $the_prefix . "templ33t_templates";
		$block_table_name = $the_prefix . "templ33t_blocks";

		// drop blocks table
		$sql_blocks = 'DROP TABLE IF EXISTS `'.$block_table_name.'`;';
		$wpdb->query($sql_blocks);

		// drop templates table
		$sql_templates = 'DROP TABLE IF EXISTS `'.$template_table_name.'`;';
		$wpdb->query($sql_templates);

		// remove db version option
		$templ33t->deleteOption('templ33t_db_version');
		$templ33t->deleteOption('templ33t_map_pub');
		$templ33t->deleteOption('templ33t_map_dev');
		$templ33t->deleteOption('templ33t_map');
		if($templ33t->multisite()) $templ33t->deleteOption('templ33t_blogs');

	}

}

/**
 * Add templ33t menu item
 */
function templ33t_menu() {

	global $templ33t, $templ33t_menu_parent, $templ33t_settings_url, $wp_version;
	
	// set menu parent and settings url
	if(function_exists('is_multisite') && is_multisite()) {
		if($wp_version{0} < 3)
			$templ33t_menu_parent = 'wpmu-admin.php';
		else
			$templ33t_menu_parent = 'ms-admin.php';
		$templ33t_settings_url = $templ33t_menu_parent.'?page=templ33t_settings';
	} else {
		$templ33t_menu_parent = 'options-general.php';
		$templ33t_settings_url = $templ33t_menu_parent.'?page=templ33t_settings';
	}
	
	add_submenu_page($templ33t_menu_parent, 'Templ33t Settings', 'Templ33t', 'edit_themes', 'templ33t_settings', 'templ33t_settings');

}

/**
 * Loads theme-specific configuration file, records available templates and
 * their blocks.
 *
 * @global string $templ33t_file
 * @global SimpleXMLElement $templ33t_xml
 * @global array $templ33t_templates
 */
function templ33t_init() {

	global $templ33t, $templ33t_multisite, $templ33t_menu_parent, $templ33t_settings_url, $templ33t_tab_pages, $templ33t_templates, $user_ID, $wpdb, $wp_version;

	// register styles & scripts
	wp_register_style('templ33t_styles', TEMPL33T_ASSETS_URL.'templ33t.css');
	wp_register_script('templ33t_scripts', TEMPL33T_ASSETS_URL.'templ33t.js');
	wp_register_script('templ33t_settings_scripts', TEMPL33T_ASSETS_URL.'templ33t_settings.js');

	// check db version or create tables if no version
	$installed_version = $templ33t->getOption('templ33t_db_version');
	if($installed_version != Templ33t::$db_version) {
		templ33t_uninstall();
		templ33t_install();
	}

	// initialize tabs & content filters
	if(in_array(basename($_SERVER['PHP_SELF']), $templ33t_tab_pages)) {

		// add hooks
		//add_action('posts_selection', 'templ33t_handle_meta', 1);
		add_action('posts_selection', array(&$templ33t, 'prepareMeta'), 1);
		add_action('admin_print_styles', 'templ33t_styles', 1);
		add_action('admin_print_scripts', 'templ33t_scripts', 1);
		add_filter('the_editor_content', 'templ33t_strip_comment', 1);
		add_filter('content_save_pre', 'templ33t_add_comment', 10);
		add_action('edit_page_form', 'templ33t_elements', 1);

		if(!empty($_POST)) templ33t_option_save();

		// create new page with forced template if from_template passed
		if(in_array(basename($_SERVER['PHP_SELF']), array('page-new.php', 'post-new.php')) && array_key_exists('from_template', $_GET)) {

			$has_title = (array_key_exists('post_title', $_POST) && !empty($_POST['post_title']));
			$has_template = (array_key_exists('page_template', $_POST) && !empty($_POST['page_template']));

			if($has_title && $has_template) {

				// post data
				$insert = array(
					'post_type' => 'page',
					'post_title' => $_POST['post_title'],
					'post_content' => '',
					'post_status' => 'draft',
					'post_author' => $user_ID,
				);

				// save post as draft
				$new_post = wp_insert_post($insert);

				if($new_post > 0) {

					// set template
					update_post_meta($new_post, '_wp_page_template', $_POST['page_template']);

					// redirect to edit page
					$redirect_to = $wp_version{0} < 3 ? 'page.php?action=edit&post='.$new_post : 'post.php?action=edit&post='.$new_post;
					wp_redirect($redirect_to);

				}

			}

		}

		// grab theme name
		$theme = get_template();

		// grab template map
		$templ33t_map = unserialize($templ33t->getOption('templ33t_map'));

		// get theme map
		$templ33t_templates = array_key_exists($theme, $templ33t_map) ? $templ33t_map[$theme] : array();

	} elseif(basename($_SERVER['PHP_SELF']) == $templ33t_menu_parent && $_GET['page'] == 'templ33t_settings') {

		// add styles & scripts
		add_action('admin_print_styles', 'templ33t_settings_styles', 1);
		add_action('admin_print_scripts', 'templ33t_settings_scripts', 1);

		// handle settings page post
		templ33t_handle_settings();

	} elseif(basename($_SERVER['PHP_SELF']) == 'media-upload.php') {

		//add_filter( 'media_send_to_editor', 'templ33t_intercept_media', 15 );

	}

}

/**
 * Catch and act upon settings post & actions
 * @global object $wpdb
 */
function templ33t_handle_settings() {

	global $templ33t, $templ33t_menu_parent, $templ33t_settings_url, $wpdb, $wp_content_dir;

	$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
	$template_table_name = $the_prefix . 'templ33t_templates';
	$block_table_name = $the_prefix . 'templ33t_blocks';

	// catch settings via post
	if(!empty($_POST)) {

		// add template to theme
		if(array_key_exists('templ33t_new_template', $_POST)) {

			// required (non-empty) fields array
			$required = array(
				'templ33t_theme',
				'templ33t_template',
				//'templ33t_main_label',
			);

			$errors = array();

			// check and sterilize
			foreach($required as $field) {
				if(!array_key_exists($field, $_POST) || empty($_POST[$field])) {
					$errors[] = str_replace('templ33t_', '', $field);
				} else {
					$_POST[$field] = htmlspecialchars($_POST[$field], ENT_QUOTES);
				}
			}

			$tfile = $wp_content_dir . '/themes/' . $_POST['templ33t_theme'] . '/' . $_POST['templ33t_template'];
			
			if(!file_exists($tfile))
				$errors[] = 'notemp';

			if(!empty($errors)) {

				// redirect with errors
				$redirect = $templ33t_settings_url;
				if(array_key_exists('templ33t_theme', $_POST)) $redirect .= '&theme='.$_POST['templ33t_theme'];
				elseif(array_key_exists('theme', $_GET)) $redirect .= '&theme='.$_GET['theme'];
				$redirect .= '&error='.implode('|', $errors);
				wp_redirect($redirect);

			} else {

				// grab templ33t config
				$config = $templ33t->parseTemplate($tfile);

				// set up insert array
				$i_arr = array(
					'theme' => $_POST['templ33t_theme'],
					'template' => $_POST['templ33t_template'],
					'config' => serialize($config),
				);

				// check for duplicates
				$check = $wpdb->get_row('SELECT * FROM `'.$template_table_name.'` WHERE `theme` = "'.$i_arr['theme'].'" AND `template` = "'.$i_arr['template'].'" LIMIT 1', ARRAY_A);

				if(empty($check)) {

					// insert record
					$insert = $wpdb->insert(
						$template_table_name,
						$i_arr
					);


					/*
					$tid = $wpdb->get_var('SELECT LAST_INSERT_ID() FROM '.$template_table_name.' LIMIT 1');

					foreach($config['blocks'] as $key => $val) {

						// set up insert array
						$i_arr = array(
							'theme' => $_POST['templ33t_theme'],
							'template_id' => $tid,
							'block_name' => $val['title'],
							'block_slug' => $key,
							'block_type' => $val['type'],
							'block_description' => $val['description'],
						);

						// save block
						$insert = $wpdb->insert(
							$block_table_name,
							$i_arr
						);

					}
					*/
					// update map dev version
					$t_dev = $templ33t->getOption('templ33t_map_dev');
					$t_dev++;
					$templ33t->updateOption('templ33t_map_dev', $t_dev);

					// return to settings page
					$redirect = $templ33t_settings_url.'&theme='.$i_arr['theme'];
					wp_redirect($redirect);

				} else {

					// redirect with error
					$redirect = $templ33t_settings_url.'&theme='.$i_arr['theme'].'&error=duptemp';
					wp_redirect($redirect);

				}

			}

		// add block to template
		} elseif(array_key_exists('templ33t_new_block', $_POST)) {

			// required (non-empty) fields array
			$required = array(
				'templ33t_theme',
				'templ33t_template',
				'templ33t_block'
			);

			$errors = array();

			// check and sterilize
			foreach($required as $field) {
				if(!array_key_exists($field, $_POST) || empty($_POST[$field])) {
					$errors[] = str_replace('templ33t_', '', $field);
				} else {
					$_POST[$field] = htmlspecialchars($_POST[$field], ENT_QUOTES);
				}
			}

			if(!empty($errors)) {

				// redirect with errors
				$redirect = $templ33t_settings_url;
				if(array_key_exists('templ33t_theme', $_POST)) $redirect .= '&theme='.$_POST['templ33t_theme'];
				$redirect .= '&error='.implode('|', $errors);
				wp_redirect($redirect);

			} else {

				// set up insert array
				$i_arr = array(
					'theme' => $_POST['templ33t_theme'],
					'template_id' => $_POST['templ33t_template'],
					'block_name' => $_POST['templ33t_block'],
					'block_slug' => '',
					'block_description' => array_key_exists('templ33t_block_description', $_POST) ? htmlspecialchars($_POST['templ33t_block_description'], ENT_QUOTES) : '',
				);

				// remove template definition if set to all
				if($i_arr['template_id'] == 'ALL') unset($i_arr['template_id']);
				
				// generate slug
				$i_arr['block_slug'] = strtolower(str_replace(' ', '_', trim(chop(preg_replace('/([^a-z0-9]+)/i', ' ', $_POST['templ33t_block'])))));

				// set where conditions for template_id
				$t_where = '`template_id` IS NULL';
				if(array_key_exists('template_id', $i_arr))
					$t_where = '('.$t_where.' OR `template_id` = "'.$i_arr['template_id'].'")';
					
				// check for duplicates
				$check = $wpdb->get_row(
					'SELECT * FROM `'.$block_table_name.'`
					WHERE
						`theme` = "'.$i_arr['theme'].'"
						AND '.$t_where.'
						AND `block_slug` = "'.$i_arr['block_slug'].'"
					LIMIT 1',
					ARRAY_A
				);

				if(empty($check)) {

					// save block
					$insert = $wpdb->insert(
						$block_table_name,
						$i_arr
					);

					// update map dev version
					$t_dev = $templ33t->getOption('templ33t_map_dev');
					$t_dev++;
					$templ33t->updateOption('templ33t_map_dev', $t_dev);
					
					
					// return to settings page
					$redirect = $templ33t_settings_url.'&theme='.$_POST['templ33t_theme'];
					wp_redirect($redirect);

				} else {

					// redirect with error
					$redirect = $templ33t_settings_url.'&error=dupblock';
					wp_redirect($redirect);

				}

			}
		
		}
		
	}

	// catch actions sent via GET
	if(isset($_GET['t_action'])) {

		switch($_GET['t_action']) {

			// rescan template config
			case 'rescan':

				// grab template from db
				$temp = $wpdb->get_row('SELECT * FROM `'.$template_table_name.'` WHERE `templ33t_template_id` = "'.htmlspecialchars($_GET['tid'], ENT_QUOTES).'"', ARRAY_A);

				if(!empty($temp)) {

					$tfile = $wp_content_dir . '/themes/' . $temp['theme'] . '/' . $temp['template'];

					if(file_exists($tfile)) {

						// grab templ33t config
						$config = $templ33t->parseTemplate($tfile);

						if(!empty($config)) {

							if(unserialize($temp['config']) != $config) {

								// save changes
								$wpdb->update($template_table_name, array('config' => serialize($config)), array('templ33t_template_id' => $temp['templ33t_template_id']));

								// update map dev version
								$t_dev = $templ33t->getOption('templ33t_map_dev');
								$t_dev++;
								$templ33t->updateOption('templ33t_map_dev', $t_dev);

								// return to settings page
								$redirect = $templ33t_settings_url.'&theme='.$temp['theme'];
								wp_redirect($redirect);

							} else {

								// return to settings page
								$redirect = $templ33t_settings_url.'&theme='.$temp['theme'].'&error=nochange';
								wp_redirect($redirect);

							}

						} else {

							// return to settings page
							$redirect = $templ33t_settings_url.'&theme='.$temp['theme'].'&error=noconfig';
							wp_redirect($redirect);

						}

					} else {

						// return error if non-existent
						wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=notemp');

					}

				} else {

					// return error if non-existent
					wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=notemp');

				}

				break;

			// delete template
			case 'deltemp':

				// grab template
				$row = $wpdb->get_row('SELECT * FROM `'.$template_table_name.'` WHERE `templ33t_template_id` = "'.htmlspecialchars($_GET['tid'], ENT_QUOTES).'"', ARRAY_A);

				if(!empty($row)) {

					// delete if exists
					$sql_temp = 'DELETE FROM `'.$template_table_name.'` WHERE `templ33t_template_id` = '.$row['templ33t_template_id'].' LIMIT 1';
					$wpdb->query($sql_temp);

					$sql_block = 'DELETE FROM `'.$block_table_name.'` WHERE `template_id` = '.$row['templ33t_template_id'];
					$wpdb->query($sql_block);
					
					// update map dev version
					$t_dev = $templ33t->getOption('templ33t_map_dev');
					$t_dev++;
					$templ33t->updateOption('templ33t_map_dev', $t_dev);

					wp_redirect($templ33t_settings_url.'&theme='.$row['theme']);

				} else {

					// return error if non-existent
					wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=notemp');

				}

				break;

			// delete block
			case 'delblock':

				// grab block
				$row = $wpdb->get_row('SELECT * FROM `'.$block_table_name.'` WHERE `templ33t_block_id` = "'.htmlspecialchars($_GET['bid'], ENT_QUOTES).'"', ARRAY_A);

				if(!empty($row)) {

					// delete if exists
					$sql = 'DELETE FROM `'.$block_table_name.'` WHERE `templ33t_block_id` = '.$row['templ33t_block_id'].' LIMIT 1';
					$wpdb->query($sql);

					// update map dev version
					$t_dev = $templ33t->getOption('templ33t_map_dev');
					$t_dev++;
					$templ33t->updateOption('templ33t_map_dev', $t_dev);
					

					wp_redirect($templ33t_settings_url.'&theme='.$row['theme']);

				} else {

					// return error if non-existent
					wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=noblock');

				}

				break;

			// publish latest template map
			case 'publish':

				// get current configuration versions
				$pub = $templ33t->getOption('templ33t_map_pub');
				$dev = $templ33t->getOption('templ33t_map_dev');

				//if($pub < $dev) {


					$template_sql = 'SELECT `theme`, `template`, `config` FROM `'.$template_table_name.'`';

					// grab templates from the database
					$templates = $wpdb->get_results($template_sql, ARRAY_A);

					$templ33t_map = array();

					// map templates and blocks
					if(!empty($templates)) {
						foreach($templates as $tmp) {
							
							// add theme to map
							if(!array_key_exists($tmp['theme'], $templ33t_map))
								$templ33t_map[$tmp['theme']] = array();

							/*
							// fill out default data for theme-wide blocks
							if(empty($tmp['template'])) {
								$tmp['template'] = 'ALL';
								$tmp['main_label'] = 'Main Content';
								$tmp['main_description'] = '';
							}

							// add template to map
							if(!array_key_exists($tmp['template'], $templ33t_map[$tmp['theme']])) {
								$templ33t_map[$tmp['theme']][$tmp['template']] = array(
									'main' => $tmp['main_label'],
									'main_description' => $tmp['main_description'],
									'blocks' => array()
								);
							}

							// add block to map
							$templ33t_map[$tmp['theme']][$tmp['template']]['blocks'][$tmp['block_slug']] = array(
								'type' => $tmp['block_type'],
								'label' => $tmp['block_name'],
								'description' => $tmp['block_description']
							);
							*/

							// add template
							$templ33t_map[$tmp['theme']][$tmp['template']] = unserialize($tmp['config']);

						}
					}

					// save latest configuration version
					$templ33t->updateOption('templ33t_map', serialize($templ33t_map));
					$templ33t->updateOption('templ33t_map_pub', $dev);

					wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme']);

				//} else {

				//	wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=nopub');

				//}
				break;

			// return error on invalid action
			default:

				wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=noaction');
				break;

		}

	}

}

/**
 * Enqueue css for settings page
 */
function templ33t_settings_styles() {

	wp_enqueue_style('templ33t_styles');
	wp_enqueue_style('thickbox');

}

/**
 * Enqueue js for settings page
 */
function templ33t_settings_scripts() {

	wp_deregister_script('jquery');
	wp_register_script('jquery', 'http://code.jquery.com/jquery-1.4.2.min.js');
	wp_enqueue_script('thickbox');
	wp_enqueue_script('templ33t_settings_scripts', null, array('jquery'));

}

/**
 * Output Templ33t settings page.
 * @global object $wpdb
 * @global array $templ33t_errors
 */
function templ33t_settings() {

	global $templ33t, $templ33t_menu_parent, $templ33t_settings_url, $templ33t_errors, $wpdb;

	// get current configuration versions
	$pub = $templ33t->getOption('templ33t_map_pub');
	$dev = $templ33t->getOption('templ33t_map_dev');

	// set table names
	$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
	$templates_table_name = $the_prefix . 'templ33t_templates';
	$blocks_table_name = $the_prefix . 'templ33t_blocks';

	// grab theme list
	$themes = get_themes();

	// count themes
	$theme_count = count($themes);

	// select theme
	if(isset($_GET['theme']) && !empty($_GET['theme'])) {
		$theme_selected = htmlspecialchars($_GET['theme'], ENT_QUOTES);
	} else {
		$theme_selected = get_template();
	}

	/*
	// grab templates for selected theme
	$templates = $wpdb->get_results(
		'SELECT `templ33t_template_id`, `template`, `main_label`, `main_description`
		FROM `'.$templates_table_name.'`
		WHERE `theme` = "'.$theme_selected.'"',
		ARRAY_A
	);

	// grab blocks for theme
	$blocks = $wpdb->get_results(
		'SELECT *
		FROM `'.$blocks_table_name.'`
		WHERE `theme` = "'.$theme_selected.'"
		ORDER BY `block_name`',
		ARRAY_A
	);

	// map blocks to templates
	$block_map = array();
	if(!empty($blocks)) {
		foreach($blocks as $key => $val) {

			if(empty($val['template_id'])) $val['template_id'] = 'ALL';

			if(!array_key_exists($val['template_id'], $block_map)) {
				$block_map[$val['template_id']] = array($val['templ33t_block_id'] => $val);
			} else {
				$block_map[$val['template_id']][$val['templ33t_block_id']] = $val;
			}

		}
	}
	*/

	// grab templates for selected theme
	$templates = $wpdb->get_results(
		'SELECT `templ33t_template_id`, `template`, `config`
		FROM `'.$templates_table_name.'`
		WHERE `theme` = "'.$theme_selected.'"',
		ARRAY_A
	);

	// unserialize template config
	if(!empty($templates)) {
		foreach($templates as $key => $val) {
			$templates[$key]['config'] = unserialize($val['config']);
		}
	}

	//die(print_r($templates, true));
	
	// parse error message
	$error = null;
	if(isset($_GET['error'])) {
		if(strpos($_GET['error'], '|') !== false) {
			$error = explode('|', $_GET['error']);
			foreach($error as $key => $err) {
				$error[$key] = $templ33t_errors[$err];
			}
			$error = implode('<br/>', $error);
		} else {
			$error = $templ33t_errors[$_GET['error']];
		}
	}

	?>

	<h2>Templ33t Configuration</h2>

	<?php if($pub < $dev) { ?>
	<div id="templ33t_publish">
		<p>
			Your configuration has changed. Would you like to publish it for use?
			<a href="<?php echo $templ33t_settings_url.'&theme='.$theme_selected.'&t_action=publish'; ?>">Publish Configuration</a>
		</p>
	</div>
	<?php } ?>

	<?php if(!empty($error)) { ?>
	<p class="templ33t_error">
		<?php echo $error; ?>
	</p>
	<?php } ?>

	<br/>

	<div id="templ33t_settings">

		<div class="templ33t_themes">

			<ul>
				<?php $x = 1; foreach($themes as $key => $val) { ?>
				<li class="<?php if($theme_selected == $val['Template']) echo 'selected'; if($x == 1) echo ' first'; elseif($x == $theme_count) echo ' last'; ?>" rel="<?php echo $val['Template']; ?>">
					<a href="<?php echo $templ33t_settings_url; ?>&theme=<?php echo $val['Template']; ?>">
						<?php if(strlen($key) > 22) echo substr($key, 0, 22).'...'; else echo $key; ?>
					</a>
				</li>
				<?php $x++; } ?>
			</ul>

		</div>

		<div class="templ33t_blocks">

			<div>

				<div>

					<a href="#TB_inline?width=400&height=180&inlineId=templ33t_new_template_container&modal=true" class="thickbox">Add Template</a>

					<div id="templ33t_new_template_container" style="display: none; text-align: center;">
						<form id="templ33t_new_template" action="<?php echo $templ33t_settings_url; ?>" method="post">
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

					
					<?php if(!empty($templates)) { foreach($templates as $tkey => $tval) { ?>
					<li class="templ33t_template_box">
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

							<a href="<?php echo $templ33t_settings_url; ?>&t_action=rescan&tid=<?php echo $tval['templ33t_template_id']; ?>">Rescan Template Configuration</a>

						</div>
						<h2><?php echo $tval['template']; ?></h2>
						<hr/>

						<h4>Blocks</h4>

						<ul>
							<li>
								<strong><?php echo $tval['config']['main']; ?></strong> - main content label
								<hr/>
								<p><?php echo $tval['config']['description'] ? $tval['config']['description'] : 'No Description'; ?></p>
							</li>
							<?php if(!empty($tval['config']['blocks'])) { foreach($tval['config']['blocks'] as $bkey => $bval) { ?>
							<li>
								<div class="templ33t_right">
									Type: <?php echo $bval['type']; ?>
								</div>
								<!-- <a class="delblock" href="<?php echo $templ33t_settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=delblock&bid=<?php //echo $bval['templ33t_block_id']; ?>" onclick="return confirm('Are you sure you want to remove this custom block?');">[X]</a> -->
								<strong><?php echo $bval['label']; ?></strong> (<?php echo $bkey; ?>)<br/>
								<hr/>
								<p><?php echo $bval['description'] ? $bval['description'] : 'No Description'; ?></p>
							</li>
							<?php } } ?>
						</ul>
						<h4>Options</h4>
						<ul>
							<?php if(!empty($tval['config']['options'])) { foreach($tval['config']['options'] as $okey => $oval) { ?>
							<li>
								<div class="templ33t_right">
									Type: <?php echo $oval['type']; ?>
								</div>
								<strong><?php echo $oval['label']; ?></strong> (<?php echo $okey; ?>)<br/>
								<hr/>
								<p><?php echo $oval['description'] ? $oval['description'] : 'No Description'; ?></p>
							</li>
							<?php } } ?>
						</ul>
						<br/>
						<p class="templ33t_right">
							<a class="deltemp" href="<?php echo $templ33t_settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=deltemp&tid=<?php echo $tval['templ33t_template_id']; ?>" onclick="return confirm('Are you sure you want to remove this template and all content blocks associated with it?');">Remove This Template</a>
						</p>
						<div class="templ33t_clear_right"></div>
					</li>
					<?php } } ?>
				</ul>
				
				
			</div>

			<div class="templ33t_clear"></div>

		</div>

		<div class="templ33t_clear"></div>

	</div>

	<?php

}

/**
 * Grabs post custom fields, records templ33t related fields and adds any
 * missing fields.
 *
 * @global mixed $templ33t_meta
 * @global array $templ33t_templates
 * @global boolean $templ33t_render
 * @global object $post
 * @global string $table_prefix
 * @global object $wpdb
 */
/*
function templ33t_handle_meta() {

	global $templ33t, $templ33t_meta, $templ33t_options, $templ33t_templates, $templ33t_meta_fetched, $templ33t_plugins, $templ33t_render, $post, $wpdb;

	if(!$templ33t_meta_fetched) {

		$templ33t_meta_fetched = true;

		// grab meta
		$meta = has_meta($post->ID);

		if(!empty($meta)) {
			// filter out unrelated
			foreach($meta as $key => $val) {
				if(strpos($val['meta_key'], 'templ33t_option_') !== false) {
					$slug = str_replace('templ33t_option_', '', $val['meta_key']);
					$odata = array(
						'id' => $val['meta_id'],
						'type' => '',
						'label' => '',
						'value' => $val['meta_value']
					);
					$templ33t_options[$slug] = $odata;
				} elseif(strpos($val['meta_key'], 'templ33t_') !== false) {
					$slug = str_replace('templ33t_', '', $val['meta_key']);
					$bdata = array(
						'id' => $val['meta_id'],
						'type' => '',
						'label' => '',
						'value' => $val['meta_value']
					);
					$templ33t_meta[$slug] = $bdata;
				}
			}
		}

		// set default page filename
		if(empty($post->page_template) || $post->page_template == 'default') $post->page_template = basename(get_page_template());

		// set up plugins array
		$templ33t_plugins = array();

		// check for template definition, create any non-existent blocks, init block type plugins
		if(array_key_exists($post->page_template, $templ33t_templates)) {

			// check for template options
			if(!empty($templ33t_templates[$post->page_template]['options'])) {

				// flag
				$templ33t_render = true;

				// add missing custom fields
				foreach($templ33t_templates[$post->page_template]['options'] as $slug => $option) {
					if(!array_key_exists($slug, $templ33t_options)) {
						if(add_post_meta($post->ID, 'templ33t_option_'.$slug, '', true)) {
							$meta_id = $wpdb->get_col('SELECT LAST_INSERT_ID() as lid FROM `'.$wpdb->prefix.'postmeta` LIMIT 1');
							$templ33t_options[$slug] = array(
								'id' => $meta_id[0],
								'type' => $option['type'],
								'label' => $option['label'],
								'description' => $option['description'],
								'value' => '',
								'config' => $option['description']
							);
						}
					} else {
						$templ33t_option[$slug]['type'] = $option['type'];
						$templ33t_option[$slug]['config'] = $option['config'];
						$templ33t_option[$slug]['label'] = $option['label'];
						$templ33t_option[$slug]['description'] = $option['description'];
					}
				}

			}

			// check for template blocks
			if(!empty($templ33t_templates[$post->page_template]['blocks'])) {

				// flag
				$templ33t_render = true;

				// add missing custom fields
				foreach($templ33t_templates[$post->page_template]['blocks'] as $slug => $block) {
					if(!array_key_exists($slug, $templ33t_meta)) {
						if(add_post_meta($post->ID, 'templ33t_'.$slug, '', true)) {
							$meta_id = $wpdb->get_col('SELECT LAST_INSERT_ID() as lid FROM `'.$wpdb->prefix.'postmeta` LIMIT 1');
							$templ33t_meta[$slug] = array(
								'id' => $meta_id[0],
								'type' => $block['type'],
								'label' => $block['label'],
								'description' => $block['description'],
								'value' => '',
							);
						}
					} else {
						$templ33t_meta[$slug]['type'] = $block['type'];
						$templ33t_meta[$slug]['label'] = $block['label'];
						$templ33t_meta[$slug]['description'] = $block['description'];
					}
				}
				
				// init block plugins
				foreach($templ33t_templates[$post->page_template]['blocks'] as $slug => $block) {

					// create config array
					// instantiate plugin
					// pass configuration
					// init the plugin (which will add any shortcode actions)
					
					$config = array(
						'id' => $templ33t_meta[$slug]['id'],
						'value' => $templ33t_meta[$slug]['value'],
						'slug' => $slug,
						'label' => $block['label'],
						'description' => $block['description'],
						'config' => $block['config'],
					);

					$instance = Templ33tPluginHandler::instantiate($block['type'], $config);
					
					$instance->init();
					
					//$instance->slug = $slug;
					//$instance->label = $block['label'];
					//$instance->description = $block['description'];
					//$instance->id = $templ33t_meta[$slug]['id'];
					//$instance->value = $templ33t_meta[$slug]['value'];
					
					$templ33t_templates[$post->page_template]['blocks'][$slug]['instance'] = $instance;

				}

			}

		}
		
		// check for theme-wide definition and create any non-existent blocks

		if(!$templ33t_render) {

			// current template uses no tabs
			$templ33t_meta = false;

		}

	}

}
*/

function templ33t_option_save() {

	global $templ33t;
	
	if(array_key_exists($_POST['page_template'], $templ33t->map) && array_key_exists('templ33t_meta', $_POST)) {

		foreach($_POST['templ33t_meta'] as $slug => $data) {

			// handle blocks
			if(array_key_exists($slug, $templ33t->map[$_POST['page_template']]['blocks'])) {

				$block = $templ33t->map[$_POST['page_template']]['blocks'][$slug];
				$block['slug'] = $slug;
				$block['id'] = $data['id'];
				$block['value'] = $data['value'];

				$instance = Templ33tPluginHandler::instantiate($block['type'], $block);
				$instance->handlePost();
				$_POST['meta'][$instance->id] = array(
					'key' => 'templ33t_'.$instance->slug,
					'value' => $instance->value,
				);

				$templ33t->block_objects[$slug] = $instance;
				
			}

			// handle options
			if(array_key_exists($slug, $templ33t->map[$_POST['page_template']]['options'])) {

				$option = $templ33t->map[$_POST['page_template']]['options'][$slug];
				$option['slug'] = $slug;
				$option['id'] = $data['id'];
				$option['value'] = $data['value'];

				$instance = Templ33tPluginHandler::instantiate($option['type'], $option);
				$instance->handlePost();
				$_POST['meta'][$instance->id] = array(
					'key' => 'templ33t_option_'.$instance->slug,
					'value' => $instance->value,
				);

				$templ33t->option_objects[$slug] = $instance;

			}

		}

	}

}

/**
 * Append templ33t field content to main content in comment form for searchability.
 * @global array $templ33t_templates
 * @param string $content
 * @return string
 */
function templ33t_add_comment($content = null) {

	global $templ33t, $templ33t_templates, $wpdb, $post;

	if(array_key_exists('meta', $_POST) && !empty($_POST['meta'])) {

		// get template from post
		$template = array_key_exists('page_template', $_POST) && !empty($_POST['page_template']) ? $_POST['page_template'] : basename(get_page_template());
		if($template == 'default') $template = basename(get_page_template());

		/*
		$blocks = array();

		// grab template blocks
		if(array_key_exists($template, $templ33t_templates)) {
			foreach($templ33t_templates[$template]['blocks'] as $key => $val) {
				$blocks[] = 'templ33t_'.$key;
			}
		}

		// grab theme-wide blocks
		if(array_key_exists('ALL', $templ33t_templates)) {
			foreach($templ33t_templates['ALL']['blocks'] as $key => $val) {
				$blocks[] = 'templ33t_'.$key;
			}
		}

		// append custom block data to content in TEMPL33T comment
		if(!empty($blocks)) {

			$append = '<!-- TEMPL33T[ ';
			foreach($_POST['meta'] as $key => $val) {
				if(in_array($val['key'], $blocks)) {
					$append .= '  ::' . $val['key'] . ':: ' . preg_replace('/(<!--.+?-->)/mi', '', preg_replace('/([\r\n]+)/mi', ' ', $val['value']));
				}
			}
			$append .= ' ]END_TEMPL33T -->';

			$content .= '  '.$append;

		}
		*/

		// grab searchable block slugs
		$searchable = array();
		if(array_key_exists($template, $templ33t->map)) {
			foreach($templ33t->map[$template]['blocks'] as $slug => $block) {
				if($block['searchable']) $searchable[] = $slug;
			}
		}

		if(!empty($searchable)) {

			// grab meta ids
			$metadata = array();
			foreach($_POST['meta'] as $key => $data) {
				$metadata[$data['key']] = array('id' => $key, 'value' => $data['value']);
			}

			// strip existing comment
			$content = templ33t_strip_comment($content);

			// generate apend string
			$append = '<!-- TEMPL33T[ ';
			foreach($searchable as $slug) {

				if(!array_key_exists($slug, $templ33t->block_objects)) {

					$block = $templ33t->map[$template]['blocks'][$slug];
					$block['slug'] = $slug;
					$block['id'] = $metadata['templ33t_'.$slug]['id'];
					$block['value'] = $metadata['templ33t_'.$slug]['value'];

					$templ33t->block_objects[$slug] = Templ33tPluginHandler::instantiate($block['type'], $block);

				}
				
				//$append .= '  ::' . $slug . ':: ' . preg_replace('/(<!--.+?-->)/mi', '', preg_replace('/([\r\n]+)/mi', ' ', $templ33t->block_objects[$slug]->value));
				$append .= '  ::' . $slug . ':: ' . strip_tags(nl2br($templ33t->block_objects[$slug]->value));

			}
			$append .= ' ]END_TEMPL33T -->';

			$content .= '  '.$append;
			
		}

	}

	return $content;

}

/**
 * Remove templ33t field comment from end of page content before editing.
 * @param string $content
 * @return string
 */
function templ33t_strip_comment($content = null) {

	$content = preg_replace('/(<!--\ TEMPL33T\[.+?\]END\_TEMPL33T\ -->)/mi', '', $content);

	return $content;

}

/**
 * Transform templ33t fields comment into appended text for search results
 * @param string $content
 * @return string
 */
function templ33t_content_filter($content = null) {

	if(is_search()) {

		$content = preg_replace('/(::[A-Z0-9\_\-]+::)/i', ' ... ', preg_replace('/(<!--\ TEMPL33T\[)(.+?)(\]END\_TEMPL33T\ -->)/mi', '$2', $content));

	} elseif(is_page()) {

		$content = templ33t_strip_comment($content);
		
	}

	return $content;

}

/**
 * Enqueue Templ33t stylesheet
 */
function templ33t_styles() {
	wp_enqueue_style('templ33t_styles');
}

/**
 * Enqueue Templ33t tab scripts and generate js tab map object
 * @global object $post
 */
function templ33t_scripts() {

	global $templ33t, $templ33t_plugins, $post;

	wp_enqueue_script('templ33t_scripts');

	templ33t_js_obj();

	foreach($templ33t_plugins as $key => $val) {
		if($val::$load_js)
			wp_enqueue_script($val);
	}

}

/**
 * Outputs templ33t javascript template map object.
 *
 * @global array $templ33t_templates
 */
function templ33t_js_obj() {

	global $templ33t, $templ33t_templates, $templ33t_plugins, $post;

	echo '<script type="text/javascript">
		/* <![CDATA[ */
		var TL33T_current = { template: "'.$post->page_template.'" };
		';

	// output js template map
	if(!empty($templ33t_templates)) {
		
		$arr = array();
		foreach($templ33t_templates as $template => $config) {
			
			$blocks = array();

			foreach($config['blocks'] as $key => $val) {

				// grab class name, load unloaded classes
				$cname = Templ33tPluginHandler::load($val['type']);
				
				$blocks[] = '{label: "'.$val['label'].'", custom: '.(!empty($cname) ? ($cname::$custom_panel ? 'true' : 'false') : 'false').'}';
				
			}

			// add settings block
			if(!empty($config['options'])) {
				$blocks[] = '{label: "Settings", custom: true}';
			}

			$str = '"'.$template.'": {main: "'.htmlspecialchars($config['main'], ENT_QUOTES).'", blocks: ['
				.(!empty($blocks) ? implode(', ', $blocks) : '').']}';
			$arr[] = $str;
		}

		$defstr = 'var TL33T_def = {'.implode(', ', $arr).'}; ';
		echo $defstr;

	} else {

		echo 'var TL33T_def = {}; ';

	}

	echo '
		/* ]]> */
		</script>';

}

/**
 * Adds the tab bar to the edit page.
 *
 * @global array $templ33t_templates
 * @global mixed $templ33t_meta
 * @global boolean $templ33t_render
 * @global object $post
 */
function templ33t_elements() {

	global $templ33t, $templ33t_templates, $templ33t_options, $templ33t_meta, $templ33t_render, $post;

	if($templ33t->render) {

		// grab main label
		if(array_key_exists($post->page_template, $templ33t->map)) {
			$main_label = $templ33t->map[$post->page_template]['main'];
			$main_desc = $templ33t->map[$post->page_template]['description'];
		} elseif(array_key_exists('ALL', $templ33t->map)) {
			$main_label = $templ33t->map['ALL']['main'];
			$main_desc = $templ33t->map['ALL']['description'];
		} else {
			$main_label = 'Page Content';
			$main_desc = 'Enter your page content here.';
		}

		// set up item lists
		$tabs = '';
		$descs = '';
		$editors = '';

		// add default tab
		$tabs .= '<li id="templ33t_default" class="selected"><a href="#" rel="default">'.$main_label.'</a><div id="templ33t_main_content"></div></li>';
		$descs .= '<div class="templ33t_description templ33t_desc_default"><p>'.$main_desc.'</p></div>';


		if(!empty($templ33t->block_objects)) {
			//foreach($templ33t_templates[$post->page_template]['blocks'] as $slug => $block) {
			foreach($templ33t->block_objects as $slug => $block) {

				//$cname = Templ33tPluginHandler::load($block['type']);
				//$instance = $block['instance'];

				// get class name
				$cname = Templ33tPluginHandler::load($block->type);

				$tabs .= '<li><a href="#" rel="'.$block->id.'">'.$block->label.'</a></li>';
				$descs .= '<div class="templ33t_description templ33t_desc_'.$block->id.' templ33t_hidden"><p>'.$block->description.'</p></div>';

				if($cname::$custom_panel) {
					$editors .= '<div id="templ33t_editor_'.$block->id.'" class="templ33t_editor" style="display: none;">';
					$editors .= $block->displayPanel();
					$editors .= '</div>';
				} else {
					$editors .= '<div id="templ33t_editor_'.$block->id.'" class="templ33t_editor templ33t_hidden"><input type="hidden" name="meta['.$block->id.'][key]" value="templ33t_'.$block->slug.'" /><textarea id="templ33t_val_'.$block->id.'" name="meta['.$block->id.'][value]">'.$block->value.'</textarea></div>';
				}

			}
		}

		/*
		// grab theme-wide tabs
		if(array_key_exists('ALL', $templ33t_templates)) {
			foreach($templ33t_templates['ALL']['blocks'] as $slug => $block) {
				$tabs .= '<li><a href="#" rel="'.$templ33t_meta[$slug]['id'].'">'.$block['label'].'</a></li>';
				$descs .= '<div class="templ33t_description templ33t_desc_'.$templ33t_meta[$slug]['id'].' templ33t_hidden"><p>'.$block['description'].'</p></div>';
				$editors .= '<div id="templ33t_editor_'.$templ33t_meta[$slug]['id'].'" class="templ33t_editor">';
				if($block['type'] == 'editor') {
					$editors .= '<textarea rows="10" class="theEditor" cols="40" name="content" tabindex="2" id="content"></textarea>';
				} else {
					$editors .= '<p>THIS BLOCK IS A: '.$block['type'].'</p>';
				}
				$editors .'</div>';
			}
		}
		*/

		// add settings tab if exists
		if(!empty($templ33t->option_objects)) {
			
			$tabs .= '<li class="templ33t_settings"><a href="#" rel="settings">Settings</a></li>';
			$descs .= '<div class="templ33t_description templ33t_desc_settings templ33t_hidden"><p>These are the settings available for this template.</p></div>';
			$editors .= '<div id="templ33t_editor_settings" class="templ33t_editor postbox" style="display: none;"><div class="inside"><p class="templ33t_description">These are the settings available for this page.</p><table border="0">';
			foreach($templ33t->option_objects as $slug => $option) {
				/*
				switch($val['type']) {
					case 'percent':
						$editors .= '<tr><td>'.$val['label'].': </td><td>';
						$editors .= '<input type="hidden" name="meta['.$templ33t_options[$key]['id'].'][key]" value="templ33t_option_'.$key.'"><input type="text" name="meta['.$templ33t_options[$key]['id'].'][value]" value="'.$templ33t_options[$key]['value'].'" size="2" />%';
						break;
				}
				*/
				$editors .= $option->displayOption();
			}
			$editors .= '</table></div></div>';

		}

		// output tab bar & descriptions
		echo '<div id="templ33t_control" style="display: none;"><ul>';
		echo $tabs;
		echo '</ul></div><div id="templ33t_descriptions" style="display: none;">';
		echo $descs;
		echo '</div><div id="templ33t_editors" class="templ33t_editors" style="display: none;">';
		echo $editors;
		echo '</div>';
		
	}

}

/**
 * Intercepts media
 */
//function templ33t_intercept_media($html = null) {
//
//	$out =  '<script type="text/javascript">' . "\n" .
//			'	/* <![CDATA[ */' . "\n" .
//			'	var win = window.dialogArguments || opener || parent || top;' . "\n" .
//			'	win.send_to_templ33t_field("' . addslashes($html) . '");' . "\n" .
//			'/* ]]> */' . "\n" .
//			'</script>' . "\n";
//
//	echo $out;
//	exit();
//
//}

/**
 * Outputs a custom block within the template file.
 *
 * @global array $templ33t_available
 * @global object $post
 * @param string $block
 * @param string $before
 * @param string $after
 */
function templ33t_block($block = null, $before = null, $after = null) {

	global $templ33t, $templ33t_available, $templ33t_meta, $post;

	/*
	if(array_key_exists($block, $templ33t->block_objects)) {
		echo apply_filters('the_content', $before.$templ33t->block_objects[$block]->value.$after);
	}
	*/

	$value = array_key_exists($block, $templ33t->block_objects) ? $templ33t->block_objects[$block]->value : '';

	if(!empty($value) || $templ33t->block_objects[$block]->optional) {
		echo apply_filters('the_content', $before.$templ33t->block_objects[$block]->value.$after);
	}
	

}

function templ33t_option($option = null) {

	global $templ33t;

	if(array_key_exists($option, $templ33t->option_objects)) {
		return $templ33t->option_objects[$option]->value;
	}

}