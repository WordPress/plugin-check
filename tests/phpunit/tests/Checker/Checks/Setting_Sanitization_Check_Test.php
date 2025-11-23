<?php
/**
 * Tests for the Setting_Sanitization_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Setting_Sanitization_Check;

class Setting_Sanitization_Check_Test extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Setting_Sanitization_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-setting-sanitization-check-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );

		$this->assertSame( 'PluginCheck.CodeAnalysis.SettingSanitization.register_settingMissing', $errors['load.php'][19][1][0]['code'] );
		$this->assertSame( 'PluginCheck.CodeAnalysis.SettingSanitization.register_settingInvalid', $errors['load.php'][20][1][0]['code'] );
	}
}
