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

	public function test_run_without_any_errors() {
		// Test plugin without any plugin updater.
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-i18n-usage-without-errors/load.php' );
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

	public function test_look_for_update_uri_header() {
		// Test plugin without any plugin updater.
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-updater-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Plugin_Updater_Check();
		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertArrayHasKey( 'plugin-update-checker.php', $errors );
		$this->assertEquals( 3, $check_result->get_error_count() );

		// Check for plugin_updater_detected error on Line no 0 and column no at 0.
		$this->assertArrayHasKey( 0, $errors['load.php'] );
		$this->assertArrayHasKey( 0, $errors['load.php'][0] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][0][0][0] );
		$this->assertEquals( 'plugin_updater_detected', $errors['load.php'][0][0][0]['code'] );

		$this->assertArrayHasKey( 0, $errors['updater.php'] );
		$this->assertArrayHasKey( 0, $errors['updater.php'][0] );
		$this->assertArrayHasKey( 'code', $errors['updater.php'][0][0][0] );
		$this->assertEquals( 'plugin_updater_detected', $errors['updater.php'][0][0][0]['code'] );

		$this->assertArrayHasKey( 0, $errors['plugin-update-checker.php'] );
		$this->assertArrayHasKey( 0, $errors['plugin-update-checker.php'][0] );
		$this->assertArrayHasKey( 'code', $errors['plugin-update-checker.php'][0][0][0] );
		$this->assertEquals( 'plugin_updater_detected', $errors['plugin-update-checker.php'][0][0][0]['code'] );

		// Check for update_modification_detected warning on Line no 0 and column no at 0.
		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );
		$this->assertEquals( 1, $check_result->get_warning_count() );
		$this->assertArrayHasKey( 0, $warnings['load.php'] );
		$this->assertArrayHasKey( 0, $warnings['load.php'][0] );
		$this->assertArrayHasKey( 'code', $warnings['load.php'][0][0][0] );
		$this->assertEquals( 'update_modification_detected', $warnings['load.php'][0][0][0]['code'] );
	}
}
