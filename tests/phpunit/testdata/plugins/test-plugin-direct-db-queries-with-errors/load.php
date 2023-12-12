<?php
/**
 * Plugin Name: Test Plugin direct DB queries with Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: n.e.x.t
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-direct-db-queries-with-errors
 *
 * @package test-plugin-direct-db-queries-with-errors
 */

/**
 * File contains errors related to direct DB queries issues.
 */

global $wpdb;

$column = $wpdb->get_col( 'SELECT X FROM Y WHERE Z = 1' );

$autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option_name ) );

$wpdb->update( $wpdb->posts, array( 'post_title' => 'Hello World' ), array( 'ID' => 1 ) );
