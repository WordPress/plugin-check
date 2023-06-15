<?php
/**
 * Tests for the I18n_Usage_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\I18n_Usage_Check;
use WordPress\Plugin_Check\Test_Utils\TestCase\Static_Check_UnitTestCase;

class I18n_Usage_Check_Tests extends Static_Check_UnitTestCase {

	public function test_run_with_errors() {
		$i18n_usage_check = new I18n_Usage_Check();
		$check_context    = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-i18n-usage-errors/load.php' );
		$check_result     = new Check_Result( $check_context );

		$i18n_usage_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertEquals( 2, $check_result->get_error_count() );

		// Check for WordPress.WP.I18n.MissingTranslatorsComment error on Line no 26 and column no at 5.
		$this->assertArrayHasKey( 26, $errors['load.php'] );
		$this->assertArrayHasKey( 5, $errors['load.php'][26] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][26][5][0] );
		$this->assertEquals( 'WordPress.WP.I18n.MissingTranslatorsComment', $errors['load.php'][26][5][0]['code'] );

		// Check for WordPress.WP.I18n.NonSingularStringLiteralDomain error on Line no 33 and column no at 29.
		$this->assertArrayHasKey( 33, $errors['load.php'] );
		$this->assertArrayHasKey( 29, $errors['load.php'][33] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][33][29][0] );
		$this->assertEquals( 'WordPress.WP.I18n.NonSingularStringLiteralDomain', $errors['load.php'][33][29][0]['code'] );
	}

	public function test_run_without_errors() {
		$i18n_usage_check = new I18n_Usage_Check();
		$check_context    = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-i18n-usage-without-errors/load.php' );
		$check_result     = new Check_Result( $check_context );

		$i18n_usage_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
