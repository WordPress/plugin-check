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
