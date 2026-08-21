<?php
/**
 * Tests for the Premium_Only_Function_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Premium_Only_Function_Check;

class Premium_Only_Function_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Premium_Only_Function_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-premium-only-function-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertSame( 2, $check_result->get_error_count() );

		$this->assertArrayHasKey( 12, $errors['load.php'] );
		$this->assertArrayHasKey( 16, $errors['load.php'] );
		$this->assertSame( 'premium_only_function_found', $errors['load.php'][12][1][0]['code'] );
		$this->assertSame( 'premium_only_function_found', $errors['load.php'][16][1][0]['code'] );
	}

	public function test_run_without_errors() {
		$check         = new Premium_Only_Function_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-premium-only-function-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertEmpty( $errors );
		$this->assertSame( 0, $check_result->get_error_count() );
	}
}
