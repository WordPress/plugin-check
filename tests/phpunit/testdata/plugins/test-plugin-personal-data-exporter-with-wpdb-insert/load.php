<?php
/**
 * Plugin Name: Test Plugin Personal Data Exporter With WPDB Insert
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Personal Data Exporter check — uses $wpdb->insert() directly but does not register a data exporter.
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-personal-data-exporter-wpdb
 *
 * @package test-plugin-personal-data-exporter-wpdb
 */

/**
 * Persists a custom value tied to a user via a direct database write.
 *
 * @param int $user_id User ID.
 */
function test_pde_wpdb_save_user_data( $user_id ) {
	global $wpdb;
	$wpdb->insert(
		$wpdb->usermeta,
		array(
			'user_id'   => $user_id,
			'meta_key'  => 'test_pde_wpdb_value',
			'meta_value' => 'test_value',
		)
	);
}
add_action( 'user_register', 'test_pde_wpdb_save_user_data' );
