<?php
/**
 * Tests for the Plugin_Updater_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Updater_Check;

class Plugin_Updater_Check_Tests extends WP_UnitTestCase {

	/**
	 * @dataProvider data_plugin_updater_check
	 */
	public function test_run_with_plugin_updater_errors( $type_flag, $plugin_basename, $expected_file, $code, $error ) {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . $plugin_basename );
		$check_result  = new Check_Result( $check_context );

		$check = new Plugin_Updater_Check( $type_flag );
		$check->run( $check_result );

		if ( $error ) {
			$errors = $check_result->get_errors();

			$this->assertNotEmpty( $errors );
			$this->assertArrayHasKey( $expected_file, $errors );
			$this->assertSame( 1, $check_result->get_error_count() );

			$this->assertTrue( isset( $errors[ $expected_file ][0][0][0] ) );
			$this->assertSame( $code, $errors[ $expected_file ][0][0][0]['code'] );
		} else {
			$warnings = $check_result->get_warnings();

			$this->assertNotEmpty( $warnings );
			$this->assertArrayHasKey( $expected_file, $warnings );
			$this->assertSame( 1, $check_result->get_warning_count() );

			$this->assertTrue( isset( $warnings[ $expected_file ][0][0][0] ) );
			$this->assertSame( $code, $warnings[ $expected_file ][0][0][0]['code'] );
		}
	}

	public function data_plugin_updater_check() {
		return array(
			'Update URI Header'      => array(
				Plugin_Updater_Check::TYPE_PLUGIN_UPDATE_URI_HEADER,
				'test-plugin-update-uri-header-errors/load.php',
				'load.php',
				'plugin_updater_detected',
				true,
			),
			'Updater File'           => array(
				Plugin_Updater_Check::TYPE_PLUGIN_UPDATER_FILE,
				'test-plugin-updater-file-errors/load.php',
				'plugin-update-checker.php',
				'plugin_updater_detected',
				true,
			),
			'Plugin Updaters'        => array(
				Plugin_Updater_Check::TYPE_PLUGIN_UPDATERS,
				'test-plugin-updaters-errors/load.php',
				'load.php',
				'plugin_updater_detected',
				true,
			),
			'Plugin Updaters Regex'  => array(
				Plugin_Updater_Check::TYPE_PLUGIN_UPDATERS,
				'test-plugin-updaters-regex-errors/load.php',
				'load.php',
				'plugin_updater_detected',
				true,
			),
			'Updater Routines'       => array(
				Plugin_Updater_Check::TYPE_PLUGIN_UPDATER_ROUTINES,
				'test-plugin-updater-routines-errors/load.php',
				'load.php',
				'update_modification_detected',
				false,
			),
			'Updater Routines Regex' => array(
				Plugin_Updater_Check::TYPE_PLUGIN_UPDATER_ROUTINES,
				'test-plugin-updater-routines-regex-errors/load.php',
				'load.php',
				'update_modification_detected',
				false,
			),
		);
	}

	public function test_run_without_any_errors() {
		// Test plugin without any plugin updater.
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-i18n-usage-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Plugin_Updater_Check();
		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );

		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
