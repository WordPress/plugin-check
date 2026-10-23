<?php
/**
 * Test file declaring a method named after a newer WordPress function in an anonymous class with a nested anonymous class argument.
 *
 * @package test-plugin-wp-functions-compatibility-with-non-global-declarations
 */

$notices = new class( new class() {} ) {

	public function wp_trigger_error( $function_name, $message ) {
		return $message;
	}
};
