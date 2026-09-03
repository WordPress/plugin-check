<?php
/**
 * Tests for the Unclosed_Ob_Start_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Performance\Unclosed_Ob_Start_Check;

class Unclosed_Ob_Start_Check_Tests extends WP_UnitTestCase {

	public function test_get_categories() {
		$check      = new Unclosed_Ob_Start_Check();
		$categories = $check->get_categories();

		$this->assertIsArray( $categories );
		$this->assertContains( Check_Categories::CATEGORY_PERFORMANCE, $categories );
		$this->assertContains( Check_Categories::CATEGORY_PLUGIN_REPO, $categories );
		$this->assertCount( 2, $categories );
	}

	public function test_run_with_errors() {
		$check         = new Unclosed_Ob_Start_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-unclosed-ob-start/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );
		$this->assertEquals( 4, $check_result->get_warning_count() );

		$expected_code = 'PluginCheck.CodeAnalysis.UnclosedObStart.UnclosedObStart';

		// 14: ob_start() at the top of the file with no closing call.
		$this->assertArrayHasKey( 14, $warnings['load.php'] );
		$this->assertWarningCodeOnLine( $warnings['load.php'][14], $expected_code );

		// 18: Multiple ob_start() in the same scope, only one closed.
		$this->assertArrayHasKey( 18, $warnings['load.php'] );
		$this->assertWarningCodeOnLine( $warnings['load.php'][18], $expected_code );

		// 32: ob_start() inside a function, closed only conditionally (if).
		$this->assertArrayHasKey( 32, $warnings['load.php'] );
		$this->assertWarningCodeOnLine( $warnings['load.php'][32], $expected_code );

		// 56: Arrow function (T_FN) with unpaired ob_start().
		$this->assertArrayHasKey( 56, $warnings['load.php'] );
		$this->assertWarningCodeOnLine( $warnings['load.php'][56], $expected_code );
	}

	/**
	 * Asserts that the line's first warning carries the expected error code.
	 *
	 * Avoids brittle direct access into deeply nested array offsets whose indices
	 * shift when other warnings are added on the same line.
	 *
	 * @param array<int, array<int, array<string, mixed>>> $line_warnings Warnings recorded for a single source line, keyed by column.
	 * @param string                                       $expected_code The expected error code.
	 */
	private function assertWarningCodeOnLine( $line_warnings, $expected_code ) {
		$first_column = reset( $line_warnings );
		$this->assertIsArray( $first_column );
		$this->assertArrayHasKey( 0, $first_column );
		$this->assertArrayHasKey( 'code', $first_column[0] );
		$this->assertEquals( $expected_code, $first_column[0]['code'] );
	}
}
