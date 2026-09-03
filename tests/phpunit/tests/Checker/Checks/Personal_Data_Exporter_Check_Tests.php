<?php
/**
 * Tests for the Personal_Data_Exporter_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Personal_Data_Exporter_Check;

class Personal_Data_Exporter_Check_Tests extends WP_UnitTestCase {

	public function test_plugin_with_personal_data_but_no_exporter_triggers_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-personal-data-exporter-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Exporter_Check();
		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );

		$found = false;
		foreach ( $warnings as $file_warnings ) {
			foreach ( $file_warnings as $line_warnings ) {
				foreach ( $line_warnings as $col_warnings ) {
					foreach ( $col_warnings as $warning ) {
						if ( isset( $warning['code'] ) && 'missing_personal_data_exporter' === $warning['code'] ) {
							$found = true;
							break 4;
						}
					}
				}
			}
		}

		$this->assertTrue( $found, 'Expected missing_personal_data_exporter warning was not found.' );
	}

	public function test_plugin_with_personal_data_and_exporter_has_no_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-personal-data-exporter-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Exporter_Check();
		$check->run( $check_result );

		$found = false;
		foreach ( $check_result->get_warnings() as $file_warnings ) {
			foreach ( $file_warnings as $line_warnings ) {
				foreach ( $line_warnings as $col_warnings ) {
					foreach ( $col_warnings as $warning ) {
						if ( isset( $warning['code'] ) && 'missing_personal_data_exporter' === $warning['code'] ) {
							$found = true;
							break 4;
						}
					}
				}
			}
		}

		$this->assertFalse( $found, 'Unexpected missing_personal_data_exporter warning was found.' );
	}

	public function test_plugin_with_no_personal_data_has_no_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-safe-redirect/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Exporter_Check();
		$check->run( $check_result );

		$found = false;
		foreach ( $check_result->get_warnings() as $file_warnings ) {
			foreach ( $file_warnings as $line_warnings ) {
				foreach ( $line_warnings as $col_warnings ) {
					foreach ( $col_warnings as $warning ) {
						if ( isset( $warning['code'] ) && 'missing_personal_data_exporter' === $warning['code'] ) {
							$found = true;
							break 4;
						}
					}
				}
			}
		}

		$this->assertFalse( $found, 'Unexpected missing_personal_data_exporter warning on a plugin with no personal data.' );
	}

	public function test_plugin_with_wpdb_insert_no_exporter_triggers_warning() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-personal-data-exporter-with-wpdb-insert/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Personal_Data_Exporter_Check();
		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );

		$found = false;
		foreach ( $warnings as $file_warnings ) {
			foreach ( $file_warnings as $line_warnings ) {
				foreach ( $line_warnings as $col_warnings ) {
					foreach ( $col_warnings as $warning ) {
						if ( isset( $warning['code'] ) && 'missing_personal_data_exporter' === $warning['code'] ) {
							$found = true;
							break 4;
						}
					}
				}
			}
		}

		$this->assertTrue( $found, 'Expected missing_personal_data_exporter warning for $wpdb->insert was not found.' );
	}
}
