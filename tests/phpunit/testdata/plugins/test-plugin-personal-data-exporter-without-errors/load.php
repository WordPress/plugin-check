<?php
/**
 * Plugin Name: Test Plugin Personal Data Exporter Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Personal Data Exporter check — stores user meta AND registers a data exporter.
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-personal-data-exporter-ok
 *
 * @package test-plugin-personal-data-exporter-ok
 */

/**
 * Saves a custom preference for a user.
 *
 * @param int $user_id User ID.
 */
function test_pde_ok_save_user_preference( $user_id ) {
	update_user_meta( $user_id, 'test_pde_ok_preference', 'some_value' );
}
add_action( 'user_register', 'test_pde_ok_save_user_preference' );

/**
 * Registers the personal data exporter.
 *
 * @param array $exporters An array of personal data exporters.
 * @return array Updated exporters array.
 */
function test_pde_ok_register_exporter( $exporters ) {
	$exporters['test-pde-ok'] = array(
		'exporter_friendly_name' => __( 'Test PDE OK Plugin Data', 'test-plugin-personal-data-exporter-ok' ),
		'callback'               => 'test_pde_ok_exporter',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'test_pde_ok_register_exporter' );

/**
 * Exports personal data for a user.
 *
 * @param string $email_address Email address of the user.
 * @param int    $page          Pagination page number.
 * @return array Export data.
 */
function test_pde_ok_exporter( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$preference = get_user_meta( $user->ID, 'test_pde_ok_preference', true );
	$data       = array();

	if ( $preference ) {
		$data[] = array(
			'group_id'    => 'test-pde-ok',
			'group_label' => __( 'Test PDE OK Data', 'test-plugin-personal-data-exporter-ok' ),
			'item_id'     => 'test-pde-ok-' . $user->ID,
			'data'        => array(
				array(
					'name'  => __( 'Preference', 'test-plugin-personal-data-exporter-ok' ),
					'value' => $preference,
				),
			),
		);
	}

	return array(
		'data' => $data,
		'done' => true,
	);
}
