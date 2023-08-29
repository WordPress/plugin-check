<?php
/**
 * Tests for the Performant_WP_Query_Params_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Performant_WP_Query_Params_Check;

class Performant_WP_Query_Params_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$performant_query = new Performant_WP_Query_Params_Check();
		$check_context    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-performant-wp-query-params-errors/load.php' );
		$check_result     = new Check_Result( $check_context );

		$performant_query->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );
		$this->assertEquals( 3, $check_result->get_warning_count() );

		// Check for WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in error.
		$this->assertArrayHasKey( 24, $warnings['load.php'] );
		$this->assertArrayHasKey( 9, $warnings['load.php'][24] );
		$this->assertArrayHasKey( 'code', $warnings['load.php'][24][9][0] );
		$this->assertEquals( 'WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in', $warnings['load.php'][24][9][0]['code'] );

		// Check for WordPress.DB.SlowDBQuery.slow_db_query_meta_query warning.
		$this->assertArrayHasKey( 27, $warnings['load.php'] );
		$this->assertArrayHasKey( 9, $warnings['load.php'][27] );
		$this->assertArrayHasKey( 'code', $warnings['load.php'][27][9][0] );
		$this->assertEquals( 'WordPress.DB.SlowDBQuery.slow_db_query_meta_query', $warnings['load.php'][27][9][0]['code'] );

		// Check for WordPress.DB.SlowDBQuery.slow_db_query_tax_query warning.
		$this->assertArrayHasKey( 34, $warnings['load.php'] );
		$this->assertArrayHasKey( 9, $warnings['load.php'][34] );
		$this->assertArrayHasKey( 'code', $warnings['load.php'][34][9][0] );
		$this->assertEquals( 'WordPress.DB.SlowDBQuery.slow_db_query_tax_query', $warnings['load.php'][34][9][0]['code'] );
	}

	public function test_run_without_errors() {
		$performant_query = new Performant_WP_Query_Params_Check();
		$check_context    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-performant-wp-query-params-without-errors/load.php' );
		$check_result     = new Check_Result( $check_context );

		$performant_query->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
