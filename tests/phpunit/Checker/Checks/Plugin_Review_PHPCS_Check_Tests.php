<?php
/**
 * Tests for the Plugin_Review_PHPCS_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Review_PHPCS_Check;
use WordPress\Plugin_Check\Test_Utils\TestCase\Static_Check_UnitTestCase;

class Plugin_Review_PHPCS_Check_Tests extends Static_Check_UnitTestCase {

	public function test_run_with_errors() {
		$plugin_review_phpcs_check = new Plugin_Review_PHPCS_Check();
		$check_context             = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-review-phpcs-errors/load.php' );
		$check_result              = new Check_Result( $check_context );

		$plugin_review_phpcs_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertEquals( 2, $check_result->get_error_count() );

		// Check for Generic.PHP.DisallowShortOpenTag.Found error on Line no 6 and column no at 1.
		$this->assertArrayHasKey( 6, $errors['load.php'] );
		$this->assertArrayHasKey( 1, $errors['load.php'][6] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][6][1][0] );
		$this->assertEquals( 'Generic.PHP.DisallowShortOpenTag.Found', $errors['load.php'][6][1][0]['code'] );

		// Check for WordPress.WP.DeprecatedFunctions.the_author_emailFound error on Line no 12 and column no at 5.
		$this->assertArrayHasKey( 12, $errors['load.php'] );
		$this->assertArrayHasKey( 5, $errors['load.php'][12] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][12][5][0] );
		$this->assertEquals( 'WordPress.WP.DeprecatedFunctions.the_author_emailFound', $errors['load.php'][12][5][0]['code'] );
	}

	public function test_run_without_errors() {
		$plugin_review_phpcs_check = new Plugin_Review_PHPCS_Check();
		$check_context             = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-review-phpcs-without-errors/load.php' );
		$check_result              = new Check_Result( $check_context );

		$plugin_review_phpcs_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
