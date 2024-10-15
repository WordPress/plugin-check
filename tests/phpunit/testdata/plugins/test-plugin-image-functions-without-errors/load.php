<?php
/**
 * Plugin Name: Test Plugin Image Functions check without errors
 * Plugin URI: https://github.com/wordpress/plugin-check
 * Description: Test plugin for the Image Functions check.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-image-functions-without-errors
 *
 * @package test-plugin-image-functions-without-errors
 */

add_action(
	'wp_body_open',
	function() {
		echo wp_get_attachment_image( 123 );
	}
);
