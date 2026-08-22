<?php

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\General\PHP_Error_Reporting_Check;

class PHP_Error_Reporting_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_warnings() {
		$check        = new PHP_Error_Reporting_Check();
		$context      = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-php-error-reporting-with-errors/load.php' );
		$check_result = new Check_Result( $context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );

		// Assert exact per-line coverage so the test fails if any specific pattern stops being detected.
		$expected = array(
			12 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.DirectErrorReportingCall',
			14 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.DirectErrorReportingCall',
			16 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.IniDirectiveDisplay_errors',
			18 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.IniDirectiveError_reporting',
			20 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.IniDirectiveDisplay_errors',
			22 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.IniDirectiveError_reporting',
			24 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.DefineWP_DEBUG',
			26 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.DefineWP_DEBUG_LOG',
			28 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.DefineWP_DEBUG_DISPLAY',
			30 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.DefineSCRIPT_DEBUG',
			32 => 'PluginCheck.CodeAnalysis.PHPErrorReporting.ConstWP_DEBUG',
		);
		$this->assertCount( 11, $warnings['load.php'], 'Expected exactly 11 distinct lines to be flagged.' );
		$this->assertEquals( 0, $check_result->get_error_count(), 'No errors should be produced.' );

		foreach ( $expected as $line => $expected_code ) {
			$line_warnings = $warnings['load.php'][ $line ] ?? array();
			$this->assertNotEmpty( $line_warnings, "Expected a warning on line {$line}, but none was found." );

			$first_column_warnings = reset( $line_warnings );
			$warning_data          = reset( $first_column_warnings );

			$this->assertEquals( $expected_code, $warning_data['code'], "Line {$line} has the wrong warning code." );
			$this->assertEquals( 8, $warning_data['severity'], "Line {$line} has the wrong severity." );
		}
	}

	public function test_run_without_errors() {
		$check        = new PHP_Error_Reporting_Check();
		$context      = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-php-error-reporting-without-errors/load.php' );
		$check_result = new Check_Result( $context );

		$check->run( $check_result );

		$this->assertEquals( 0, $check_result->get_warning_count() );
		$this->assertEquals( 0, $check_result->get_error_count() );
	}
}
