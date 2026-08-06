<?php
/**
 * Plugin Name: Test Plugin Trialware Without Errors
 * Description: Test plugin without trialware patterns.
 * Version: 1.0.0
 * Author: Test Author
 * License: GPL-2.0+
 *
 * @package test-plugin-trialware-without-errors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Display admin settings page.
 */
function test_trialware_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="wrap">';
	echo esc_html__( 'Settings Page', 'test-plugin' );
	echo '</div>';
}

/**
 * Save plugin settings.
 */
function test_trialware_save_settings() {
	if ( ! isset( $_POST['test_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['test_nonce'] ) ), 'test_action' ) ) {
		return;
	}

	$option = isset( $_POST['test_option'] ) ? sanitize_text_field( wp_unslash( $_POST['test_option'] ) ) : '';
	update_option( 'test_trialware_option', $option );
}

/**
 * Load plugin textdomain.
 */
function test_trialware_load_textdomain() {
	load_plugin_textdomain( 'test-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'test_trialware_load_textdomain' );

/**
 * Calls a third-party weather API using its own API key.
 *
 * Not trialware: the key authenticates to an external service the plugin
 * doesn't control, it does not gate any of the plugin's bundled functionality.
 */
function test_trialware_call_external_api() {
	$api_key = get_option( 'test_weather_api_key' );

	if ( empty( $api_key ) ) {
		return new WP_Error( 'missing_api_key', __( 'Weather API key is required.', 'test-plugin' ) );
	}

	return wp_remote_get( 'https://api.example.com/weather?key=' . $api_key );
}

/**
 * Renders a notice linking to a separate premium add-on plugin.
 *
 * Not trialware: this promotes a standalone product, it doesn't disable
 * any functionality bundled in this plugin.
 */
function test_trialware_upgrade_notice() {
	echo '<p>' . esc_html__( 'Need more features? Check out Test Plugin Pro, our premium add-on.', 'test-plugin' ) . '</p>';
	echo '<a href="https://example.com/pro">' . esc_html__( 'Learn more', 'test-plugin' ) . '</a>';
}

/**
 * Checks for plugin updates using a license key, EDD-style updater pattern.
 *
 * Not trialware: the license only unlocks update/support delivery, it does
 * not disable any bundled functionality.
 */
function test_trialware_check_for_updates() {
	$updater = new Test_Plugin_Updater(
		'https://example.com',
		__FILE__,
		array(
			'license' => get_option( 'test_plugin_license_key' ),
			'item_id' => 12345,
		)
	);

	$updater->fetch_update_data();
}
