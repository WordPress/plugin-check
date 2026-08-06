<?php
/**
 * Tests for the Trialware_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Trialware_Check;

class Trialware_Check_Tests extends WP_UnitTestCase {

	public function test_trialware_with_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-trialware-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Trialware_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertSame( 5, $check_result->get_error_count() );

		// Verify each result code is present.
		$found_codes = array();
		foreach ( $errors as $file_errors ) {
			foreach ( $file_errors as $line_errors ) {
				foreach ( $line_errors as $col_errors ) {
					foreach ( $col_errors as $message ) {
						$found_codes[] = $message['code'];
					}
				}
			}
		}

		$this->assertContains( 'trialware_license_gate_candidate', $found_codes );
		$this->assertContains( 'trialware_pro_premium_gate_candidate', $found_codes );
		$this->assertContains( 'trialware_trial_gate_candidate', $found_codes );
		$this->assertContains( 'trialware_quota_gate_candidate', $found_codes );
		$this->assertContains( 'trialware_payment_gate_candidate', $found_codes );
	}

	public function test_trialware_without_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-trialware-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Trialware_Check();
		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );

		$this->assertSame( 0, $check_result->get_error_count() );
		$this->assertSame( 0, $check_result->get_warning_count() );
	}

	public function test_trialware_check_is_plugin_repo_category() {
		$check = new Trialware_Check();

		$this->assertSame( array( 'plugin_repo' ), $check->get_categories() );
	}

	public function test_trialware_check_has_description() {
		$check = new Trialware_Check();

		$this->assertNotEmpty( $check->get_description() );
	}

	public function test_trialware_check_has_documentation_url() {
		$check = new Trialware_Check();

		$this->assertNotEmpty( $check->get_documentation_url() );
		$this->assertStringContainsString( 'https://', $check->get_documentation_url() );
	}
}
