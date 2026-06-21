<?php
/**
 * Tests for the Runtime_Fatal_Error_Prevention_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Runtime_Fatal_Error_Prevention_Check;

class Runtime_Fatal_Error_Prevention_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Runtime_Fatal_Error_Prevention_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-runtime-fatal-error-prevention-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		// All findings are emitted as warnings to avoid blocking submission on
		// potentially legitimate integration patterns.
		$this->assertEmpty( $errors );
		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );

		// DynamicImportUnguarded warning at line 18.
		$line_18_warnings = array();
		if ( isset( $warnings['load.php'][18] ) ) {
			foreach ( $warnings['load.php'][18] as $column => $msgs ) {
				$line_18_warnings = array_merge( $line_18_warnings, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_18_warnings, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.DynamicImportUnguarded' ) ) );

		// OptionalFunctionCallUnguarded warning at line 20.
		$line_20_warnings = array();
		if ( isset( $warnings['load.php'][20] ) ) {
			foreach ( $warnings['load.php'][20] as $column => $msgs ) {
				$line_20_warnings = array_merge( $line_20_warnings, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_20_warnings, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.OptionalFunctionCallUnguarded' ) ) );

		// OptionalClassInstantiationUnguarded warning at line 22.
		$line_22_warnings = array();
		if ( isset( $warnings['load.php'][22] ) ) {
			foreach ( $warnings['load.php'][22] as $column => $msgs ) {
				$line_22_warnings = array_merge( $line_22_warnings, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_22_warnings, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.OptionalClassInstantiationUnguarded' ) ) );

		// DynamicCallbackInvocationUnguarded warning at line 24.
		$line_24_warnings = array();
		if ( isset( $warnings['load.php'][24] ) ) {
			foreach ( $warnings['load.php'][24] as $column => $msgs ) {
				$line_24_warnings = array_merge( $line_24_warnings, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_24_warnings, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.DynamicCallbackInvocationUnguarded' ) ) );

		// DynamicCallbackCallUserFuncUnguarded warning at line 26.
		$line_26_warnings = array();
		if ( isset( $warnings['load.php'][26] ) ) {
			foreach ( $warnings['load.php'][26] as $column => $msgs ) {
				$line_26_warnings = array_merge( $line_26_warnings, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_26_warnings, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.DynamicCallbackCallUserFuncUnguarded' ) ) );

		// HookedCallbackMethodNotFound warning at line 30.
		$line_30_warnings = array();
		if ( isset( $warnings['load.php'][30] ) ) {
			foreach ( $warnings['load.php'][30] as $column => $msgs ) {
				$line_30_warnings = array_merge( $line_30_warnings, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_30_warnings, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.HookedCallbackMethodNotFound' ) ) );

		// HookedCallbackMethodNotFoundWarning warning at line 36.
		$line_36_warnings = array();
		if ( isset( $warnings['load.php'][36] ) ) {
			foreach ( $warnings['load.php'][36] as $column => $msgs ) {
				$line_36_warnings = array_merge( $line_36_warnings, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_36_warnings, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.HookedCallbackMethodNotFoundWarning' ) ) );
	}

	public function test_run_without_errors() {
		$check         = new Runtime_Fatal_Error_Prevention_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-runtime-fatal-error-prevention-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
	}
}
