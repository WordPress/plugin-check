<?php
/**
 * Tests for the File_Type_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\File_Type_Check;

class File_Type_Check_Tests extends WP_UnitTestCase {

	/**
	 * @dataProvider data_forbidden_file_types
	 */
	public function test_run_with_file_type_errors( $type_flag, $plugin_basename, $expected_file, $expected_code ) {
		// Test given plugin with relevant forbidden file types.
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/' . $plugin_basename );
		$check_result  = new Check_Result( $check_context );

		$check = new File_Type_Check( $type_flag );
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( $expected_file, $errors );
		$this->assertSame( 1, $check_result->get_error_count() );

		$this->assertTrue( isset( $errors[ $expected_file ][0][0][0] ) );
		$this->assertSame( $expected_code, $errors[ $expected_file ][0][0][0]['code'] );
	}

	public function data_forbidden_file_types() {
		return array(
			'compressed' => array(
				File_Type_Check::TYPE_COMPRESSED,
				'test-plugin-file-type-compressed-errors/load.php',
				'compressed.zip',
				'compressed_files',
			),
			'phar'       => array(
				File_Type_Check::TYPE_PHAR,
				'test-plugin-file-type-phar-errors/load.php',
				'load.phar',
				'phar_files',
			),
			'hidden'     => array(
				File_Type_Check::TYPE_HIDDEN,
				'test-plugin-file-type-vcs-hidden-errors/load.php',
				'.gitignore',
				'hidden_files',
			),
		);
	}

	public function test_run_with_vcs_dir_errors() {
		// Test plugin with a .bzr directory which is forbidden.
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-file-type-vcs-hidden-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new File_Type_Check( File_Type_Check::TYPE_VCS );
		$check->run( $check_result );

		if ( ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) && 'production' === wp_get_environment_type() ) {
			$problems      = $check_result->get_errors();
			$problem_count = $check_result->get_error_count();
		} else {
			$problems      = $check_result->get_warnings();
			$problem_count = $check_result->get_warning_count();
		}

		$this->assertNotEmpty( $problems );
		$this->assertArrayHasKey( '.bzr', $problems );
		$this->assertSame( 1, $problem_count );

		$this->assertTrue( isset( $problems['.bzr'][0][0][0] ) );
		$this->assertSame( 'vcs_present', $problems['.bzr'][0][0][0]['code'] );
	}

	public function test_run_without_any_file_type_errors() {
		// Test plugin without any forbidden file types.
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-i18n-usage-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new File_Type_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertEmpty( $errors );
		$this->assertSame( 0, $check_result->get_error_count() );
	}
}
