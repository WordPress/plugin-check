<?php
/**
 * Tests for the Direct_DB_Queries_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Direct_DB_Queries_Check;

class Direct_DB_Queries_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Direct_DB_Queries_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-db-queries-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );
		$this->assertEquals( 6, $check_result->get_warning_count() );
	}

	public function test_run_without_errors() {
		$check         = new Direct_DB_Queries_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-db-queries-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
