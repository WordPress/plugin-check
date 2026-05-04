<?php
/**
 * Plugin Name: Test Plugin Privacy Policy With Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: A test plugin that handles personal data but does not register privacy policy content.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-privacy-policy-with-errors
 *
 * @package test-plugin-privacy-policy-with-errors
 */

// Sends data to an external service — indicates potential personal data handling.
function test_plugin_privacy_send_data() {
	$response = wp_remote_post(
		'https://example-analytics.com/collect',
		array(
			'body' => array(
				'email' => get_option( 'admin_email' ),
			),
		)
	);

	return $response;
}
