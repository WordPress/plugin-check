<?php
/**
 * Tests for the Localhost_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Localhost_Check;

class Localhost_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$localhost_check = new Localhost_Check();
		$check_context   = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-localhost-with-errors/load.php' );
		$check_result    = new Check_Result( $check_context );

		$localhost_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertEquals( 1, $check_result->get_error_count() );

		$this->assertArrayHasKey( 0, $errors['load.php'] );
		$this->assertArrayHasKey( 0, $errors['load.php'][0] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][0][0][0] );
		$this->assertEquals( 'localhost_code_detected', $errors['load.php'][0][0][0]['code'] );
	}
}
