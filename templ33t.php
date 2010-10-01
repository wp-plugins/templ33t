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
 * Url path to plugin assets
 */
define ('TEMPL33T_ASSETS', WP_PLUGIN_URL.'/templ33t/');

/**
 * Database version
 */
global $templ33t_db_version;
$templ33t_db_version = "0.2";

/**
 * Pages to initialize tab functionality
 */
$templ33t_tab_pages = array('page.php', 'page-new.php', 'post.php', 'post-new.php');

/**
 * Array of templates and configured blocks (set by templ33t_init)
 */
$templ33t_templates = array();

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

/**
 * Create options and tables
 */
function templ33t_install() {

	global $wpdb, $templ33t_db_version;

	$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
	$template_table_name = $the_prefix . "templ33t_templates";
	$block_table_name = $the_prefix . "templ33t_blocks";

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

		$sql = 'CREATE TABLE `'.$template_table_name.'` (
			`templ33t_template_id` int(11) NOT NULL AUTO_INCREMENT,
			`theme` varchar(50) DEFAULT NULL,
			`template` varchar(255) DEFAULT NULL,
			`main_label` varchar(255) DEFAULT NULL,
			PRIMARY KEY  (`templ33t_template_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;
			
			CREATE TABLE `'.$block_table_name.'` (
			`templ33t_block_id` int(11) NOT NULL AUTO_INCREMENT,
			`theme` varchar(255) DEFAULT NULL,
			`template_id` int(11) DEFAULT NULL,
			`block_name` varchar(30) DEFAULT NULL,
			`block_slug` varchar(30) DEFAULT NULL,
			PRIMARY KEY  (`templ33t_block_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql);

		if(function_exists('add_site_option')) {
			add_site_option('templ33t_db_version', $templ33t_db_version);
			add_site_option('templ33t_map_pub', 0);
			add_site_option('templ33t_map_dev', 0);
			add_site_option('templ33t_map', serialize(array()));
		} else {
			add_option('templ33t_db_version', $templ33t_db_version);
			add_option('templ33t_map_pub', 0);
			add_option('templ33t_map_dev', 0);
			add_option('templ33t_map', serialize(array()));
		}

	}

	/*
	$installed_version = get_option( "templ33t_db_version" );

	if($installed_version != $templ33t_db_version) {

		$sql_templates = 'CREATE TABLE `'.$template_table_name.'` (
			`templ33t_template_id` int(11) NOT NULL AUTO_INCREMENT,
			`theme` varchar(50) DEFAULT NULL,
			`template` varchar(255) DEFAULT NULL,
			`main_label` varchar(255) DEFAULT NULL,
			PRIMARY KEY (`templ33t_template_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1';

		$sql_blocks = 'CREATE TABLE `'.$block_table_name.'` (
			`templ33t_block_id` int(11) NOT NULL AUTO_INCREMENT,
			`theme` varchar(255) DEFAULT NULL,
			`template_id` int(11) DEFAULT NULL,
			`block_name` varchar(30) DEFAULT NULL,
			`block_slug` varchar(30) DEFAULT NULL,
			PRIMARY KEY (`templ33t_block_id`),
			KEY `FK_templ33t_blocks_templ33t_templates` (`template_id`),
			CONSTRAINT `FK_templ33t_blocks_templ33t_templates` FOREIGN KEY (`template_id`) REFERENCES `'.$template_table_name.'` (`templ33t_template_id`) ON DELETE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=latin1';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql_templates);
		dbDelta($sql_blocks);

		if(function_exists('update_site_option')) {
			update_site_option('templ33t_db_version', $templ33t_db_version);
		} else {
			update_option('templ33t_db_version', $templ33t_db_version);
		}

	}
	*/
}

/**
 * Remove plugin options and tables
 * @global object $wpdb
 */
function templ33t_uninstall() {

	global $wpdb;

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
	if(function_exists('delete_site_option')) {
		delete_site_option('templ33t_db_version');
		delete_site_option('templ33t_map_pub');
		delete_site_option('templ33t_map_dev');
		delete_site_option('templ33t_map');
	} else {
		delete_option('templ33t_db_version');
		delete_option('templ33t_map_pub');
		delete_option('templ33t_map_dev');
		delete_option('templ33t_map');
	}
	
}

/**
 * Add templ33t menu item
 */
function templ33t_menu() {

	global $templ33t_menu_parent, $templ33t_settings_url;

	// set menu parent and settings url
	if(function_exists('is_multisite') && is_multisite()) {
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

	global $templ33t_menu_parent, $templ33t_settings_url, $templ33t_db_version, $templ33t_tab_pages, $templ33t_templates, $wpdb;
	
	// register styles & scripts
	wp_register_style('templ33t_styles', TEMPL33T_ASSETS.'templ33t.css');
	wp_register_script('templ33t_scripts', TEMPL33T_ASSETS.'templ33t.js');
	wp_register_script('templ33t_settings_scripts', TEMPL33T_ASSETS.'templ33t_settings.js');

	// check db version or create tables if no version
	if(function_exists('get_site_option')) $installed_version = get_site_option("templ33t_db_version");
	else $installed_version = get_option('templ33t_db_version');
	if($installed_version != $templ33t_db_version) {
		templ33t_uninstall();
		templ33t_install();
	}

	// initialize tab
	if(in_array(basename($_SERVER['PHP_SELF']), $templ33t_tab_pages)) {

		// add hooks
		add_action('posts_selection', 'templ33t_handle_meta', 1);
		add_action('admin_print_styles', 'templ33t_styles', 1);
		add_action('admin_print_scripts', 'templ33t_scripts', 1);
		add_action('edit_page_form', 'templ33t_elements', 1);

		// set table names
		$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
		$template_table_name = $the_prefix.'templ33t_templates';
		$block_table_name = $the_prefix.'templ33t_blocks';

		// grab theme name
		$theme = get_template();

		if(function_exists('get_site_option')) {
			$templ33t_map = unserialize(get_site_option('templ33t_map'));
		} else {
			$templ33t_map = unserialize(get_option('templ33t_map'));
		}

		$templ33t_templates = array_key_exists($theme, $templ33t_map) ? $templ33t_map[$theme] : array();

	} elseif(basename($_SERVER['PHP_SELF']) == $templ33t_menu_parent && $_GET['page'] == 'templ33t_settings') {

		// add styles & scripts
		add_action('admin_print_styles', 'templ33t_styles', 1);
		add_action('admin_print_scripts', 'templ33t_settings_scripts', 1);

		// handle settings page post
		templ33t_handle_settings();

	}

}

/**
 * Catch and act upon settings post & actions
 * @global object $wpdb
 */
function templ33t_handle_settings() {

	global $templ33t_menu_parent, $templ33t_settings_url, $wpdb;

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
				'templ33t_main_label',
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

			if(!file_exists(WP_CONTENT_DIR . '/themes/' . $_POST['templ33t_theme'] . '/' . $_POST['templ33t_template']))
				$errors[] = 'notemp';

			if(!empty($errors)) {

				// redirect with errors
				$redirect = $templ33t_settings_url;
				if(array_key_exists('templ33t_theme', $_POST)) $redirect .= '&theme='.$_POST['templ33t_theme'];
				elseif(array_key_exists('theme', $_GET)) $redirect .= '&theme='.$_GET['theme'];
				$redirect .= '&error='.implode('|', $errors);
				wp_redirect($redirect);

			} else {

				// set up insert array
				$i_arr = array(
					'theme' => $_POST['templ33t_theme'],
					'template' => $_POST['templ33t_template'],
					'main_label' => $_POST['templ33t_main_label']
				);

				// check for duplicates
				$check = $wpdb->get_row('SELECT * FROM `'.$template_table_name.'` WHERE `theme` = "'.$i_arr['theme'].'" AND `template` = "'.$i_arr['template'].'" LIMIT 1', ARRAY_A);

				if(empty($check)) {

					// insert record
					$insert = $wpdb->insert(
						$template_table_name,
						$i_arr
					);

					// update map dev version
					if(function_exists('get_site_option')) {
						$t_dev = get_site_option('templ33t_map_dev');
						$t_dev++;
						update_site_option('templ33t_map_dev', $t_dev);
					} else {
						$t_dev = get_option('templ33t_map_dev');
						$t_dev++;
						update_option('templ33t_map_dev', $t_dev);
					}

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
					if(function_exists('get_site_option')) {
						$t_dev = get_site_option('templ33t_map_dev');
						$t_dev++;
						update_site_option('templ33t_map_dev', $t_dev);
					} else {
						$t_dev = get_option('templ33t_map_dev');
						$t_dev++;
						update_option('templ33t_map_dev', $t_dev);
					}
					
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
					if(function_exists('get_site_option')) {
						$t_dev = get_site_option('templ33t_map_dev');
						$t_dev++;
						update_site_option('templ33t_map_dev', $t_dev);
					} else {
						$t_dev = get_option('templ33t_map_dev');
						$t_dev++;
						update_option('templ33t_map_dev', $t_dev);
					}

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
					if(function_exists('get_site_option')) {
						$t_dev = get_site_option('templ33t_map_dev');
						$t_dev++;
						update_site_option('templ33t_map_dev', $t_dev);
					} else {
						$t_dev = get_option('templ33t_map_dev');
						$t_dev++;
						update_option('templ33t_map_dev', $t_dev);
					}

					wp_redirect($templ33t_settings_url.'&theme='.$row['theme']);

				} else {

					// return error if non-existent
					wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=noblock');

				}

				break;

			// publish latest template map
			case 'publish':

				// get current configuration versions
				if(function_exists('get_site_option')) {
					$pub = get_site_option('templ33t_map_pub');
					$dev = get_site_option('templ33t_map_dev');
				} else {
					$pub = get_option('templ33t_map_pub');
					$dev = get_option('templ33t_map_dev');
				}

				if($pub < $dev) {

					$template_sql = 'SELECT a.*, b.template, b.main_label
						FROM `'.$block_table_name.'` as a
						LEFT JOIN `'.$template_table_name.'` as b ON (a.template_id = b.templ33t_template_id)';

					// grab templates from the database
					$templates = $wpdb->get_results($template_sql, ARRAY_A);

					$templ33t_map = array();

					// map templates and blocks
					if(!empty($templates)) {
						foreach($templates as $tmp) {

							// add theme to map
							if(!array_key_exists($tmp['theme'], $templ33t_map))
								$templ33t_map[$tmp['theme']] = array();

							// fill out default data for theme-wide blocks
							if(empty($tmp['template'])) {
								$tmp['template'] = 'ALL';
								$tmp['main_label'] = 'Main Content';
							}

							// add template to map
							if(!array_key_exists($tmp['template'], $templ33t_map[$tmp['theme']]))
								$templ33t_map[$tmp['theme']][$tmp['template']] = array('main' => $tmp['main_label'], 'blocks' => array());

							// add block to map
							$templ33t_map[$tmp['theme']][$tmp['template']]['blocks'][$tmp['block_slug']] = $tmp['block_name'];

						}
					}

					// save latest configuration version
					if(function_exists('update_site_option')) {
						update_site_option('templ33t_map', serialize($templ33t_map));
						update_site_option('templ33t_map_pub', $dev);
					} else {
						update_option('templ33t_map', serialize($templ33t_map));
						update_option('templ33t_map_pub', $dev);
					}

					wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme']);

				} else {

					wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=nopub');

				}
				break;

			// return error on invalid action
			default:

				wp_redirect($templ33t_settings_url.'&theme='.$_GET['theme'].'&error=noaction');
				break;

		}

	}

}

/**
 * Enqueue js for settings page
 */
function templ33t_settings_scripts() {

	wp_enqueue_script('templ33t_settings_scripts', null, array('jquery'));

}

/**
 * Output Templ33t settings page.
 * @global object $wpdb
 * @global array $templ33t_errors
 */
function templ33t_settings() {

	global $templ33t_menu_parent, $templ33t_settings_url, $templ33t_errors, $wpdb;

	// get current configuration versions
	if(function_exists('get_site_option')) {
		$pub = get_site_option('templ33t_map_pub');
		$dev = get_site_option('templ33t_map_dev');
	} else {
		$pub = get_option('templ33t_map_pub');
		$dev = get_option('templ33t_map_dev');
	}

	// set table names
	$the_prefix = property_exists($wpdb, 'base_prefix') ? $wpdb->base_prefix : $wpdb->prefix;
	$templates_table_name = $the_prefix . 'templ33t_templates';
	$blocks_table_name = $the_prefix . 'templ33t_blocks';

	// grab theme list
	$themes = get_themes();

	// count themes
	$theme_count = count($themes);

	// select theme
	if(isset($_GET['theme'])) {
		$theme_selected = htmlspecialchars($_GET['theme'], ENT_QUOTES);
	} else {
		$theme_selected = get_template();
	}

	// grab templates for selected theme
	$templates = $wpdb->get_results(
		'SELECT `templ33t_template_id`, `template`, `main_label`
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
	foreach($blocks as $key => $val) {

		if(empty($val['template_id'])) $val['template_id'] = 'ALL';

		if(!array_key_exists($val['template_id'], $block_map)) {
			$block_map[$val['template_id']] = array($val['templ33t_block_id'] => $val);
		} else {
			$block_map[$val['template_id']][$val['templ33t_block_id']] = $val;
		}

	}

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

	<h2>Templ33t Block Settings</h2>

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
					<a href="<?php echo $templ33t_settings_url; ?>&theme=<?php echo $val['Template']; ?>"><?php echo $key; ?></a>
				</li>
				<?php $x++; } ?>
			</ul>

		</div>

		<div class="templ33t_blocks">

			<div>

				<div>
					<form id="templ33t_new_template" method="post">
						Add template: 
						<input type="hidden" name="templ33t_theme" value="<?php echo $theme_selected; ?>" />
						<input type="text" class="templ33t_template" name="templ33t_template" value="" size="30" />
						<input type="text" class="templ33t_main_label" name="templ33t_main_label" value="" size="30" />
						<input type="submit" name="templ33t_new_template" value="Add Template" />
					</form>
				</div>

				<hr/>

				<ul>
					<li class="templ33t_all_box">
						<div class="templ33t_right">
							<form id="templ33t_all_block" method="post">
								<input type="hidden" name="templ33t_theme" value="<?php echo $theme_selected; ?>" />
								<input type="hidden" name="templ33t_template" value="ALL" />
								<input type="text" class="templ33t_block" name="templ33t_block" value="" size="30" />
								<input type="submit" name="templ33t_new_block" value="Add Block" />
							</form>
						</div>
						<h2>Theme Wide / All Templates</h2>
						<hr/>
						<?php if(array_key_exists('ALL', $block_map) && !empty($block_map['ALL'])) { ?>
						<ul>
							<?php foreach($block_map['ALL'] as $bkey => $bval) { ?>
							<li>
								<?php echo $bval['block_name']; ?> (<?php echo $bval['block_slug']; ?>)
								<a class="delblock" href="<?php echo $templ33t_settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=delblock&bid=<?php echo $bval['templ33t_block_id']; ?>" onclick="return confirm('Are you sure you want to remove this custom block?');">[X]</a>
							</li>
							<?php } ?>
						</ul>
						<?php } else { ?>
						<p>No Content Blocks</p>
						<?php } ?>
					</li>
					<?php if(!empty($templates)) { foreach($templates as $tkey => $tval) { ?>
					<li class="templ33t_template_box">
						<div class="templ33t_right">
							<form id="templ33t_new_block" method="post">
								<input type="hidden" name="templ33t_theme" value="<?php echo $theme_selected; ?>" />
								<input type="hidden" name="templ33t_template" value="<?php echo $tval['templ33t_template_id']; ?>" />
								<input type="text" class="templ33t_block" name="templ33t_block" value="" size="30" />
								<input type="submit" name="templ33t_new_block" value="Add Block" />
							</form>
						</div>
						<h2><?php echo $tval['template']; ?></h2>
						<hr/>
						<ul>
							<li>
								<?php echo $tval['main_label']; ?> (main content label)
							</li>
							<?php if(array_key_exists($tval['templ33t_template_id'], $block_map) && !empty($block_map[$tval['templ33t_template_id']])) { foreach($block_map[$tval['templ33t_template_id']] as $bkey => $bval) { ?>
							<li>
								<?php echo $bval['block_name']; ?> (<?php echo $bval['block_slug']; ?>)
								<a class="delblock" href="<?php echo $templ33t_settings_url; ?>&theme=<?php echo $theme_selected; ?>&t_action=delblock&bid=<?php echo $bval['templ33t_block_id']; ?>" onclick="return confirm('Are you sure you want to remove this custom block?');">[X]</a>
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
function templ33t_handle_meta() {

	global $templ33t_meta, $templ33t_templates, $templ33t_meta_fetched, $templ33t_render, $post, $wpdb;

	if(!$templ33t_meta_fetched) {

		$templ33t_meta_fetched = true;

		// grab meta
		$meta = has_meta($post->ID);

		// filter out unrelated
		foreach($meta as $key => $val) {
			if(strpos($val['meta_key'], 'templ33t_') !== false) {
				$slug = str_replace('templ33t_', '', $val['meta_key']);
				$bdata = array(
					'id' => $val['meta_id'],
					'label' => '',
					'value' => $val['meta_value']
				);
				$templ33t_meta[$slug] = $bdata;
			}
		}

		// set default page filename
		if(empty($post->page_template) || $post->page_template == 'default') $post->page_template = 'page.php';

		// check for template definition and create any non-existent blocks
		if(array_key_exists($post->page_template, $templ33t_templates) && !empty($templ33t_templates[$post->page_template]['blocks'])) {

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
								'label' => $block,
								'value' => '',
							);
						}
					} else {
						$templ33t_meta[$slug]['label'] = $block;
					}
				}

			}

		}
		
		// check for theme-wide definition and create any non-existent blocks
		if(array_key_exists('ALL', $templ33t_templates) && !empty($templ33t_templates['ALL']['blocks'])) {
			
			// check for template blocks
			if(!empty($templ33t_templates['ALL']['blocks'])) {
			
				// flag
				$templ33t_render = true;

				// add missing custom fields
				foreach($templ33t_templates['ALL']['blocks'] as $slug => $block) {
					if(!array_key_exists($slug, $templ33t_meta)) {
						if(add_post_meta($post->ID, 'templ33t_'.$slug, '', true)) {
							$meta_id = $wpdb->get_col('SELECT LAST_INSERT_ID() as lid FROM `'.$wpdb->prefix.'postmeta` LIMIT 1');
							$templ33t_meta[$slug] = array(
								'id' => $meta_id[0],
								'label' => $block,
								'value' => '',
							);
						}
					} else {
						$templ33t_meta[$slug]['label'] = $block;
					}
				}
			
			}

		}

		if(!$templ33t_render) {

			// current template uses no tabs
			$templ33t_meta = false;

		}

	}

	

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

	global $post;

	wp_enqueue_script('templ33t_scripts');

	wp_localize_script('templ33t_scripts', 'TL33T_current', array('template' => $post->page_template));

	templ33t_js_obj();

}

/**
 * Outputs templ33t javascript template map object.
 *
 * @global array $templ33t_templates
 */
function templ33t_js_obj() {

	global $templ33t_templates;

	echo '<script type="text/javascript"> ';

	// output js template map
	if(!empty($templ33t_templates)) {

		$arr = array();
		foreach($templ33t_templates as $template => $config) {
			$str = '"'.$template.'": {main: "'.htmlspecialchars($config['main'], ENT_QUOTES).'", blocks: ['
				.(!empty($config['blocks']) ? '"'.implode('", "', $config['blocks']).'"' : '').']}';
			$arr[] = $str;
		}

		$defstr = 'var TL33T_def = {'.implode(', ', $arr).'}; ';
		echo $defstr;

	} else {

		echo 'var TL33T_def = {}; ';

	}

	echo '</script>';

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

	global $templ33t_templates, $templ33t_meta, $templ33t_render, $post;

	if($templ33t_render && !empty($templ33t_meta)) {

		// grab main label
		if(array_key_exists($post->page_template, $templ33t_templates))
			$main_label = $templ33t_templates[$post->page_template]['main'];
		else
			$main_label = $templ33t_templates['ALL']['main'];

		// output tab bar
		echo '<div id="templ33t_control" style="display: none;"><ul>';
		echo '<li id="templ33t_default" class="selected"><a href="" rel="default">'.$main_label.'</a><div id="templ33t_main_content"></div></li>';

		// output template specific tabs
		if(array_key_exists($post->page_template, $templ33t_templates)) {
			foreach($templ33t_templates[$post->page_template]['blocks'] as $slug => $block) {
				echo '<li><a href="#" rel="'.$templ33t_meta[$slug]['id'].'">'.$block.'</a></li>';
			}
		}

		// output theme-wide tabs
		if(array_key_exists('ALL', $templ33t_templates)) {
			foreach($templ33t_templates['ALL']['blocks'] as $slug => $block) {
				echo '<li><a href="#" rel="'.$templ33t_meta[$slug]['id'].'">'.$block.'</a></li>';
			}
		}

		// close tab bar
		echo '</ul></div>';
		
	}

}

/**
 * Outputs a custom block within the template file.
 *
 * @global array $templ33t_available
 * @global object $post
 * @param string $block
 */
function templ33t_block($block = null) {

	global $templ33t_available, $templ33t_meta, $post;

	// grab custom fields
	if(empty($templ33t_available))
		$templ33t_available = get_post_custom($post->ID);
	
	if(!is_array($templ33t_available)) $templ33t_available = array();

	// output block if exists
	if(array_key_exists('templ33t_'.$block, $templ33t_available)) {
		if(is_array($templ33t_available['templ33t_'.$block]))
			echo $templ33t_available['templ33t_'.$block][0];
		else
			$templ33t_available['templ33t_'.$block];
	}

}