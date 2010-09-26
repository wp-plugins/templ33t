=== Templ33t ===
Contributors: Ryan Willis
Tags: template, page, admin, custom fields, blocks, cms
Requires at least: 2.9
Tested up to: 3.0.1
Stable tag: 0.1

Add tabs for custom content blocks to the edit page with enhanced theme-specific configuration and smart template switching.

== Description ==

Add tabs for custom content blocks to the edit page with enhanced theme-specific configuration and smart template switching. Makes use of custom fields, page templates and a theme-specific XML file. This plugin is based on [Page.ly MultiEdit][1]. Visit [Templ33t Home][2] for updates and info.

 [1]: http://blog.page.ly/multiedit-plugin/ "Page.ly MultiEdit"
 [2]: http://www.totallyryan.com/projects/Templ33t "Templ33t"

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

**Templating**: use the templ33t\_block('block\_name') function wherever you want to add custom content (replacing 'block_name' with your custom block name).

**Configuration File**: create a file named templ33t.xml in your theme directory. It should be structured like this:

<templ33t>
	<template>
		<file>custom-footer-template.php</file>
		<main>Main Content</main>
		<block>Custom_Footer</block>
	</template>
	<template>
		<file>two-column-template.php</file>
		<main>Left_Column</main>
		<block>Right_Column</block>
		<block>Custom_Footer</block>
	</template>
</templ33t>

You can have as many <template> elements as you like. Each one identifies the template file name (<file>), the label for the main content tab (<main>) and all custom content blocks (<block>) added to your template code.

**Multi-Site Installation**: Drop the contents in the wp-content/mu-plugins directory. Move templ33t.php into the parent directory and change the TEMPL33T\_ASSETS path definition near the top of the file to WP\_CONTENT.'/mu-plugins/templ33t/'
== Changelog ==
= 0.1 =
*   initial build and import