<?php
/**
 * Tests for the Plugin_Readme_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Readme_Check;

class Plugin_Readme_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors_no_readme() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-plugin-readme-errors-no-readme/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );
		$this->assertEquals( 1, $check_result->get_warning_count() );

		// Check for no readme file warning.
		$this->assertArrayHasKey( 0, $warnings['readme.txt'] );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'][0] );
		$this->assertArrayHasKey( 'code', $warnings['readme.txt'][0][0][0] );
		$this->assertEquals( 'plugin_readme.does_not_exist', $warnings['readme.txt'][0][0][0]['code'] );
	}

	public function test_run_with_errors() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-plugin-readme-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );
		$this->assertEquals( 2, $check_result->get_warning_count() );

		// Check for valid license warning.
		$this->assertArrayHasKey( 0, $warnings['readme.txt'] );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'][0] );
		$this->assertArrayHasKey( 'code', $warnings['readme.txt'][0][0][0] );
		$this->assertEquals( 'plugin_readme.missing_license', $warnings['readme.txt'][0][0][0]['code'] );

		// Check for valid stable tag warning.
		$this->assertArrayHasKey( 0, $warnings['readme.txt'] );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'][0] );
		$this->assertArrayHasKey( 'code', $warnings['readme.txt'][0][0][1] );
		$this->assertEquals( 'plugin_readme.missing_stable_tag', $warnings['readme.txt'][0][0][1]['code'] );
	}

	public function test_run_without_errors() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-plugin-readme-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
