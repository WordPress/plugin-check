<?php
/**
 * Tests for the Error_Reporting_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\General\Error_Reporting_Check;

class Error_Reporting_Check_Tests extends WP_UnitTestCase {

	/**
	 * Test running the check on a plugin with error reporting configuration modifications.
	 *
	 * @since 1.9.0
	 */
	public function test_run_with_errors() {
		$check        = new Error_Reporting_Check();
		$context      = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-error-reporting-with-errors/load.php' );
		$check_result = new Check_Result( $context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );

		$file_warnings = $warnings['load.php'];

		// Assert we caught all error_reporting, ini_set, ini_alter, define calls.
		$codes = array();
		foreach ( $file_warnings as $line => $columns ) {
			foreach ( $columns as $column => $messages ) {
				foreach ( $messages as $message ) {
					$codes[] = $message['code'];
				}
			}
		}

		$this->assertContains( 'PluginCheck.CodeAnalysis.ErrorReporting.ChangingErrorReportingFound', $codes );
		$this->assertContains( 'PluginCheck.CodeAnalysis.ErrorReporting.ChangingIniSettingFound', $codes );
		$this->assertContains( 'PluginCheck.CodeAnalysis.ErrorReporting.ChangingDebugConstantFound', $codes );
	}

	/**
	 * Test running the check on a plugin without error reporting configuration modifications.
	 *
	 * @since 1.9.0
	 */
	public function test_run_without_errors() {
		$check        = new Error_Reporting_Check();
		$context      = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-error-reporting-without-errors/load.php' );
		$check_result = new Check_Result( $context );

		$check->run( $check_result );

		$this->assertEmpty( $check_result->get_errors() );
		$this->assertEmpty( $check_result->get_warnings() );
	}
}
