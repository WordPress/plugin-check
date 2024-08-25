<?php
/**
 * Tests for the Sanitized_Input_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Security\Sanitized_Input_Check;

class Sanitized_Input_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$sanitized_input_check = new Sanitized_Input_Check();
		$check_context     = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-sanitized-input-errors/load.php' );
		$check_result      = new Check_Result( $check_context );

		$sanitized_input_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertEquals( 3, $check_result->get_error_count() );

		// Check for WordPress.Security.ValidatedSanitizedInput.InputNotValidated error on Line no 24 and column no at 6.
		$this->assertArrayHasKey( 22, $errors['load.php'] );
		$this->assertArrayHasKey( 28, $errors['load.php'][22] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][22][28][0] );
		$this->assertEquals( 'WordPress.Security.ValidatedSanitizedInput.InputNotValidated', $errors['load.php'][22][28][0]['code'] );
	}

	public function test_run_without_errors() {
		$sanitized_input_check = new Sanitized_Input_Check();
		$check_context     = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-sanitized-input-without-errors/load.php' );
		$check_result      = new Check_Result( $check_context );

		$sanitized_input_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
