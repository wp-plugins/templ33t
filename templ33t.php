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

// add install hook
register_activation_hook(__FILE__, 'templ33t_install');

// add plugin hooks
if(in_array(basename($_SERVER['PHP_SELF']), array('page.php', 'page-new.php', 'post.php', 'post-new.php'))) {
	add_action('admin_init', 'templ33t_init', 1);
	add_action('posts_selection', 'templ33t_handle_meta', 1);
	add_action('admin_head', 'templ33t_header', 1);
	add_action('edit_page_form', 'templ33t_elements', 1);
}

/**
 * Create db tables
 */
function templ33t_install() {

	global $wpdb, $templ33t_db_version;

	$table_name = $wpdb->prefix . "templ33t_blocks";

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

		$sql = 'CREATE TABLE `'.$table_name.'` (
					`templ33t_block_id` int(11) NOT NULL AUTO_INCREMENT,
					`theme_id` int(11) DEFAULT NULL,
					`block_name` varchar(30) DEFAULT NULL,
					`block_slug` varchar(30) DEFAULT NULL,
					PRIMARY KEY  (`templ33t_block_id`)
				);';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option("templ33t_db_version", $templ33t_db_version);

	}

	/*
	$installed_version = get_option( "templ33t_db_version" );

	if($installed_version != $templ33t_db_version) {

		$sql = 'CREATE TABLE `'.$table_name.'` (
					`templ33t_block_id` int(11) NOT NULL AUTO_INCREMENT,
					`blog_id` int(11) DEFAULT NULL,
					`theme` varchar(255) DEFAULT NULL,
					`block_name` varchar(30) DEFAULT NULL,
					`block_slug` varchar(30) DEFAULT NULL,
					PRIMARY KEY  (`templ33t_block_id`)
				);';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		update_option("templ33t_db_version", $templ33t_db_version);

	}
	*/
}

function templ33t_uninstall() {



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

	global $templ33t_file, $templ33t_xml, $templ33t_templates;

	// register scripts & styles
	wp_register_script('templ33t_scripts', TEMPL33T_ASSETS.'templ33t.js');
	wp_register_style('templ33t_styles', TEMPL33T_ASSETS.'templ33t.css');

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

/**
 * Adds templ33t css and javascript functions/variables to the header.
 *
 * @global array $templ33t_templates
 * @global object $post
 */
function templ33t_header() {

	global $templ33t_templates, $post;

	// output style and js
	echo '<link rel="stylesheet" href="'.TEMPL33T_ASSETS.'templ33t.css" />';
	echo '<script type="text/javascript"> TL33T_current = "'.$post->page_template.'"; ';

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

	// output templ33t control js
	echo '<script type="text/javascript" src="'.TEMPL33T_ASSETS.'templ33t.js"></script>';

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