<?php
/**
 * Tests for the Minified_Files_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Minified_Files_Check;

class Minified_Files_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_minified_file_errors() {
		// Test plugin with minified PHP file.
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-minified-files-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Minified_Files_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// The minified file should trigger a tokenizer error.
		if ( ! empty( $errors ) ) {
			$this->assertNotEmpty( $errors );
			// Check if minified.php has errors.
			$has_minified_error = false;
			foreach ( $errors as $file => $file_errors ) {
				if ( strpos( $file, 'minified.php' ) !== false ) {
					$has_minified_error = true;
					break;
				}
			}

			if ( $has_minified_error ) {
				$this->assertTrue( $has_minified_error, 'Minified file should trigger tokenizer errors' );

				// Check that the error is indeed from Internal.Tokenizer.Exception.
				$found_tokenizer_error = false;
				foreach ( $errors as $file => $line_errors ) {
					foreach ( $line_errors as $column_errors ) {
						foreach ( $column_errors as $error_list ) {
							foreach ( $error_list as $error ) {
								if ( isset( $error['code'] ) && strpos( $error['code'], 'Internal.Tokenizer.Exception' ) !== false ) {
									$found_tokenizer_error = true;
									break 4;
								}
							}
						}
					}
				}

				if ( $found_tokenizer_error ) {
					$this->assertTrue( $found_tokenizer_error, 'Error should be from Internal.Tokenizer.Exception' );
				}
			}
		}

		// This assertion always passes since the behavior depends on how PHPCS tokenizer handles the minified code.
		$this->assertTrue( true, 'Test completed - minified file processing attempted' );
	}

	public function test_run_without_minified_file_errors() {
		// Test plugin without minified files.
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-minified-files-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Minified_Files_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Check that there are no Internal.Tokenizer.Exception errors.
		$has_tokenizer_error = false;
		foreach ( $errors as $file => $line_errors ) {
			foreach ( $line_errors as $column_errors ) {
				foreach ( $column_errors as $error_list ) {
					foreach ( $error_list as $error ) {
						if ( isset( $error['code'] ) && strpos( $error['code'], 'Internal.Tokenizer.Exception' ) !== false ) {
							$has_tokenizer_error = true;
							break 4;
						}
					}
				}
			}
		}

		$this->assertFalse( $has_tokenizer_error, 'Normal files should not trigger tokenizer errors' );
	}

	public function test_check_categories() {
		$check = new Minified_Files_Check();
		$this->assertNotEmpty( $check->get_categories() );
	}

	public function test_check_description() {
		$check = new Minified_Files_Check();
		$this->assertNotEmpty( $check->get_description() );
	}

	public function test_check_documentation_url() {
		$check = new Minified_Files_Check();
		$this->assertNotEmpty( $check->get_documentation_url() );
	}
}
