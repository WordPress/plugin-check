<?php
/**
 * Plugin Name: Test Plugin DB Prepared Query with Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0 Beta
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-check-prepared-sql
 *
 * @package test-plugin-check-prepared-sql
 */

global $wpdb;

$query = 'SELECT * FROM wp_posts WHERE post_status = %s';
$args  = array( 'publish' );

$results = $wpdb->get_results(
	$wpdb->prepare( $query, ...$args )
);
