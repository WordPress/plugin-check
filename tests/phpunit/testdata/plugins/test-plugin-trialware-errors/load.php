<?php
/**
 * Plugin Name: Test Plugin Trialware Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: File contains locked built-in feature patterns.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * License: GPLv2 or later
 * Text Domain: test-plugin-trialware-errors
 *
 * @package test-plugin-check
 */

function test_plugin_trialware_can_export_report() {
	$license_key = get_option( 'test_plugin_trialware_license_key' );

	if ( empty( $license_key ) ) {
		return new WP_Error( 'locked', 'Enter a license key to unlock report exports.' );
	}

	return array( 'report' => 'exported' );
}

function test_plugin_trialware_can_add_item( $items ) {
	if ( count( $items ) >= 3 ) {
		wp_die( 'Limit reached. Upgrade to add unlimited items.' );
	}

	return true;
}
