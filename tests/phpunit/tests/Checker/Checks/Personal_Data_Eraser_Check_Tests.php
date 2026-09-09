<?php
/**
 * Tests for the Personal_Data_Eraser_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Personal_Data_Eraser_Check;

class Personal_Data_Eraser_Check_Tests extends WP_UnitTestCase {

	public function test_plugin_with_personal_data_but_no_eraser_triggers_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-personal-data-eraser-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Eraser_Check();
		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );

		$found = false;
		foreach ( $warnings as $file_warnings ) {
			foreach ( $file_warnings as $line_warnings ) {
				foreach ( $line_warnings as $col_warnings ) {
					foreach ( $col_warnings as $warning ) {
						if ( isset( $warning['code'] ) && 'missing_personal_data_eraser' === $warning['code'] ) {
							$found = true;
							break 4;
						}
					}
				}
			}
		}

		$this->assertTrue( $found, 'Expected missing_personal_data_eraser warning was not found.' );
	}

	public function test_plugin_with_personal_data_and_eraser_has_no_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-personal-data-eraser-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Eraser_Check();
		$check->run( $check_result );

		$found = false;
		foreach ( $check_result->get_warnings() as $file_warnings ) {
			foreach ( $file_warnings as $line_warnings ) {
				foreach ( $line_warnings as $col_warnings ) {
					foreach ( $col_warnings as $warning ) {
						if ( isset( $warning['code'] ) && 'missing_personal_data_eraser' === $warning['code'] ) {
							$found = true;
							break 4;
						}
					}
				}
			}
		}

		$this->assertFalse( $found, 'Unexpected missing_personal_data_eraser warning was found.' );
	}

	public function test_plugin_with_no_personal_data_has_no_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-safe-redirect/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Eraser_Check();
		$check->run( $check_result );

		$found = false;
		foreach ( $check_result->get_warnings() as $file_warnings ) {
			foreach ( $file_warnings as $line_warnings ) {
				foreach ( $line_warnings as $col_warnings ) {
					foreach ( $col_warnings as $warning ) {
						if ( isset( $warning['code'] ) && 'missing_personal_data_eraser' === $warning['code'] ) {
							$found = true;
							break 4;
						}
					}
				}
			}
		}

		$this->assertFalse( $found, 'Unexpected missing_personal_data_eraser warning on a plugin with no personal data.' );
	}

	public function test_plugin_with_wpdb_write_but_no_eraser_triggers_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-personal-data-eraser-with-wpdb-insert/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Eraser_Check();
		$check->run( $check_result );

		$this->assertTrue(
			$this->has_warning_code( $check_result, 'missing_personal_data_eraser' ),
			'Expected missing_personal_data_eraser warning for a $wpdb write was not found.'
		);
	}

	public function test_plugin_with_eraser_registered_only_in_tests_directory_triggers_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-personal-data-eraser-tests-dir/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Eraser_Check();
		$check->run( $check_result );

		$this->assertTrue(
			$this->has_warning_code( $check_result, 'missing_personal_data_eraser' ),
			'Expected missing_personal_data_eraser warning despite an eraser registered under the tests directory.'
		);
	}

	public function test_plugin_with_personal_data_only_in_comments_and_strings_has_no_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-personal-data-eraser-comment-only/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Eraser_Check();
		$check->run( $check_result );

		$this->assertFalse(
			$this->has_warning_code( $check_result, 'missing_personal_data_eraser' ),
			'Unexpected missing_personal_data_eraser warning when personal data appears only in comments and strings.'
		);
	}

	public function test_plugin_mentioning_eraser_filter_only_in_comment_triggers_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-personal-data-eraser-comment-filter/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Eraser_Check();
		$check->run( $check_result );

		$this->assertTrue(
			$this->has_warning_code( $check_result, 'missing_personal_data_eraser' ),
			'Expected missing_personal_data_eraser warning when the eraser filter is only mentioned in a comment.'
		);
	}

	/**
	 * Checks whether the given result contains a warning with the given code.
	 *
	 * @param Check_Result $result The check result to inspect.
	 * @param string       $code   The warning code to look for.
	 * @return bool True if a matching warning exists, false otherwise.
	 */
	private function has_warning_code( Check_Result $result, string $code ): bool {
		foreach ( $result->get_warnings() as $file_warnings ) {
			foreach ( $file_warnings as $line_warnings ) {
				foreach ( $line_warnings as $col_warnings ) {
					foreach ( $col_warnings as $warning ) {
						if ( isset( $warning['code'] ) && $code === $warning['code'] ) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}
}
