<?php
/**
 * Tests for the Late_Escaping_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Late_Escaping_Check;

class Late_Escaping_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$late_escape_check = new Late_Escaping_Check();
		$check_context     = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-late-escaping-errors/load.php' );
		$check_result      = new Check_Result( $check_context );

		$late_escape_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertEquals( 2, $check_result->get_error_count() );

		// Check for WordPress.Security.EscapeOutput error on Line no 22 and column no at 5.
		$this->assertArrayHasKey( 22, $errors['load.php'] );
		$this->assertArrayHasKey( 5, $errors['load.php'][22] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][22][5][0] );
		$this->assertEquals( 'WordPress.Security.EscapeOutput', $errors['load.php'][22][5][0]['code'] );
	}

	public function test_run_without_errors() {
		$late_escape_check = new Late_Escaping_Check();
		$check_context     = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-late-escaping-without-errors/load.php' );
		$check_result      = new Check_Result( $check_context );

		$late_escape_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
