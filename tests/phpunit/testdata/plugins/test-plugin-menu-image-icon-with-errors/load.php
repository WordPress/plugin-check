<?php
/**
 * Plugin Name: Test Plugin Menu Image Icon With Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin with raster image files used as admin menu icons.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-menu-image-icon-with-errors
 *
 * @package test-plugin-menu-image-icon-with-errors
 */

/**
 * These are examples of problematic code that uses raster image files as
 * the admin menu icon in add_menu_page().
 */

// Exclamation: PNG image used as menu icon.
add_menu_page(
	'My Plugin',
	'My Plugin',
	'manage_options',
	'my-plugin',
	'my_plugin_page',
	'img/icon.png',
	30
);

// Exclamation: JPG image used as menu icon.
add_menu_page(
	'My Plugin',
	'My Plugin',
	'manage_options',
	'my-plugin',
	'my_plugin_page',
	'img/icon.jpg'
);

// Exclamation: GIF image used as menu icon.
add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_page', 'img/icon.gif', 32 );

// Exclamation: WebP image used as menu icon.
add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_page', 'img/icon.webp', 33 );

// Exclamation: ICO image used as menu icon.
add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_page', 'img/icon.ico', 34 );

// Exclamation: BMP image used as menu icon.
add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_page', 'img/icon.bmp', 35 );

// Exclamation: Image with a query string is still a raster image icon.
add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_page', 'img/icon.png?v=2', 36 );

// Exclamation: Fully qualified call uses a raster image, even in global scope.
\add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_page', 'img/icon.png', 37 );

/**
 * Callback function for admin pages.
 */
function my_plugin_page() {
	echo '<div class="wrap"><h1>My Plugin</h1></div>';
}
