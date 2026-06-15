<?php
/**
 * Plugin Name: Test Plugin Privacy Policy String Only
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: A test plugin that mentions privacy-related function names only inside string literals, but has a real signal in code. Token check must ignore the string-only privacy call and still warn.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-privacy-policy-string-only
 *
 * @package test-plugin-privacy-policy-string-only
 */

/**
 * Sends data to an external service. Real signal must trigger warning.
 *
 * @return array|WP_Error
 */
function test_plugin_privacy_string_only_send_data() {
	$placeholder = 'We may eventually call wp_add_privacy_policy_content() to register privacy text.';

	return wp_remote_post(
		'https://example-analytics.com/collect',
		array(
			'body' => array(
				'email' => get_option( 'admin_email' ),
			),
		)
	);
}
