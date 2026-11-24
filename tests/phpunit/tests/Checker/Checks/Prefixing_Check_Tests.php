<?php
/**
 * Tests for the Prefixing_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Prefixing_Check;

class Prefixing_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Prefixing_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-prefixing-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );

		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][18][9], array( 'code' => 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][20][1], array( 'code' => 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][28][1], array( 'code' => 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][41][1], array( 'code' => 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound' ) ) );
	}

	public function test_loop_variables_not_flagged() {
		$check         = new Prefixing_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-prefixing-foreach/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		// Single-line foreach/for header lines: variables declared in
		// `as $key => $value` or `for ( $i = 0; ...` are loop-local and
		// must not be reported.
		$this->assertArrayNotHasKey( 21, $warnings['load.php'] ?? array() );
		$this->assertArrayNotHasKey( 26, $warnings['load.php'] ?? array() );
		$this->assertArrayNotHasKey( 31, $warnings['load.php'] ?? array() );

		// Multi-line foreach/for with `as`/`$var` on the same line.
		$this->assertArrayNotHasKey( 39, $warnings['load.php'] ?? array() );
		$this->assertArrayNotHasKey( 47, $warnings['load.php'] ?? array() );

		// Multi-line foreach with `as` and `$var` on separate lines.
		// $key on line 60 is still a loop header variable.
		$this->assertArrayNotHasKey( 60, $warnings['load.php'] ?? array() );

		// Multi-line for with multi-init on separate lines.
		// $i (line 68) and $j (line 69) are both init expressions.
		$this->assertArrayNotHasKey( 68, $warnings['load.php'] ?? array() );
		$this->assertArrayNotHasKey( 69, $warnings['load.php'] ?? array() );
	}
}
