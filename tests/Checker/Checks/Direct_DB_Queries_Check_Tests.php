<?php
/**
 * Tests for the Direct_DB_Queries_Check_Tests class.
 *
 * @package plugin-check
 */

namespace Checker\Checks;

use WordPress\Plugin_Check\Checker\Checks\Direct_DB_Queries_Check;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WP_UnitTestCase;

class Direct_DB_Queries_Check_Tests extends WP_UnitTestCase {

	protected $direct_db_queries_check;

	public function set_up() {
		parent::set_up();

		$this->direct_db_queries_check = new Direct_DB_Queries_Check();
	}

	public function test_run_with_errors() {

		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/testdata/plugins/test-plugin-with-errors/test-plugin-with-errors.php' );

		$check_result = new Check_Result( $check_context );

		$this->direct_db_queries_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );

		$this->assertArrayHasKey( 'direct-db-query.php', $warnings );

		$this->assertEquals( 1, $check_result->get_error_count() );

		// Check for WordPress.WP.I18n.MissingTranslatorsComment error on Line no 9 and column no at 5.
		$this->assertArrayHasKey( 8, $warnings['direct-db-query.php'] );
		$this->assertArrayHasKey( 1, $warnings['direct-db-query.php'][8] );
		$this->assertArrayHasKey( 'code', $warnings['direct-db-query.php'][8][1][0] );
		$this->assertEquals( 'WordPress.DB.DirectDatabaseQuery.DirectQuery', $warnings['direct-db-query.php'][8][1][0]['code'] );

		$this->assertArrayHasKey( 'code', $warnings['direct-db-query.php'][8][1][1] );
		$this->assertEquals( 'WordPress.DB.DirectDatabaseQuery.NoCaching', $warnings['direct-db-query.php'][8][1][1]['code'] );
	}

	public function test_run_without_errors() {

		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/testdata/plugins/test-plugin-without-errors/test-plugin-without-errors.php' );

		$check_result = new Check_Result( $check_context );

		$this->direct_db_queries_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );

		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
