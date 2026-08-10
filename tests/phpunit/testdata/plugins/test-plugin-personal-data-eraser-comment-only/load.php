<?php
/**
 * Plugin Name: Test Plugin Personal Data Eraser Comment Only
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Personal Data Eraser check — only mentions personal data function names in comments and strings, never calls them.
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-personal-data-eraser-comment-only
 *
 * @package test-plugin-personal-data-eraser-comment-only
 */

/**
 * Notes about potential functions:
 * update_user_meta() and add_user_meta() are referenced here only inside a
 * comment and must not be treated as personal data handling.
 */

/**
 * Returns an option name that happens to contain a function name as a string.
 *
 * @return string Option name.
 */
function test_pdel_comment_option() {
	// Mentioning the call in a comment should not count either: update_user_meta().
	return 'update_user_meta';
}
