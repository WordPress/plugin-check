<?php
/**
 * Plugin Name: Test Plugin Menu Image Icon Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin with proper admin menu icon usage (dashicons and SVG data: URIs).
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-menu-image-icon-without-errors
 *
 * @package test-plugin-menu-image-icon-without-errors
 */

/**
 * These are examples of correct admin menu icon usage.
 */

// Dashicon class used as menu icon.
add_menu_page(
	'My Plugin',
	'My Plugin',
	'manage_options',
	'my-plugin',
	'my_plugin_page',
	'dashicons-admin-post',
	30
);

// Another dashicon class.
add_menu_page( 'Plugin', 'Plugin', 'manage_options', 'plugin', 'plugin_page', 'dashicons-admin-generic', 31 );

// SVG data: URI used as menu icon (valid — adapts to color scheme).
add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin-2', 'my_plugin_page_2', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0i...', 32 );

// SVG data: URI with base64.
add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin-3', 'my_plugin_page_3', 'data:image/svg+xml;charset=utf8,%3Csvg%3E...', 33 );

// Empty icon — default dashicon used.
add_menu_page( 'Plugin A', 'Plugin A', 'manage_options', 'plugin-a', 'plugin_a_page', '', 34 );

// 'none' icon — no icon shown.
add_menu_page( 'Plugin B', 'Plugin B', 'manage_options', 'plugin-b', 'plugin_b_page', 'none', 35 );

// Variable as icon — cannot be statically analyzed, so should not be flagged.
$icon_url = 'img/icon.png';
add_menu_page( 'Plugin C', 'Plugin C', 'manage_options', 'plugin-c', 'plugin_c_page', $icon_url, 36 );

/**
 * Callback functions for admin pages.
 */
function my_plugin_page() {
	echo '<div class="wrap"><h1>My Plugin</h1></div>';
}

function my_plugin_page_2() {
	echo '<div class="wrap"><h1>My Plugin 2</h1></div>';
}

function my_plugin_page_3() {
	echo '<div class="wrap"><h1>My Plugin 3</h1></div>';
}

function plugin_page() {
	echo '<div class="wrap"><h1>Plugin</h1></div>';
}

function plugin_a_page() {
	echo '<div class="wrap"><h1>Plugin A</h1></div>';
}

function plugin_b_page() {
	echo '<div class="wrap"><h1>Plugin B</h1></div>';
}

function plugin_c_page() {
	echo '<div class="wrap"><h1>Plugin C</h1></div>';
}
