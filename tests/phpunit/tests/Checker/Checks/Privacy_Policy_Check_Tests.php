<?php
/**
 * Tests for the Privacy_Policy_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Privacy_Policy_Check;

class Privacy_Policy_Check_Tests extends WP_UnitTestCase {

	/**
	 * Tests that a plugin using wp_remote_post() without wp_add_privacy_policy_content()
	 * receives a warning.
	 */
	public function test_run_with_errors() {
		$check         = new Privacy_Policy_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-privacy-policy-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );

		// Warning must be on the plugin's main file.
		$this->assertArrayHasKey( 'load.php', $warnings );

		// Verify the expected warning code is present.
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'missing_privacy_policy_content' ) ) );
	}

	/**
	 * Tests that a plugin using wp_remote_post() WITH wp_add_privacy_policy_content()
	 * does not receive any warnings.
	 */
	public function test_run_without_errors() {
		$check         = new Privacy_Policy_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-privacy-policy-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();
		$errors   = $check_result->get_errors();

		$this->assertEmpty( $warnings );
		$this->assertEmpty( $errors );
	}

	/**
	 * Tests that a plugin with no personal-data-handling patterns does not receive
	 * any warnings, even if it does not call wp_add_privacy_policy_content().
	 */
	public function test_run_with_no_signals() {
		$check         = new Privacy_Policy_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-privacy-policy-no-signals/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();
		$errors   = $check_result->get_errors();

		$this->assertEmpty( $warnings );
		$this->assertEmpty( $errors );
	}
}
