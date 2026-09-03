<?php
/**
 * Plugin Name: Test Plugin Personal Data Eraser Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Personal Data Eraser check — stores user meta AND registers a data eraser.
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-personal-data-eraser-ok
 *
 * @package test-plugin-personal-data-eraser-ok
 */

/**
 * Saves a custom preference for a user.
 *
 * @param int $user_id User ID.
 */
function test_pdel_ok_save_user_preference( $user_id ) {
	update_user_meta( $user_id, 'test_pdel_ok_preference', 'some_value' );
}
add_action( 'user_register', 'test_pdel_ok_save_user_preference' );

/**
 * Registers the personal data eraser.
 *
 * @param array $erasers An array of personal data erasers.
 * @return array Updated erasers array.
 */
function test_pdel_ok_register_eraser( $erasers ) {
	$erasers['test-pdel-ok'] = array(
		'eraser_friendly_name' => __( 'Test PDEL OK Plugin Data', 'test-plugin-personal-data-eraser-ok' ),
		'callback'             => 'test_pdel_ok_eraser',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'test_pdel_ok_register_eraser' );

/**
 * Erases personal data for a user.
 *
 * @param string $email_address Email address of the user.
 * @param int    $page          Pagination page number.
 * @return array Erasure status array.
 */
function test_pdel_ok_eraser( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	$removed = delete_user_meta( $user->ID, 'test_pdel_ok_preference' );

	return array(
		'items_removed'  => $removed,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => true,
	);
}
