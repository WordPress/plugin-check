<?php
/**
 * Tests for the Unclosed_Ob_Start_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Performance\Unclosed_Ob_Start_Check;

class Unclosed_Ob_Start_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Unclosed_Ob_Start_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-unclosed-ob-start/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );
		$this->assertEquals( 3, $check_result->get_warning_count() );

		// 14: ob_start() at the top of the file with no closing call.
		$this->assertArrayHasKey( 14, $warnings['load.php'] );
		$this->assertEquals( 'PluginCheck.CodeAnalysis.UnclosedObStart.UnclosedObStart', $warnings['load.php'][14][1][0]['code'] );

		// 18: Multiple ob_start() in the same scope, only one closed.
		$this->assertArrayHasKey( 18, $warnings['load.php'] );
		$this->assertEquals( 'PluginCheck.CodeAnalysis.UnclosedObStart.UnclosedObStart', $warnings['load.php'][18][2][0]['code'] );

		// 32: ob_start() inside a function, closed only conditionally (if).
		$this->assertArrayHasKey( 32, $warnings['load.php'] );
		$this->assertEquals( 'PluginCheck.CodeAnalysis.UnclosedObStart.UnclosedObStart', $warnings['load.php'][32][2][0]['code'] );
	}
}
