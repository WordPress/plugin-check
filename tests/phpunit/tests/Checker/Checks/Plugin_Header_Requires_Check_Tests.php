<?php
/**
 * Tests for the Plugin_Header_Requires_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Header_Requires_Check;

class Plugin_Header_Requires_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Plugin_Header_Requires_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-header-requires-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertEquals( 2, $check_result->get_error_count() );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_warning_count() );

		$this->assertArrayHasKey( 0, $errors['load.php'] );
		$this->assertArrayHasKey( 0, $errors['load.php'][0] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][0][0][0] );
		$this->assertEquals( 'missing_plugin_header', $errors['load.php'][0][0][0]['code'] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][0][0][1] );
		$this->assertEquals( 'missing_plugin_header', $errors['load.php'][0][0][1]['code'] );
	}

	public function test_run_without_errors() {
		$check         = new Plugin_Header_Requires_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-header-requires-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
