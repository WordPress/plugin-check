<?php
/**
 * Plugin Name: Test Plugin Prefixing Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Prefixing check.
 * Requires at least: 6.0
 * Requires PHP: 7.2
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-prefixing-errors
 *
 * @package test-plugin-prefixing-errors
 */

define( 'WP_HELLO', 1 );

function dosomething() {
	echo 'Hello, World!';
}

function xyz_dosomething() {
	echo 'Hello, World!';
}

function er_dosomething() {
	echo 'Hello, World!';
}

function abc_display() {
}

function abc_render() {
}

function abcd_test() {
}

function wp_test() {
}

update_option( 'random_number', 123 );
