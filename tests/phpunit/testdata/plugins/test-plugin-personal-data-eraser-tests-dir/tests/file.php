<?php
/**
 * Test fixture that registers a data eraser from within the tests directory.
 *
 * @package test-plugin-personal-data-eraser-tests-dir
 */

/**
 * Registers the personal data eraser. This lives under tests/ and must not
 * suppress the missing-erasure warning for the plugin.
 *
 * @param array $erasers An array of personal data erasers.
 * @return array Updated erasers array.
 */
function test_pdel_tests_register_eraser( $erasers ) {
	$erasers['test-pdel-tests'] = array(
		'eraser_friendly_name' => __( 'Test PDEL Tests Plugin Data', 'test-plugin-personal-data-eraser-tests-dir' ),
		'callback'             => 'test_pdel_tests_eraser',
	);

	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'test_pdel_tests_register_eraser' );

/**
 * Erases personal data for a user.
 *
 * @return array Erasure status array.
 */
function test_pdel_tests_eraser() {
	return array(
		'items_removed'  => false,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => true,
	);
}
