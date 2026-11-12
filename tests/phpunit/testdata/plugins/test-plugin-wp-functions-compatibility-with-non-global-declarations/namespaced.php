<?php
/**
 * Test file declaring a namespaced function named after a newer WordPress function.
 *
 * @package test-plugin-wp-functions-compatibility-with-non-global-declarations
 */

namespace Test_Plugin\Notices;

function wp_admin_notice( $message ) {
	echo $message;
}
