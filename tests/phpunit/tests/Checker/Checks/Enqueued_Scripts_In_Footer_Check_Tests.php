<?php
/**
 * Tests for the Enqueued_Scripts_In_Footer_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Enqueued_Scripts_In_Footer_Check;

class Enqueued_Scripts_In_Footer_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$enqueued_scripts_in_footer_check = new Enqueued_Scripts_In_Footer_Check();
		$check_context                    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-scripts-in-footer-check-with-errors/load.php' );
		$check_result                     = new Check_Result( $check_context );

		$enqueued_scripts_in_footer_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );
		$this->assertEquals( 2, $check_result->get_warning_count() );

		// Check for WordPress.WP.EnqueuedResourceParameters.MissingVersion warning on Line no 9 and column no at 9.
		$this->assertArrayHasKey( 9, $warnings['load.php'] );
		$this->assertArrayHasKey( 9, $warnings['load.php'][9] );
		$this->assertArrayHasKey( 'code', $warnings['load.php'][9][9][0] );
		$this->assertEquals( 'WordPress.WP.EnqueuedResourceParameters.MissingVersion', $warnings['load.php'][9][9][0]['code'] );

		// Check for WordPress.WP.EnqueuedResourceParameters.NotInFooter warning on Line no 9 and column no at 9.
		$this->assertArrayHasKey( 'code', $warnings['load.php'][9][9][1] );
		$this->assertEquals( 'WordPress.WP.EnqueuedResourceParameters.NotInFooter', $warnings['load.php'][9][9][1]['code'] );
	}

	public function test_run_without_errors() {
		$enqueued_scripts_in_footer_check = new Enqueued_Scripts_In_Footer_Check();
		$check_context                    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-scripts-in-footer-check-without-errors/load.php' );
		$check_result                     = new Check_Result( $check_context );

		$enqueued_scripts_in_footer_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );

		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
