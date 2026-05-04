<?php
/**
 * Plugin Name: Test Plugin Privacy Policy No Signals
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: A test plugin that does not handle personal data at all — no privacy check signals present.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-privacy-policy-no-signals
 *
 * @package test-plugin-privacy-policy-no-signals
 */

/**
 * Outputs a greeting message in the admin footer.
 *
 * @return void
 */
function test_plugin_privacy_no_signals_greet() {
	echo '<p>' . esc_html__( 'Hello from Test Plugin!', 'test-plugin-privacy-policy-no-signals' ) . '</p>';
}

add_action( 'admin_footer', 'test_plugin_privacy_no_signals_greet' );
