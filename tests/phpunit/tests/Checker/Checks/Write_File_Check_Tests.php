<?php
/**
 * Tests for the Write_File_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Write_File_Check;

class Write_File_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$write_file_check = new Write_File_Check();
		$check_context    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-write-file-with-errors/load.php' );
		$check_result     = new Check_Result( $check_context );

		$write_file_check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should have errors for plugin directory writes.
		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'file-operations.php', $errors );

		// Check for specific error codes.
		$error_codes                 = array();
		$file_operations_error_count = 0;
		foreach ( $errors['file-operations.php'] as $line => $columns ) {
			foreach ( $columns as $column => $messages ) {
				foreach ( $messages as $message ) {
					$error_codes[] = $message['code'];
					++$file_operations_error_count;
				}
			}
		}

		// Should detect PluginDirectoryWrite errors.
		$this->assertContains( 'PluginCheck.CodeAnalysis.WriteFile.PluginDirectoryWrite', $error_codes );

		// file-operations.php should include direct and variable-indirection plugin path violations.
		$this->assertGreaterThanOrEqual( 8, $file_operations_error_count );
	}

	public function test_run_without_errors() {
		$write_file_check = new Write_File_Check();
		$check_context    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-write-file-without-errors/load.php' );
		$check_result     = new Check_Result( $check_context );

		$write_file_check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should have no errors when using wp_upload_dir() or temp directories.
		$this->assertEmpty( $errors );
		$this->assertSame( 0, $check_result->get_error_count() );
	}

	public function test_get_description() {
		$check = new Write_File_Check();
		$this->assertNotEmpty( $check->get_description() );
	}

	public function test_get_documentation_url() {
		$check = new Write_File_Check();
		$url   = $check->get_documentation_url();
		$this->assertNotEmpty( $url );
		$this->assertStringContainsString( 'developer.wordpress.org', $url );
	}
}
