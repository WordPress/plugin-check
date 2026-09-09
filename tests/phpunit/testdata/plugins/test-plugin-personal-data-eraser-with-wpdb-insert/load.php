<?php
/**
 * Plugin Name: Test Plugin Personal Data Eraser With WPDB Insert
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Personal Data Eraser check — writes directly to the database via $wpdb but does not register a data eraser.
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-personal-data-eraser-wpdb
 *
 * @package test-plugin-personal-data-eraser-wpdb
 */

/**
 * Saves a custom log entry using a direct database write.
 *
 * @param int    $user_id User ID.
 * @param string $note    Note to store.
 */
function test_pdel_wpdb_save_log( $user_id, $note ) {
	global $wpdb;

	$wpdb->insert(
		$wpdb->prefix . 'test_pdel_log',
		array(
			'user_id' => $user_id,
			'note'    => $note,
			'time'    => current_time( 'mysql' ),
		)
	);
}
add_action( 'user_register', 'test_pdel_wpdb_save_log' );
