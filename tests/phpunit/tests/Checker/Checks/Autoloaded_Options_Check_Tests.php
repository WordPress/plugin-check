<?php
/**
 * Tests for the Autoloaded_Options_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Performance\Autoloaded_Options_Check;

class Autoloaded_Options_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Autoloaded_Options_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-autoloaded-options-check-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );

		// Both add_option calls without $autoload must produce warnings.
		$this->assertSame(
			'PluginCheck.CodeAnalysis.AutoLoadedOptions.add_option_autoloadMissing',
			$warnings['load.php'][19][1][0]['code']
		);
		$this->assertSame(
			'PluginCheck.CodeAnalysis.AutoLoadedOptions.add_option_autoloadMissing',
			$warnings['load.php'][22][1][0]['code']
		);

		// Both update_option calls without $autoload must produce warnings.
		$this->assertSame(
			'PluginCheck.CodeAnalysis.AutoLoadedOptions.update_option_autoloadMissing',
			$warnings['load.php'][25][1][0]['code']
		);
		$this->assertSame(
			'PluginCheck.CodeAnalysis.AutoLoadedOptions.update_option_autoloadMissing',
			$warnings['load.php'][28][1][0]['code']
		);
	}

	public function test_run_without_errors() {
		$check         = new Autoloaded_Options_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-autoloaded-options-check-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$this->assertEmpty( $check_result->get_errors() );
		$this->assertEmpty( $check_result->get_warnings() );
	}
}
