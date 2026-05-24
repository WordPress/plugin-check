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

	public function test_run() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-unclosed-ob-start/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Unclosed_Ob_Start_Check();
		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertArrayHasKey( 'load.php', $warnings );
		$this->assertCount( 3, $warnings['load.php'] );

		// 14: ob_start() at the top of the file with no closing call.
		$this->assertArrayHasKey( 14, $warnings['load.php'] );
		$this->assertEquals( 'unclosed_ob_start', $warnings['load.php'][14][0][0]['code'] );
		$this->assertEquals( 14, $warnings['load.php'][14][0][0]['line'] );

		// 18: Multiple ob_start() in the same scope, only one closed.
		$this->assertArrayHasKey( 18, $warnings['load.php'] );
		$this->assertEquals( 'unclosed_ob_start', $warnings['load.php'][18][0][0]['code'] );
		$this->assertEquals( 18, $warnings['load.php'][18][0][0]['line'] );

		// 32: ob_start() inside a function, closed only conditionally (if).
		$this->assertArrayHasKey( 32, $warnings['load.php'] );
		$this->assertEquals( 'unclosed_ob_start', $warnings['load.php'][32][0][0]['code'] );
		$this->assertEquals( 32, $warnings['load.php'][32][0][0]['line'] );
	}
}
