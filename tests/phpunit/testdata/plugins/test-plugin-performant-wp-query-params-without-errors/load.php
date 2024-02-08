<?php
/**
 * Plugin Name: Test Plugin Performant without errors for Plugin Check
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-check
 *
 * @package test-plugin-check
 */

/**
 * File contains no errors related to performant WP_Query params check.
 */

$query = new WP_Query(
	array(
		'posts_per_page' => 10,
		'post_type'      => 'post',
	)
);
