<?php
/**
 * Tests for the Plugin_Header_Fields_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Plugin_Header_Fields_Check;

class Plugin_Header_Fields_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-header-fields-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $errors );
		$this->assertNotEmpty( $warnings );

		$this->assertCount( 0, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_restricted_fields' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_requires_wp' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_requires_php' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_no_license' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_missing_plugin_version' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_author_uri' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_plugin_uri_domain' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_plugin_description' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_network' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'textdomain_mismatch' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'plugin_header_nonexistent_domain_path' ) ) );

		if ( is_wp_version_compatible( '6.5' ) ) {
			$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_requires_plugins' ) ) );
		}
	}

	public function test_plugin_header_nonexistent_domain_path_skipped_in_update_mode() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-header-fields-with-errors/load.php', '', 'update' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertArrayHasKey( 'load.php', $warnings, 'Fixture should still emit warnings for the main plugin file in update mode.' );
		$this->assertCount(
			1,
			wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'textdomain_mismatch' ) ),
			'Unrelated warnings must still run so we know the check executed (same fixture as test_run_with_errors).'
		);
		$this->assertCount(
			0,
			wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'plugin_header_nonexistent_domain_path' ) )
		);
	}

	public function test_run_with_invalid_requires_wp_header() {
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '6.5.1' ) );

		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-header-fields-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertNotEmpty( $errors );

		$error_items = wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_requires_wp' ) );

		$this->assertCount( 1, $error_items );
		$this->assertStringContainsString( 'such as "6.5" or "6.4"', reset( $error_items )['message'] );
	}

	public function test_run_with_valid_requires_plugins_header() {
		/*
		 * Test plugin has following valid header.
		 * Requires Plugins: woocommerce, contact-form-7
		 */

		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-unfiltered-uploads-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		if ( is_wp_version_compatible( '6.5' ) ) {
			$this->assertCount( 0, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_requires_plugins' ) ) );
		} else {
			// For WordPress < 6.5, the check doesn't run.
			$this->assertTrue( true );
		}
	}

	public function test_run_with_mismatched_tested_up_to() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-tested-up-to-mismatch/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// The "Tested up to" mismatch check has been moved to Plugin_Readme_Check.
		// This test now verifies that Plugin_Header_Fields_Check does NOT report this error.
		$error_items = wp_list_filter( $errors['load.php'][0][0] ?? array(), array( 'code' => 'mismatched_tested_up_to_header' ) );
		$this->assertCount( 0, $error_items );
	}

	public function test_run_with_matching_tested_up_to() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-tested-up-to-match/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have mismatched tested up to error.
		$error_items = wp_list_filter( $errors['load.php'][0][0] ?? array(), array( 'code' => 'mismatched_tested_up_to_header' ) );
		$this->assertCount( 0, $error_items );
	}

	public function test_run_with_invalid_mpl1_license() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-mpl1-license-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );

		// Check for invalid license.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_license' ) ) );
	}

	public function test_run_with_valid_wtfpl_license() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-wtfpl-license/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have invalid license error for WTFPL.
		if ( isset( $errors['load.php'] ) && isset( $errors['load.php'][0][0] ) ) {
			$invalid_license_errors = wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_license' ) );
			$this->assertEmpty( $invalid_license_errors, 'WTFPL license should be recognized as GPL-compatible' );
		} else {
			// If no errors at all, that's also fine - the license is valid.
			$this->assertTrue( true );
		}
	}

	public function test_run_with_valid_eupl_license() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-eupl-license/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have invalid license error for EUPL.
		if ( isset( $errors['load.php'] ) && isset( $errors['load.php'][0][0] ) ) {
			$invalid_license_errors = wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_license' ) );
			$this->assertEmpty( $invalid_license_errors, 'EUPL license should be recognized as GPL-compatible' );
		} else {
			// If no errors at all, that's also fine - the license is valid.
			$this->assertTrue( true );
		}
	}

	public function test_run_with_invalid_header_fields() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-late-escaping-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );

		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_missing_plugin_description' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_invalid_plugin_version' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'textdomain_invalid_format' ) ) );
	}

	public function test_run_with_errors_requires_at_least_latest_plus_two_version() {
		// Target plugin has "6.0" in plugin header.
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '5.8.1' ) );

		$readme_check  = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-localhost-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertNotEmpty( $errors );

		$filtered_items = wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_nonexistent_requires_wp' ) );

		$this->assertCount( 1, $filtered_items );
		$this->assertStringContainsString( 'Requires at least: 6.0', $filtered_items[0]['message'] );
		$this->assertStringContainsString( 'This version of WordPress does not exist (yet).', $filtered_items[0]['message'] );
	}

	public function test_run_without_errors_requires_at_least_latest_plus_one_version() {
		// Target plugin has "6.0" in plugin header.
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '5.9.1' ) );

		$readme_check  = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-localhost-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertEmpty( $errors );
	}

	public function test_run_without_errors_requires_at_least_latest_version() {
		// Target plugin has "6.0" in plugin header.
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '6.0.1' ) );

		$readme_check  = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-localhost-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertEmpty( $errors );
	}

	public function test_run_with_unsupported_plugin_name_in_new_mode() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-unsupported-plugin-name/load.php', '', 'new' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_unsupported_plugin_name' ) ) );
	}

	public function test_run_with_unsupported_plugin_name_in_update_mode() {
		$check         = new Plugin_Header_Fields_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-unsupported-plugin-name/load.php', '', 'update' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have error in update mode.
		$this->assertCount( 0, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'plugin_header_unsupported_plugin_name' ) ) );
	}
}
