<?php
/**
 * Test file declaring a method named after a newer WordPress function.
 *
 * @package test-plugin-wp-functions-compatibility-with-non-global-declarations
 */

class Notices {

	public function wp_trigger_error( $function_name, $message ) {
		return "{$function_name}: {$message}";
	}
}
