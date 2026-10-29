<?php
/**
 * Test file declaring an enum method named after a newer WordPress function.
 *
 * @package test-plugin-wp-functions-compatibility-with-non-global-declarations
 */

enum /* level */ Notice_Level: string {
	case INFO = 'info';

	public function wp_trigger_error( $function_name, $message ) {
		return $message;
	}
}
