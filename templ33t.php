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
 * Theme-specific templ33t configuration file (set by templ33t_init)
 */
$templ33t_file = null;

/**
 * Parsed theme-specific configuration file in SimpleXML object form (set by
 * templ33t_init)
 */
$templ33t_xml = null;

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
	'block' => 'Please enter a custom block name.',
	'duplicate' => 'This block already exists.',
	'noblock' => 'Invalid block.',
	'noaction' => 'Invalid action.',
);

// add install hook
register_activation_hook(__FILE__, 'templ33t_install');

// add uninstall hook
register_deactivation_hook(__FILE__, 'templ33t_uninstall');

// initialize plugin
add_action('admin_init', 'templ33t_init', 1);

// add settings page
add_action('admin_menu', 'templ33t_menu');

/**
 * Create db tables
 */
function templ33t_install() {

	global $wpdb, $templ33t_db_version;

	$template_table_name = $wpdb->prefix . "templ33t_templates";
	$block_table_name = $wpdb->prefix . "templ33t_blocks";

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

		$sql_templates = 'CREATE TABLE `'.$template_table_name.'` (
			`templ33t_template_id` int(11) NOT NULL AUTO_INCREMENT,
			`theme` varchar(50) DEFAULT NULL,
			`template` varchar(255) DEFAULT NULL,
			`main_label` varchar(255) DEFAULT NULL,
			PRIMARY KEY (`templ33t_template_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1';

		$sql_blocks = 'CREATE TABLE `'.$block_table_name.'` (
			`templ33t_block_id` int(11) NOT NULL AUTO_INCREMENT,
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

		add_option("templ33t_db_version", $templ33t_db_version);

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

		update_option("templ33t_db_version", $templ33t_db_version);

	}
	*/
}

function templ33t_uninstall() {

	global $wpdb;

	$template_table_name = $wpdb->prefix . "templ33t_templates";
	$block_table_name = $wpdb->prefix . "templ33t_blocks";

	$sql_blocks = 'DROP TABLE IF EXISTS `'.$block_table_name.'`;';
	$wpdb->query($sql_blocks);

	$sql_templates = 'DROP TABLE IF EXISTS `'.$template_table_name.'`;';
	$wpdb->query($sql_templates);

	delete_option("templ33t_db_version");

}

function templ33t_menu() {

	add_submenu_page('options-general.php', 'Templ33t Settings', 'Templ33t Settings', 'edit_themes', 'templ33t_settings', 'templ33t_settings');
	
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

	global $templ33t_tab_pages, $templ33t_file, $templ33t_xml, $templ33t_templates;
	
	// register styles & scripts
	wp_register_style('templ33t_styles', TEMPL33T_ASSETS.'templ33t.css');
	wp_register_script('templ33t_scripts', TEMPL33T_ASSETS.'templ33t.js');
	wp_register_script('templ33t_settings_scripts', TEMPL33T_ASSETS.'templ33t_settings.js');

	// initialize tab
	if(in_array(basename($_SERVER['PHP_SELF']), $templ33t_tab_pages)) {

		// add hooks
		add_action('posts_selection', 'templ33t_handle_meta', 1);
		add_action('admin_print_styles', 'templ33t_styles', 1);
		add_action('admin_print_scripts', 'templ33t_scripts', 1);
		add_action('edit_page_form', 'templ33t_elements', 1);

		// generate config file path
		$templ33t_file = get_template_directory().'/templ33t.xml';

		if(file_exists($templ33t_file)) {

			// parse configuration file
			$templ33t_xml = new SimpleXMLElement(file_get_contents($templ33t_file));

			if(property_exists($templ33t_xml, 'template')) {

				// record templates and blocks
				foreach($templ33t_xml->template as $template) {

					$templ33t_templates[(string)$template->file] = array(
						'main' => (property_exists($template, 'main') ? (string)$template->main : ''),
						'blocks' => array(),
					);

					if(property_exists($template, 'block')) {
						foreach($template->block as $block) {
							$templ33t_templates[(string)$template->file]['blocks'][] = (string)$block;
						}
					}

				}

			}

		}

	} elseif(basename($_SERVER['PHP_SELF']) == 'options-general.php' && $_GET['page'] == 'templ33t_settings') {

		// add styles & scripts
		add_action('admin_print_styles', 'templ33t_styles', 1);
		add_action('admin_print_scripts', 'templ33t_settings_scripts', 1);

		// handle settings page post
		templ33t_handle_settings();

	}

}

function templ33t_handle_settings() {

	global $wpdb;

	$table_name = $wpdb->prefix . 'templ33t_blocks';

	if(!empty($_POST) && array_key_exists('templ33t', $_POST)) {

		$required = array(
			'templ33t_theme',
			'templ33t_template',
			'templ33t_block'
		);

		if($_POST['templ33t_all'] == 1) $_POST['templ33t_template'] = 'ALL';

		$errors = array();

		// check and sterilize
		foreach($required as $field) {
			if(!array_key_exists($field, $_POST)) {
				$errors[] = str_replace('templ33t_', '', $field);
			} else {
				$_POST[$field] = htmlspecialchars($_POST[$field], ENT_QUOTES);
			}
		}

		if(!empty($errors)) {

			$redirect = 'options-general.php?page=templ33t_settings';

			if(array_key_exists('templ33t_theme', $_POST)) $redirect .= '&theme='.$_POST['templ33t_theme'];

			$redirect .= '&error='.implode('|', $errors);

			wp_redirect($redirect);

		} else {

			$slug = strtolower(str_replace(' ', '_', trim(chop(preg_replace('/([^a-z0-9]+)/i', ' ', $_POST['templ33t_block'])))));

			$check = $wpdb->get_row('SELECT * FROM `'.$table_name.'` WHERE `theme` = "'.$_POST['templ33t_theme'].'" AND `block_slug` = "'.$slug.'" LIMIT 1', ARRAY_A);

			if(empty($check)) {

				$i_arr = array(
					'theme' => $_POST['templ33t_theme'],
					'template' => $_POST['templ33t_template'],
					'block_name' => $_POST['templ33t_block'],
					'block_slug' => $slug
				);

				$insert = $wpdb->insert(
					$table_name,
					$i_arr
				);

				$redirect = 'options-general.php?page=templ33t_settings&theme='.$_POST['templ33t_theme'];

				wp_redirect($redirect);

			} else {

				$redirect = 'options-general.php?page=templ33t_settings&error=duplicate';

				wp_redirect($redirect);

			}

		}
		
		
		
	}

	if(isset($_GET['t_action'])) {

		switch($_GET['t_action']) {

			case 'delete':

				$row = $wpdb->get_row('SELECT * FROM `'.$table_name.'` WHERE `block_slug` = "'.htmlspecialchars($_GET['t_block'], ENT_QUOTES).'"', ARRAY_A);

				if(!empty($row)) {

					$sql = 'DELETE FROM `'.$table_name.'` WHERE `templ33t_block_id` = '.$row['templ33t_block_id'];

					$wpdb->query($sql);

					wp_redirect('options-general.php?page=templ33t_settings&theme='.$row['theme']);

				} else {

					wp_redirect('options-general.php?page=templ33t_settings&error=noblock');

				}

				break;

			default:

				wp_redirect('options-general.php?page=templ33t_settings&error=noaction');

				break;

		}

	}

}

function templ33t_settings_scripts() {

	wp_enqueue_script('templ33t_settings_scripts', null, array('jquery'));

}

function templ33t_settings() {

	global $wpdb, $templ33t_errors;

	$templates_table_name = $wpdb->prefix . 'templ33t_templates';
	$blocks_table_name = $wpdb->prefix . 'templ33t_blocks';

	$themes = get_themes();

	$theme_count = count($themes);

	if(isset($_GET['theme'])) {
		$theme_selected = $_GET['theme'];
	} else {
		$top = current($themes);
		$theme_selected = $top['Template'];
	}

	$block_data = $wpdb->get_results(
		'SELECT a.*, b.templ33t_template_id, b.template, b.theme
		FROM `'.$blocks_table_name.'` as a
		JOIN `'.$templates_table_name.'` as b ON (a.template_id = b.templ33t_template_id)
		ORDER BY `template`, `block_name`',
		ARRAY_A
	);

	$blocks = array();
	$templates = array();

	foreach($block_data as $key => $val) {
		
		if(!array_key_exists($val['theme'], $templates)) {
			$templates[$val['theme']] = array($val['templ33t_template_id'] => $val['template']);
		} else {
			$templates[$val['theme']][$val['templ33t_template_id']] = $val['template'];
		}

		if(!array_key_exists($val['theme'], $blocks)) {

			$blocks[$val['theme']] = array($val['template'] => array($val['block_slug'] => $val['block_name']));
			
		} elseif(!array_key_exists($val['template'], $blocks[$val['theme']])) {

			$blocks[$val['theme']][$val['template']] = array($val['block_slug'] => $val['block_name']);

		} else {

			$blocks[$val['theme']][$val['template']][$val['block_slug']] = $val['block_name'];

		}

	}

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

	<?php if(!empty($error)) { ?>
	<p class="templ33t_error">
		<?php echo $error; ?>
	</p>
	<?php } ?>

	<div id="templ33t_settings">

		<div class="templ33t_themes">

			<ul>
				<?php $x = 1; foreach($themes as $key => $val) { ?>
				<li class="<?php if($theme_selected == $val['Template']) echo 'selected'; if($x == 1) echo ' first'; elseif($x == $theme_count) echo ' last'; ?>" rel="<?php echo $val['Template']; ?>">
					<?php echo $key; ?>
				</li>
				<?php $x++; } ?>
			</ul>

		</div>

		<div class="templ33t_blocks">

			<?php foreach($themes as $key => $val) { ?>
			<div class="templ33t_block_list_<?php echo $val['Template']; if($theme_selected == $val['Template']) echo ' templ33t_active'; else echo ' templ33t_hidden'; ?>">

				<div>
					<form method="post">

						Add template: 
						<input type="hidden" name="templ33t_theme" value="<?php echo $val['Template']; ?>" />
						<input type="text" class="templ33t_template" name="templ33t_template" value="" size="30" />
						<input type="text" class="templ33t_main_label" name="templ33t_main_label" value="" size="30" />
						<input type="submit" name="templ33t_new_template" value="Add Block" />
						
					</form>
				</div>

				<hr/>

				<?php if(isset($templates[$val['Template']]) && !empty($templates[$val['Template']])) { ?>
				<ul>
					<?php foreach($templates[$val['Template']] as $tkey => $tval) { ?>
					<li>
						<div class="templ33t_right">
							<form method="post">

							</form>
						</div>
						<h4><?php echo $tval; ?></h4>
						<?php if(isset($blocks[$val['Template']]) && isset($blocks[$val['Template']][$tval['template']]) && !empty($blocks[$val['Template']][$tval['template']])) { ?>
						<ul>
							<?php foreach($blocks[$val['Template']][$tval['template']] as $bkey => $bval) { ?>
							<li>
								<?php echo $bval; ?> (<?php echo $bkey; ?>)
							</li>
							<?php } ?>
						</ul>
						<?php } else { ?>
						<p>No Content Blocks</p>
						<?php } ?>
					</li>
					<?php } ?>
				</ul>
				<?php } else { ?>
				<p>No Templates</p>
				<?php } ?>
				
			</div>
			<?php } ?>

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

	global $templ33t_meta, $templ33t_templates, $templ33t_render, $post, $table_prefix, $wpdb;

	if(empty($templ33t_meta) && $templ33t_meta !== false) {

		// grab meta
		$meta = has_meta($post->ID);

		// filter out unrelated
		foreach($meta as $key => $val) {
			if(strpos($val['meta_key'], 'templ33t_') !== false)
				$templ33t_meta[str_replace('templ33t_', '', $val['meta_key'])] = array('id' => $val['meta_id'], 'value' => $val['meta_value']);
		}

		// set default page filename
		if(empty($post->page_template) || $post->page_template == 'default') $post->page_template = 'page.php';

		// check for template definitions, create any non-existent blocks and reload
		if(array_key_exists($post->page_template, $templ33t_templates) && !empty($templ33t_templates[$post->page_template]['blocks'])) {

			// flag
			$templ33t_render = true;

			// add missing custom fields
			foreach($templ33t_templates[$post->page_template]['blocks'] as $key => $block) {
				if(!array_key_exists($block, $templ33t_meta)) {
					
					if(add_post_meta($post->ID, 'templ33t_'.$block, '', true)) {

						$meta_id = $wpdb->get_col('SELECT LAST_INSERT_ID() as lid FROM `'.$table_prefix.'postmeta` LIMIT 1');
						$templ33t_meta[$block] = array(
							'id' => $meta_id[0],
							'value' => '',
						);
						
					}
					
				}
			}

		} elseif(empty($templ33t_meta)) {

			// current template uses no tabs
			$templ33t_render = false;
			$templ33t_meta = false;

		}

	}

	

}

function templ33t_styles() {
	wp_enqueue_style('templ33t_styles');
}

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
			$str = '"'.$template.'": {main: "'.$config['main'].'", blocks: ['
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

		// output tab bar
		echo '<div id="templ33t_control" style="display: none;"><ul>';
		echo '<li id="templ33t_default" class="selected"><a href="" rel="default">'.str_replace('_', ' ', $templ33t_templates[$post->page_template]['main']).'</a><div id="templ33t_main_content"></div></li>';

		foreach($templ33t_templates[$post->page_template]['blocks'] as $key => $val) {
			echo '<li><a href="#" rel="'.$templ33t_meta[$val]['id'].'">'.str_replace('_', ' ', $val).'</a></li>';
		}

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

	global $templ33t_available, $post;

	// grab custom fields
	if(empty($templ33t_available))
		$templ33t_available = get_post_custom();

	// output block if exists
	if(array_key_exists('templ33t_'.$block, $templ33t_available)) {
		if(is_array($templ33t_available['templ33t_'.$block]))
			echo $templ33t_available['templ33t_'.$block][0];
		else
			$templ33t_available['templ33t_'.$block];
	}

}