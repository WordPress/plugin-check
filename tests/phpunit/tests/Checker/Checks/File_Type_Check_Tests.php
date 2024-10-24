<?php
/**
 * Tests for the File_Type_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\File_Type_Check;

class File_Type_Check_Tests extends WP_UnitTestCase {

	/**
	 * @dataProvider data_forbidden_file_types
	 */
	public function test_run_with_file_type_errors( $type_flag, $plugin_basename, $expected_file, $expected_code ) {
		// Test given plugin with relevant forbidden file types.
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . $plugin_basename );
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
			'compressed'  => array(
				File_Type_Check::TYPE_COMPRESSED,
				'test-plugin-file-type-compressed-errors/load.php',
				'compressed.zip',
				'compressed_files',
			),
			'phar'        => array(
				File_Type_Check::TYPE_PHAR,
				'test-plugin-file-type-phar-errors/load.php',
				'load.phar',
				'phar_files',
			),
			'hidden'      => array(
				File_Type_Check::TYPE_HIDDEN,
				'test-plugin-file-type-vcs-hidden-errors/load.php',
				'.gitignore',
				'hidden_files',
			),
			'application' => array(
				File_Type_Check::TYPE_APPLICATION,
				'test-plugin-file-type-application-errors/load.php',
				'hello-world.sh',
				'application_detected',
			),
		);
	}

	public function test_run_with_vcs_dir_errors() {
		// Test plugin with a .bzr directory which is forbidden.
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-file-type-vcs-hidden-errors/load.php' );
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
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-i18n-usage-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new File_Type_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertEmpty( $errors );
		$this->assertSame( 0, $check_result->get_error_count() );
	}

	public function test_run_with_badly_named_errors() {
		// Test plugin without any forbidden file types.
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-file-type-badly-named-files-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new File_Type_Check( File_Type_Check::TYPE_BADLY_NAMED );
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertEquals( 3, $check_result->get_error_count() );

		// Check for invalid name error.
		$this->assertArrayHasKey( 0, $errors['plugin name.php'] );
		$this->assertArrayHasKey( 0, $errors['plugin name.php'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['plugin name.php'][0][0], array( 'code' => 'badly_named_files' ) ) );

		// Badly named directory check.
		$this->assertArrayHasKey( 0, $errors['badly directory/file.php'] );
		$this->assertArrayHasKey( 0, $errors['badly directory/file.php'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['badly directory/file.php'][0][0], array( 'code' => 'badly_named_files' ) ) );

		// Badly named file with special chars.
		$this->assertArrayHasKey( 0, $errors['badly|file%name!@#$%^&*()+=[]{};:"\'<>,?|`~.php'] );
		$this->assertArrayHasKey( 0, $errors['badly|file%name!@#$%^&*()+=[]{};:"\'<>,?|`~.php'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['badly|file%name!@#$%^&*()+=[]{};:"\'<>,?|`~.php'][0][0], array( 'code' => 'badly_named_files' ) ) );
	}

	public function test_run_with_library_core_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-file-type-library-core-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new File_Type_Check( File_Type_Check::TYPE_LIBRARY_CORE );
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertEquals( 1, $check_result->get_error_count() );

		// Check for core PHPMailer.
		$this->assertArrayHasKey( 0, $errors['PHPMailer.php'] );
		$this->assertArrayHasKey( 0, $errors['PHPMailer.php'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['PHPMailer.php'][0][0], array( 'code' => 'library_core_files' ) ) );
	}
}
