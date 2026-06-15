<?php
/**
 * Plugin Name: Test Plugin Privacy Policy Comment Only
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: A test plugin that mentions privacy-related function names only in comments, but has a real signal in code. Token check must ignore the comment-only privacy call and still warn.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-privacy-policy-comment-only
 *
 * @package test-plugin-privacy-policy-comment-only
 */

// TODO: call wp_add_privacy_policy_content() later when privacy is implemented.

/**
 * Sends data to an external service. Real signal must trigger warning.
 *
 * @return array|WP_Error
 */
function test_plugin_privacy_comment_only_send_data() {
	return wp_remote_post(
		'https://example-analytics.com/collect',
		array(
			'body' => array(
				'email' => get_option( 'admin_email' ),
			),
		)
	);
}
