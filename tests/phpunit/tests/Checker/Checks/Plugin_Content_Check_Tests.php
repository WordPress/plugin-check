<?php
/**
 * Tests for the Plugin_Content_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Plugin_Content_Check;

class Plugin_Content_Check_Tests extends WP_UnitTestCase {
	public function test_detect_five_stars_reviews_without_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-five-stars-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Plugin_Content_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertSame( 2, $check_result->get_error_count() );

		$this->assertTrue( isset( $errors['load.php'][16][12][0] ) );
		$this->assertSame( 'five_star_reviews_detected', $errors['load.php'][16][12][0]['code'] );
		$this->assertTrue( isset( $errors['load.php'][17][16][0] ) );
		$this->assertSame( 'five_star_reviews_detected', $errors['load.php'][17][16][0]['code'] );
	}
}
