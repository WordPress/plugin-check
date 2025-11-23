<?php
/**
 * Plugin Name: Test Plugin Minified Files Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Author: WordPress Plugins Team
 * Author URI: https://make.wordpress.org/plugins/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-minified-files-without-errors
 *
 * @package test-plugin-minified-files-without-errors
 */

/**
 * A normal, non-minified function.
 *
 * @param int $a First number.
 * @param int $b Second number.
 * @return int Sum of the two numbers.
 */
function test_add_numbers( $a, $b ) {
	return $a + $b;
}

/**
 * Another normal function with proper formatting.
 *
 * @param array $items Array of items.
 * @return array Processed items.
 */
function test_process_items( $items ) {
	$result = array();

	foreach ( $items as $item ) {
		$result[] = strtoupper( $item );
	}

	return $result;
}

// Use the functions.
$sum   = test_add_numbers( 5, 10 );
$items = test_process_items( array( 'hello', 'world' ) );

