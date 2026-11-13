<?php
/**
 * Plugin Name: Test Plugin Menu Image Icon Fully Qualified
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin using fully qualified global add_menu_page() calls with raster image menu icons.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-menu-image-icon-fully-qualified
 *
 * @package test-plugin-menu-image-icon-fully-qualified
 */

namespace Example\Plugin;

/**
 * Examples of fully qualified global add_menu_page() calls that use a raster
 * image as the admin menu icon. On PHP 8+, the leading backslash makes the call
 * tokenize as T_NAME_FULLY_QUALIFIED rather than T_STRING.
 */

// Exclamation: fully qualified call with a PNG image icon inside a namespace.
\add_menu_page(
	'My Plugin',
	'My Plugin',
	'manage_options',
	'my-plugin',
	'my_plugin_page',
	'img/icon.png',
	40
);

// Exclamation: fully qualified call with a JPG image icon inside a namespace.
\add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_page', 'img/icon.jpg', 41 );

/**
 * Callback function for admin pages.
 */
function my_plugin_page() {
	echo '<div class="wrap"><h1>My Plugin</h1></div>';
}
