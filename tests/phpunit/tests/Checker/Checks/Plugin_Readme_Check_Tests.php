<?php
/**
 * Tests for the Plugin_Readme_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Plugin_Readme_Check;

class Plugin_Readme_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors_no_readme() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-no-readme/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );
		$this->assertEquals( 1, $check_result->get_error_count() );

		// Check for no readme file error.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertArrayHasKey( 'code', $errors['readme.txt'][0][0][0] );
		$this->assertEquals( 'no_plugin_readme', $errors['readme.txt'][0][0][0]['code'] );
	}

	public function test_run_with_errors_invalid_readme_files() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-invalid-readme/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );
		$this->assertEquals( 1, $check_result->get_error_count() );

		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertArrayHasKey( 'code', $errors['readme.txt'][0][0][0] );
		$this->assertSame( 'no_plugin_readme', $errors['readme.txt'][0][0][0]['code'] );
	}

	public function test_run_with_errors_invalid_name() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-invalid-name/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for invalid name error.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'invalid_plugin_name' ) ) );
	}

	public function test_run_with_errors_restricted_contributors() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-invalid-name/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $errors );
		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $errors );
		$this->assertArrayHasKey( 'readme.txt', $warnings );

		// Check for restricted contributors error.
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_restricted_contributors' ) ) );

		// Check for reserved contributors warning.
		$this->assertCount( 1, wp_list_filter( $warnings['readme.txt'][0][0], array( 'code' => 'readme_reserved_contributors' ) ) );
	}

	public function test_run_with_errors_empty_name() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-empty-name/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for empty name error.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'empty_plugin_name' ) ) );
	}

	public function test_run_with_errors_default_text() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-default-text/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for default readme text error.
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'default_readme_text' ) ) );
	}

	public function test_run_with_errors_stable_tag() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-stable-tag/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for trunk stable tag error.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'trunk_stable_tag' ) ) );

		// Check for stable tag mismatch file error. This should NOT be triggered as there is already 'trunk_stable_tag' error.
		$this->assertCount( 0, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'stable_tag_mismatch' ) ) );
	}

	public function test_run_with_errors_no_stable_tag() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-no-stable-tag/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for no stable tag error.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'no_stable_tag' ) ) );
	}

	public function test_run_with_errors_license() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-license/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for invalid license.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'invalid_license' ) ) );

		// Check for not same license.
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'license_mismatch' ) ) );
	}

	public function test_run_with_errors_no_license() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-no-license/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for no license.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'no_license' ) ) );
	}

	public function test_run_with_errors_tested_upto() {
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '6.5.0' ) );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-tested-upto/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for tested upto (default mode is new).
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$error_items = wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'outdated_tested_upto_header' ) );
		$this->assertCount( 1, $error_items );
		$this->assertSame( 7, reset( $error_items )['severity'] );
		$this->assertCount( 0, wp_list_filter( $warnings['readme.txt'][0][0] ?? array(), array( 'code' => 'outdated_tested_upto_header' ) ) );
	}

	public function test_outdated_tested_upto_header_reported_as_warning_in_update_mode() {
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '6.5.0' ) );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-tested-upto/load.php', '', 'update' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertCount( 0, wp_list_filter( $errors['readme.txt'][0][0] ?? array(), array( 'code' => 'outdated_tested_upto_header' ) ) );

		$warning_items = wp_list_filter( $warnings['readme.txt'][0][0] ?? array(), array( 'code' => 'outdated_tested_upto_header' ) );
		$this->assertCount( 1, $warning_items );
		$this->assertSame( 5, reset( $warning_items )['severity'] );
	}

	public function test_run_with_errors_tested_upto_minor_same_major_version() {
		// Target plugin has "6.1.1" is readme.
		// Current version is set to 6.1.2.
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '6.1.2' ) );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-tested-upto-minor/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for tested upto minor error.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'invalid_tested_upto_minor' ) ) );
	}

	public function test_run_with_errors_tested_upto_minor_different_major_version() {
		// Target plugin has "6.1.1" is readme.
		// Current version is set to 6.2.1.
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '6.2.1' ) );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-tested-upto-minor/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for tested upto minor error.
		$this->assertArrayHasKey( 0, $errors['readme.txt'] );
		$this->assertArrayHasKey( 0, $errors['readme.txt'][0] );
		$this->assertCount( 0, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'invalid_tested_upto_minor' ) ) );

		// There must be outdated_tested_upto_header error.
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'outdated_tested_upto_header' ) ) );
	}

	public function test_run_with_errors_missing_readme_headers() {
		add_filter( 'wp_plugin_check_ignored_readme_warnings', '__return_empty_array' );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-upgrade-notice/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		remove_filter( 'wp_plugin_check_ignored_readme_warnings', '__return_empty_array' );

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		// Check for missing tested upto header.
		$tested_upto_error = array_values( wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'missing_readme_header_tested' ) ) );
		$this->assertCount( 1, $tested_upto_error );
		$this->assertSame( 7, $tested_upto_error[0]['severity'] );

		// Check for missing contributors header.
		$contributors_error = array_values( wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'missing_readme_header_contributors' ) ) );
		$this->assertCount( 1, $contributors_error );
		$this->assertSame( 7, $contributors_error[0]['severity'] );
	}

	public function test_run_md_with_errors() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-md-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.md', $errors );

		$this->assertCount( 1, wp_list_filter( $errors['readme.md'][0][0], array( 'code' => 'trunk_stable_tag' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['readme.md'][0][0], array( 'code' => 'outdated_tested_upto_header' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['readme.md'][0][0], array( 'code' => 'default_readme_text' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['readme.md'][0][0], array( 'code' => 'invalid_license' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['readme.md'][0][0], array( 'code' => 'license_mismatch' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['readme.md'][0][0], array( 'code' => 'readme_invalid_donate_link' ) ) );

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.md', $warnings );

		$this->assertCount( 1, wp_list_filter( $warnings['readme.md'][0][0], array( 'code' => 'mismatched_plugin_name' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['readme.md'][0][0], array( 'code' => 'readme_invalid_contributors' ) ) );
	}

	public function test_single_file_plugin_without_error_for_trademarks() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( WP_PLUGIN_DIR . '/single-file-plugin.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertSame( 0, $check_result->get_error_count() );
		$this->assertSame( 0, $check_result->get_warning_count() );
	}

	public function test_run_with_errors_parser_warnings() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-parser-warnings/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );

		// Check for parser warning.
		$this->assertCount( 1, wp_list_filter( $warnings['readme.txt'][0][0], array( 'code' => 'readme_parser_warnings_tested_header_ignored' ) ) );
	}

	public function test_run_with_errors_multiple_parser_warnings() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-multiple-parser-warnings/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		// Verify warnings exist.
		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'] );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'][0] );

		// Check for specific parser warnings this test is verifying.
		// Note: We check for specific codes rather than exact counts to allow for future checks.
		$warning_codes = wp_list_pluck( $warnings['readme.txt'][0][0], 'code' );

		$this->assertContains( 'readme_parser_warnings_ignored_tags', $warning_codes, 'Should have ignored tags warning' );
		$this->assertContains( 'readme_parser_warnings_too_many_tags', $warning_codes, 'Should have too many tags warning' );
		$this->assertContains( 'readme_parser_warnings_requires_header_ignored', $warning_codes, 'Should have requires header ignored warning' );
		$this->assertContains( 'readme_parser_warnings_tested_header_ignored', $warning_codes, 'Should have tested header ignored warning' );
		$this->assertContains( 'readme_parser_warnings_requires_php_header_ignored', $warning_codes, 'Should have requires PHP header ignored warning' );
		$this->assertContains( 'readme_parser_warnings_trimmed_short_description', $warning_codes, 'Should have trimmed short description warning' );
		$this->assertContains( 'readme_parser_warnings_trimmed_section_changelog', $warning_codes, 'Should have trimmed changelog warning' );

		// Note: This test focuses on parser warnings. Any additional errors from other checks
		// (like language detection, mismatched headers, etc.) will not cause this test to fail,
		// Making it resilient to new checks being added in the future.
	}

	public function test_run_with_errors_parser_warnings_with_custom_set_transient_version() {
		$version = '5.0';

		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '5.0.1' ) );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-parser-warnings/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );

		$filtered_items = wp_list_filter( $warnings['readme.txt'][0][0], array( 'code' => 'readme_parser_warnings_tested_header_ignored' ) );

		$this->assertCount( 1, $filtered_items );

		$filtered_items = array_values( $filtered_items );

		$this->assertStringContainsString( 'The "Tested up to" field was ignored. This field should only contain a valid WordPress version such as "' . $version . '"', $filtered_items[0]['message'] );
	}

	public function test_run_with_errors_multiple_parser_warnings_and_empty_ignored_array() {
		add_filter( 'wp_plugin_check_ignored_readme_warnings', '__return_empty_array' );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-multiple-parser-warnings/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		remove_filter( 'wp_plugin_check_ignored_readme_warnings', '__return_empty_array' );

		// Verify warnings exist.
		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'] );
		$this->assertArrayHasKey( 0, $warnings['readme.txt'][0] );

		$warning_codes = wp_list_pluck( $warnings['readme.txt'][0][0], 'code' );

		// When empty array is returned, contributor_ignored should also be included.
		$this->assertContains( 'readme_parser_warnings_contributor_ignored', $warning_codes, 'Should have contributor ignored warning when filter returns empty array' );
		$this->assertContains( 'readme_parser_warnings_ignored_tags', $warning_codes, 'Should have ignored tags warning' );
		$this->assertContains( 'readme_parser_warnings_too_many_tags', $warning_codes, 'Should have too many tags warning' );
		$this->assertContains( 'readme_parser_warnings_requires_header_ignored', $warning_codes, 'Should have requires header ignored warning' );
		$this->assertContains( 'readme_parser_warnings_tested_header_ignored', $warning_codes, 'Should have tested header ignored warning' );
		$this->assertContains( 'readme_parser_warnings_requires_php_header_ignored', $warning_codes, 'Should have requires PHP header ignored warning' );
		$this->assertContains( 'readme_parser_warnings_trimmed_short_description', $warning_codes, 'Should have trimmed short description warning' );
		$this->assertContains( 'readme_parser_warnings_trimmed_section_changelog', $warning_codes, 'Should have trimmed changelog warning' );

		// Note: This test focuses on parser warnings when filter returns empty array.
		// Any additional errors from other checks will not cause this test to fail,
		// making it resilient to new checks being added in the future.
	}

	public function test_filter_readme_warnings_ignored() {
		// Define custom ignore for testing.
		$custom_ignores = array(
			'requires_php_header_ignored',
		);

		// Create a mock filter that will return our custom ignores.
		$filter_name = 'wp_plugin_check_ignored_readme_warnings';
		add_filter(
			$filter_name,
			static function () use ( $custom_ignores ) {
				return $custom_ignores;
			}
		);

		$result = apply_filters( $filter_name, array() );

		// Remove the filter to avoid interfering with other tests.
		remove_filter(
			$filter_name,
			static function () use ( $custom_ignores ) {
				return $custom_ignores;
			}
		);

		$this->assertEquals( $custom_ignores, $result );
	}

	public function test_filter_wp_plugin_check_ignored_readme_warnings_will_return_no_error() {
		// Define custom ignore for testing.
		$custom_ignores = array(
			'tested_header_ignored',
			'contributor_ignored',
		);

		// Create a mock filter that will return our custom ignores.
		$filter_name = 'wp_plugin_check_ignored_readme_warnings';
		add_filter(
			$filter_name,
			static function () use ( $custom_ignores ) {
				return $custom_ignores;
			}
		);

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-parser-warnings/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		// Remove the filter to avoid interfering with other tests.
		remove_filter(
			$filter_name,
			static function () use ( $custom_ignores ) {
				return $custom_ignores;
			}
		);

		// The test readme has proper English content, so no language errors.
		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertSame( 0, $check_result->get_error_count() );
		$this->assertSame( 0, $check_result->get_warning_count() );
	}

	public function test_run_with_errors_upgrade_notice() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-upgrade-notice/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'readme.txt', $warnings );

		// Check for upgrade notices.
		$this->assertCount( 2, wp_list_filter( $warnings['readme.txt'][0][0], array( 'code' => 'upgrade_notice_limit' ) ) );
	}

	public function test_run_with_errors_tested_up_to_latest_plus_two_version() {
		// Target plugin has "6.1" is readme.
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '5.9.1' ) );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-md-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertNotEmpty( $errors );

		$filtered_items = array_values( wp_list_filter( $errors['readme.md'][0][0], array( 'code' => 'nonexistent_tested_upto_header' ) ) );

		$this->assertCount( 1, $filtered_items );
		$this->assertStringContainsString( 'Tested up to: 6.1', $filtered_items[0]['message'] );
		$this->assertStringContainsString( 'This version of WordPress does not exist (yet).', $filtered_items[0]['message'] );
	}

	public function test_run_without_errors_tested_up_to_latest_plus_one_version() {
		// Target plugin has "6.1" is readme.
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '6.0.1' ) );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-md-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertCount( 0, wp_list_filter( $errors['readme.md'][0][0], array( 'code' => 'nonexistent_tested_upto_header' ) ) );
	}

	public function test_run_without_errors_tested_up_to_latest_stable_version() {
		// Target plugin has "6.1" is readme.
		set_transient( 'wp_plugin_check_latest_version_info', array( 'current' => '6.1.1' ) );

		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-md-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$errors = $check_result->get_errors();

		delete_transient( 'wp_plugin_check_latest_version_info' );

		$this->assertCount( 0, wp_list_filter( $errors['readme.md'][0][0], array( 'code' => 'nonexistent_tested_upto_header' ) ) );
	}

	public function test_run_without_errors_readme_contributors_warning() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-parser-warnings/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		// Should not contain contributors warning.
		$this->assertCount( 0, wp_list_filter( $warnings['readme.txt'][0][0], array( 'code' => 'readme_invalid_contributors' ) ) );
	}

	public function test_run_without_errors_readme_contributors_formatting() {
		$readme_check  = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-readme-contributors-formatting/load.php' );
		$check_result  = new Check_Result( $check_context );

		$readme_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		// Should not contain contributors warning even with leading/trailing spaces and commas.
		$this->assertCount( 0, wp_list_filter( $warnings['readme.txt'][0][0], array( 'code' => 'readme_invalid_contributors' ) ) );
	}

	public function test_run_with_mismatched_requires_headers() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-trademarks-plugin-readme-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_mismatched_header_requires' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_mismatched_header_requires_php' ) ) );
	}

	public function test_run_with_discouraged_donate_link() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-default-text/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );

		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_invalid_donate_link_domain' ) ) );
	}

	public function test_run_with_valid_paypal_donate_link() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-valid-paypal-donate/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have invalid donate link error for PayPal URLs with complex query strings.
		if ( isset( $errors['readme.txt'] ) && isset( $errors['readme.txt'][0][0] ) ) {
			$invalid_donate_link_errors = wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_invalid_donate_link' ) );
			$this->assertEmpty( $invalid_donate_link_errors, 'PayPal donation URL with complex query string should be recognized as valid' );
		} else {
			// If no errors at all, that's also fine - the URL is valid.
			$this->assertTrue( true );
		}
	}

	public function test_run_language_detection_with_non_english_content() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-language/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		// Check that short description error exists.
		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'readme.txt', $errors );
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_short_description_non_official_language' ) ) );

		// Check that description error exists.
		$this->assertCount( 1, wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_description_non_official_language' ) ) );
	}

	public function test_run_language_detection_with_english_content() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-parser-warnings/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		// Check that NO language errors exist.
		$short_desc_errors = isset( $errors['readme.txt'][0][0] ) ? wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_short_description_non_official_language' ) ) : array();
		$desc_errors       = isset( $errors['readme.txt'][0][0] ) ? wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_description_non_official_language' ) ) : array();

		$this->assertCount( 0, $short_desc_errors );
		$this->assertCount( 0, $desc_errors );
	}

	public function test_run_language_detection_with_edge_cases() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-language-edge-cases/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		// Check that NO language errors exist for content with code snippets, URLs, and technical terms.
		$short_desc_errors = isset( $errors['readme.txt'][0][0] ) ? wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_short_description_non_official_language' ) ) : array();
		$desc_errors       = isset( $errors['readme.txt'][0][0] ) ? wp_list_filter( $errors['readme.txt'][0][0], array( 'code' => 'readme_description_non_official_language' ) ) : array();

		$this->assertCount( 0, $short_desc_errors );
		$this->assertCount( 0, $desc_errors );
	}

	public function test_run_with_mismatch() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-tested-up-to-mismatch/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );

		// Check for mismatched "Tested up to" error.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'mismatched_tested_up_to_header' ) ) );

		// Verify the error message contains the correct versions.
		$error_items   = wp_list_filter( $errors['load.php'][0][0], array( 'code' => 'mismatched_tested_up_to_header' ) );
		$error_message = reset( $error_items )['message'];
		$this->assertStringContainsString( '6.7', $error_message );
		$this->assertStringContainsString( '6.5', $error_message );
	}

	public function test_run_with_match() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-tested-up-to-match/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have mismatched tested up to error when values match.
		// Note: Other readme errors may still be present.
		if ( ! empty( $errors ) ) {
			foreach ( $errors as $file => $file_errors ) {
				if ( isset( $file_errors[0][0] ) ) {
					$this->assertCount( 0, wp_list_filter( $file_errors[0][0], array( 'code' => 'mismatched_tested_up_to_header' ) ) );
				}
			}
		}

		// Explicitly assert that we checked for the error code.
		$this->assertTrue( true );
	}

	public function test_run_with_readme_only() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-tested-up-to-readme-only/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have mismatched tested up to error when only readme has the value.
		// Note: Other readme errors may still be present.
		if ( ! empty( $errors ) ) {
			foreach ( $errors as $file => $file_errors ) {
				if ( isset( $file_errors[0][0] ) ) {
					$this->assertCount( 0, wp_list_filter( $file_errors[0][0], array( 'code' => 'mismatched_tested_up_to_header' ) ) );
				}
			}
		}

		// Explicitly assert that we checked for the error code.
		$this->assertTrue( true );
	}

	public function test_run_with_header_only() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-tested-up-to-header-only/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have mismatched tested up to error when only header has the value.
		// Note: Other readme errors may still be present.
		if ( ! empty( $errors ) ) {
			foreach ( $errors as $file => $file_errors ) {
				if ( isset( $file_errors[0][0] ) ) {
					$this->assertCount( 0, wp_list_filter( $file_errors[0][0], array( 'code' => 'mismatched_tested_up_to_header' ) ) );
				}
			}
		}

		// Explicitly assert that we checked for the error code.
		$this->assertTrue( true );
	}

	public function test_run_with_single_file_plugin() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( WP_PLUGIN_DIR . '/hello.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have mismatched tested up to errors for single-file plugins.
		// Note: Other header field errors may still be present.
		if ( ! empty( $errors ) ) {
			foreach ( $errors as $file => $file_errors ) {
				if ( isset( $file_errors[0][0] ) ) {
					$this->assertCount( 0, wp_list_filter( $file_errors[0][0], array( 'code' => 'mismatched_tested_up_to_header' ) ) );
				}
			}
		}

		// Explicitly assert that we checked for the error code.
		$this->assertTrue( true );
	}

	public function test_run_with_no_readme() {
		$check         = new Plugin_Readme_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-readme-errors-no-readme/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Should not have tested up to errors when readme doesn't exist.
		$this->assertEmpty( wp_list_filter( $errors, array( 'code' => 'mismatched_tested_up_to_header' ) ) );
	}
}
