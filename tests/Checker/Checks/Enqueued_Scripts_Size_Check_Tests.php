<?php
/**
 * Tests for the Enqueued_Scripts_Size_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Checks\Enqueued_Scripts_Size_Check;
use WordPress\Plugin_Check\Checker\Preparations\Universal_Runtime_Preparation;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Preparation;

class Enqueued_Scripts_Size_Check_Tests extends WP_UnitTestCase {

	public function test_get_shared_preparations() {
		$check        = new Enqueued_Scripts_Size_Check();
		$preparations = $check->get_shared_preparations();

		$this->assertIsArray( $preparations );

		foreach ( $preparations as $class => $args ) {
			$instance = new $class( ...$args );
			$this->assertInstanceOf( Preparation::class, $instance );
		}
	}

	public function test_run_without_errors() {
		$check         = new Enqueued_Scripts_Size_Check();
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/testdata/plugins/test-plugin-without-errors/test-plugin-without-errors.php' );
		$check_result  = new Check_Result( $check_context );
		$runtime_prep  = new Universal_Runtime_Preparation( $check_context );

		// Run the required preparations.
		$runtime_cleanup = $runtime_prep->prepare();
		$shared_cleanup  = $this->run_shared_preperations( $check );
		$check_cleanup   = $check->prepare();

		$check->run( $check_result );

		// Cleanup preparations.
		$check_cleanup();
		$shared_cleanup();
		$runtime_cleanup();

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );

		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}

	public function test_run_with_errors() {
		$check         = new Enqueued_Scripts_Size_Check();
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/testdata/plugins/test-plugin-with-errors/test-plugin-with-errors.php' );
		$check_result  = new Check_Result( $check_context );
		$runtime_prep  = new Universal_Runtime_Preparation( $check_context );

		// Run the required preparations.
		$runtime_cleanup = $runtime_prep->prepare();
		$shared_cleanup  = $this->run_shared_preperations( $check );
		$check_cleanup   = $check->prepare();

		$check->run( $check_result );

		// Cleanup preparations.
		$check_cleanup();
		$shared_cleanup();
		$runtime_cleanup();

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );

		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}

	protected function run_shared_preperations( $check ) {
		$preparations = $check->get_shared_preparations();
		$cleanups     = array();

		foreach ( $preparations as $class => $args ) {
			$preparation = new $class( ...$args );
			$cleanups    = $preparation->prepare();
		}

		return function() use ( $cleanups ) {
			foreach ( $cleanups as $cleanup ) {
				$cleanup();
			}
		};
	}
}
