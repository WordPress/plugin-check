<?php
/**
 * Test file declaring an anonymous class method named after a newer WordPress function.
 *
 * @package test-plugin-wp-functions-compatibility-with-non-global-declarations
 */

$notices = new class( function () {} ) {

	public function wp_trigger_error( $function_name, $message ) {
		return "{$function_name}: {$message}";
	}
};
