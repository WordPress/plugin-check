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
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-no-readme/load.php' );
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
		$this->assertEquals( 'no_plugin_readme', $warnings['readme.txt'][0][0][0]['code'] );
	}

	public function test_run_with_errors_default_text() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-default-text/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );
		$this->assertEquals( 1, $check_result->get_warning_count() );

		// Check for default text file warning.
		$this->assertArrayHasKey( 0, $warnings['readme.txt'] );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'][0] );
		$this->assertArrayHasKey( 'code', $warnings['readme.txt'][0][0][0] );
		$this->assertEquals( 'default_readme_text', $warnings['readme.txt'][0][0][0]['code'] );
	}

	public function test_run_with_errors_stable_tag() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-stable-tag/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );
		$this->assertEquals( 2, $check_result->get_error_count() );

		// Check for trunk stable tag error.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertArrayHasKey( 'code', $errors['readme.txt'][0][0][0] );
		$this->assertEquals( 'trunk_stable_tag', $errors['readme.txt'][0][0][0]['code'] );

		// Check for stable tag mismatch file error.
		$this->assertArrayHasKey( 'code', $errors['readme.txt'][0][0][1] );
		$this->assertEquals( 'stable_tag_mismatch', $errors['readme.txt'][0][0][1]['code'] );
	}

	public function test_run_with_errors_license() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-license/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );
		$this->assertEquals( 1, $check_result->get_warning_count() );

		// Check for invalid license warning.
		$this->assertArrayHasKey( 0, $warnings['readme.txt'] );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'][0] );
		$this->assertArrayHasKey( 'code', $warnings['readme.txt'][0][0][0] );
		$this->assertEquals( 'invalid_license', $warnings['readme.txt'][0][0][0]['code'] );
	}

	public function test_run_without_errors() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}

	public function test_run_md_without_errors() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-md-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}

	public function test_run_md_with_errors() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-md-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.md', $errors );
		$this->assertEquals( 2, $check_result->get_error_count() );

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.md', $warnings );
		$this->assertEquals( 2, $check_result->get_warning_count() );

		// Check for default text file warning.
		$this->assertArrayHasKey( 0, $warnings['readme.md'] );
		$this->assertArrayHasKey( 0, $warnings['readme.md'][0] );
		$this->assertArrayHasKey( 'code', $warnings['readme.md'][0][0][0] );
		$this->assertEquals( 'default_readme_text', $warnings['readme.md'][0][0][0]['code'] );

		// Check for invalid license warning.
		$this->assertArrayHasKey( 'code', $warnings['readme.md'][0][0][1] );
		$this->assertEquals( 'invalid_license', $warnings['readme.md'][0][0][1]['code'] );

		// Check for trunk stable tag error.
		$this->assertArrayHasKey( 0, $errors['readme.md'] );
		$this->assertArrayHasKey( 0, $errors['readme.md'][0] );
		$this->assertArrayHasKey( 'code', $errors['readme.md'][0][0][0] );
		$this->assertEquals( 'trunk_stable_tag', $errors['readme.md'][0][0][0]['code'] );

		// Check for stable tag mismatch file error.
		$this->assertArrayHasKey( 'code', $errors['readme.md'][0][0][1] );
		$this->assertEquals( 'stable_tag_mismatch', $errors['readme.md'][0][0][1]['code'] );
	}

	public function test_run_root_readme_file_without_errors() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-root-readme-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}

	public function test_single_file_plugin_without_error_for_trademarks() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( WP_PLUGIN_DIR . '/single-file-plugin.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertSame( 0, $check_result->get_error_count() );
		$this->assertSame( 0, $check_result->get_warning_count() );
	}

	public function test_run_with_errors_parser_warnings() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-parser-warnings/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );
		$this->assertEquals( 1, $check_result->get_warning_count() );

		// Check for parser warning.
		$this->assertArrayHasKey( 0, $warnings['readme.txt'] );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'][0] );
		$this->assertArrayHasKey( 'code', $warnings['readme.txt'][0][0][0] );
		$this->assertEquals( 'readme_parser_warnings', $warnings['readme.txt'][0][0][0]['code'] );
	}

	public function test_filter_readme_warnings_ignored() {
		// Define custom ignore for testing.
		$custom_ignores = array(
			'requires_php_header_ignored',
		);

		// Create a mock filter that will return our custom ignores.
		$filter_name = 'wp_plugin_check_ignored_readme_warnings';
		add_filter(
			$filter_name,
			static function () use ( $custom_ignores ) {
				return $custom_ignores;
			}
		);

		$result = apply_filters( 'wp_plugin_check_ignored_readme_warnings', array() );

		$this->assertEquals( $custom_ignores, $result );

		// Remove the filter to avoid interfering with other tests.
		remove_filter(
			$filter_name,
			static function () use ( $custom_ignores ) {
				return $custom_ignores;
			}
		);
	}
}
