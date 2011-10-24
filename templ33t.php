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
 * Require templ33t objects & interfaces
 */
require_once(dirname(__FILE__) . '/templ33t_object.php');
require_once(dirname(__FILE__) . '/plugs/templ33t_plugin_handler.php');
require_once(dirname(__FILE__) . '/plugs/templ33t_plugin.php');
require_once(dirname(__FILE__) . '/plugs/templ33t_tab_interface.php');
require_once(dirname(__FILE__) . '/plugs/templ33t_option_interface.php');

/**
 * Create Templ33t Instance
 */
$templ33t = new Templ33t;

/**
 * Outputs a custom block within the template file.
 *
 * @global array $templ33t_available
 * @global object $post
 * @param string $block
 * @param string $before
 * @param string $after
 * @param boolean $return
 */
function templ33t_block($block = null, $before = null, $after = null, $return = false) {

	global $templ33t;
	
	//print_r($templ33t->block_objects);
	
	$value = array_key_exists($block, $templ33t->block_objects) ? ($templ33t->block_objects[$block] instanceOf Templ33tTab ? $templ33t->block_objects[$block]->output(true) : $templ33t->block_objects[$block]->value) : '';

	if(!empty($value) || $templ33t->block_objects[$block]->optional) {
		if($return)
			return apply_filters('the_content', $before.$value.$after);
		else
			echo apply_filters('the_content', $before.$value.$after);
	}

}

/**
 * Returns an option value.
 *
 * @global object $templ33t
 * @param string $option
 */
function templ33t_option($option = null) {

	global $templ33t;

	if(array_key_exists($option, $templ33t->option_objects)) {
		return $templ33t->option_objects[$option] instanceOf Templ33tOption ? $templ33t->option_objects[$option]->getValue() : $templ33t->option_objects[$option]->value;
	}

}