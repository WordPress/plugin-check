<?php
/**
 * Plugin Name: Test Plugin direct DB with Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-direct-db-with-errors
 *
 * @package test-plugin-direct-db-with-errors
 */

function insecure_wpdb_query_1( $foo ) {
	global $wpdb;

	// 1. Unescaped query, string concat.
	$wpdb->query( "SELECT * FROM $wpdb->users WHERE foo = '" . $foo . "' LIMIT 1" ); // Error.
}

function insecure_wpdb_query_2( $bar ) {
	global $wpdb;

	// 2. Unescaped query, interpolated string.
	$wpdb->query( "SELECT * FROM $wpdb->posts WHERE bar = '$bar' LIMIT 1" ); // Error.
}

function insecure_wpdb_query_3( $baz ) {
	global $wpdb;

	// 3. Unescaped query, interpolated with {}.
	$wpdb->query( "SELECT * FROM $wpdb->comments WHERE baz = '{$baz}' LIMIT 1" ); // Error.
}

function insecure_wpdb_query_4( $qux ) {
	global $wpdb;

	// 4. Unescaped query, superglobal.
	$wpdb->query( "SELECT * FROM $wpdb->users WHERE qux = '" . $_POST['qux'] . "' LIMIT 1" ); // Error.
}

function insecure_wpdb_query_5( $quux ) {
	global $wpdb;

	// 5. Unescaped query, object property.
	$wpdb->query( "SELECT * FROM $wpdb->posts WHERE quux = '" . $quux->property . "' LIMIT 1" ); // Error.
}
