<?php
/**
 * Tests for the Enqueued_Resources_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Performance\Enqueued_Resources_Check;

class Enqueued_Resources_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Enqueued_Resources_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-resources-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertEquals( 2, $check_result->get_error_count() );
	}

	public function test_run_without_errors() {
		$check         = new Enqueued_Resources_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-resources-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertEmpty( $errors );
		$this->assertEquals( 0, $check_result->get_error_count() );
	}
}
