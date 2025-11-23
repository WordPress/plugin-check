<?php
/**
 * Plugin Name: Test Plugin Third Party Documented
 * Plugin URI: https://example.com
 * Description: Test plugin with properly documented third-party services.
 * Version: 1.0.0
 * Author: Test Author
 * Author URI: https://example.com
 * Text Domain: test-plugin
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Make an API call to Stripe.
function test_payment_processing() {
	$response = wp_remote_get( 'https://api.stripe.com/v1/charges' );
	return $response;
}

