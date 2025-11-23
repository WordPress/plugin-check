<?php
/**
 * Tests for the Plugin_Uninstall_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Plugin_Uninstall_Check;

class Plugin_Uninstall_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_missing_constant_check() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-uninstall-constant-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Plugin_Uninstall_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'uninstall.php', $errors );

		// Check for missing constant check.
		$this->assertCount( 1, wp_list_filter( $errors['uninstall.php'][0][0], array( 'code' => 'uninstall_missing_constant_check' ) ) );
	}
}
