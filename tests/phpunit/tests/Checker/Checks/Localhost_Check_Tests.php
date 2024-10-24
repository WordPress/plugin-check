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
		$this->assertArrayNotHasKey( 'load.php', $errors ); // Localhost in comment should not be error.
		$this->assertArrayHasKey( 'another.php', $errors );

		$this->assertCount( 1, wp_list_filter( $errors['another.php'][2][15], array( 'code' => 'PluginCheck.CodeAnalysis.Localhost.Found' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['another.php'][3][15], array( 'code' => 'PluginCheck.CodeAnalysis.Localhost.Found' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['another.php'][4][15], array( 'code' => 'PluginCheck.CodeAnalysis.Localhost.Found' ) ) );
	}
}
