<?php
/**
 * Plugin Name: Test Plugin Privacy Policy Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: A test plugin that handles personal data AND correctly calls wp_add_privacy_policy_content().
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-privacy-policy-without-errors
 *
 * @package test-plugin-privacy-policy-without-errors
 */

// Registers suggested privacy policy content — satisfies the check.
add_action(
	'admin_init',
	function () {
		wp_add_privacy_policy_content(
			'Test Plugin Privacy Policy Without Errors',
			__( 'This plugin sends data to an external analytics service. No personally identifiable information is transmitted.', 'test-plugin-privacy-policy-without-errors' )
		);
	}
);

// Sends data to an external service.
function test_plugin_privacy_no_errors_send_data() {
	$response = wp_remote_post(
		'https://example-analytics.com/collect',
		array(
			'body' => array(
				'site_url' => get_site_url(),
			),
		)
	);

	return $response;
}
