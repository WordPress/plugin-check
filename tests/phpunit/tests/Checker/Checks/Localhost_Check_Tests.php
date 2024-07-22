<?php
/**
 * Tests for the Localhost_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Localhost_Check;

class Localhost_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$localhost_check = new Localhost_Check();
		$check_context   = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-localhost-with-errors/load.php' );
		$check_result    = new Check_Result( $check_context );

		$localhost_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertArrayHasKey( 'another.php', $errors );
		$this->assertSame( 4, $check_result->get_error_count() );

		$this->assertArrayHasKey( 19, $errors['load.php'] );
		$this->assertArrayHasKey( 24, $errors['load.php'][19] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][19][24], array( 'code' => 'localhost_code_detected' ) ) );

		$this->assertArrayHasKey( 2, $errors['another.php'] );
		$this->assertArrayHasKey( 35, $errors['another.php'][2] );
		$this->assertCount( 1, wp_list_filter( $errors['another.php'][2][35], array( 'code' => 'localhost_code_detected' ) ) );

		$this->assertArrayHasKey( 3, $errors['another.php'] );
		$this->assertArrayHasKey( 30, $errors['another.php'][3] );
		$this->assertCount( 1, wp_list_filter( $errors['another.php'][3][30], array( 'code' => 'localhost_code_detected' ) ) );

		$this->assertArrayHasKey( 4, $errors['another.php'] );
		$this->assertArrayHasKey( 27, $errors['another.php'][4] );
		$this->assertCount( 1, wp_list_filter( $errors['another.php'][4][27], array( 'code' => 'localhost_code_detected' ) ) );
	}
}
