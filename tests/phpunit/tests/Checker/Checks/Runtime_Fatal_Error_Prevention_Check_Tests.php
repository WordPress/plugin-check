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

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );

		// OptionalFunctionCallUnguarded error at line 20.
		$line_20_errors = array();
		if ( isset( $errors['load.php'][20] ) ) {
			foreach ( $errors['load.php'][20] as $column => $msgs ) {
				$line_20_errors = array_merge( $line_20_errors, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_20_errors, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.OptionalFunctionCallUnguarded' ) ) );

		// OptionalClassInstantiationUnguarded error at line 22.
		$line_22_errors = array();
		if ( isset( $errors['load.php'][22] ) ) {
			foreach ( $errors['load.php'][22] as $column => $msgs ) {
				$line_22_errors = array_merge( $line_22_errors, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_22_errors, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.OptionalClassInstantiationUnguarded' ) ) );

		// DynamicCallbackInvocationUnguarded error at line 24.
		$line_24_errors = array();
		if ( isset( $errors['load.php'][24] ) ) {
			foreach ( $errors['load.php'][24] as $column => $msgs ) {
				$line_24_errors = array_merge( $line_24_errors, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_24_errors, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.DynamicCallbackInvocationUnguarded' ) ) );

		// DynamicCallbackCallUserFuncUnguarded error at line 26.
		$line_26_errors = array();
		if ( isset( $errors['load.php'][26] ) ) {
			foreach ( $errors['load.php'][26] as $column => $msgs ) {
				$line_26_errors = array_merge( $line_26_errors, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_26_errors, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.DynamicCallbackCallUserFuncUnguarded' ) ) );

		// HookedCallbackMethodNotFound error at line 30.
		$line_30_errors = array();
		if ( isset( $errors['load.php'][30] ) ) {
			foreach ( $errors['load.php'][30] as $column => $msgs ) {
				$line_30_errors = array_merge( $line_30_errors, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_30_errors, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.HookedCallbackMethodNotFound' ) ) );

		// DynamicImportUnguarded warning at line 18.
		$line_18_warnings = array();
		if ( isset( $warnings['load.php'][18] ) ) {
			foreach ( $warnings['load.php'][18] as $column => $msgs ) {
				$line_18_warnings = array_merge( $line_18_warnings, $msgs );
			}
		}
		$this->assertCount( 1, wp_list_filter( $line_18_warnings, array( 'code' => 'PluginCheck.CodeAnalysis.RuntimeFatalErrorPrevention.DynamicImportUnguarded' ) ) );

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
