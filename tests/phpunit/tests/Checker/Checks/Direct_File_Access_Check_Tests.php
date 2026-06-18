<?php
/**
 * Tests for the Direct_File_Access_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Direct_File_Access_Check;

class Direct_File_Access_Check_Tests extends WP_UnitTestCase {

	/**
	 * Test that files without direct access protection are flagged as errors.
	 */
	public function test_run_with_missing_guards() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'file-without-guard.php', $errors );
		$this->assertArrayHasKey( 'another-unprotected.php', $errors );

		// Check for missing direct file access protection error code.
		$this->assertCount( 1, wp_list_filter( $errors['file-without-guard.php'][0][0], array( 'code' => 'missing_direct_file_access_protection' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['another-unprotected.php'][0][0], array( 'code' => 'missing_direct_file_access_protection' ) ) );
	}

	/**
	 * Test that files with proper ABSPATH guards do not produce errors.
	 */
	public function test_run_with_abspath_guard() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Files with guards should not produce errors.
		$this->assertArrayNotHasKey( 'file-with-abspath-guard.php', $errors );
		$this->assertArrayNotHasKey( 'file-with-abspath-or-exit.php', $errors );
		$this->assertArrayNotHasKey( 'file-with-abspath-or-exit-no-parens.php', $errors );
		$this->assertArrayNotHasKey( 'file-with-abspath-or-die.php', $errors );
		$this->assertArrayNotHasKey( 'file-with-abspath-if-exit.php', $errors );
		$this->assertArrayNotHasKey( 'file-with-abspath-if-die.php', $errors );
		$this->assertArrayNotHasKey( 'file-with-wpinc-guard.php', $errors );
		$this->assertArrayNotHasKey( 'file-with-wpinc-or-exit.php', $errors );
		$this->assertArrayNotHasKey( 'file-with-comments-before-guard.php', $errors );
	}

	/**
	 * Test that uninstall.php files are skipped.
	 */
	public function test_run_skips_uninstall_file() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Uninstall.php should not be checked by this check.
		$this->assertArrayNotHasKey( 'uninstall.php', $errors );
	}

	/**
	 * Test that files containing only class/namespace definitions are allowed.
	 */
	public function test_run_allows_class_only_files() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Files with only class definitions should not produce errors.
		$this->assertArrayNotHasKey( 'includes/class-only-file.php', $errors );
		$this->assertArrayNotHasKey( 'includes/namespace-class-only.php', $errors );
	}

	/**
	 * Test that files with procedural code without guards are flagged.
	 */
	public function test_run_flags_procedural_code_without_guards() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Files with procedural code (functions, hooks) should be flagged.
		$this->assertArrayHasKey( 'file-without-guard.php', $errors );
		$this->assertArrayHasKey( 'another-unprotected.php', $errors );

		// Include files with procedural code should also be flagged.
		$this->assertArrayHasKey( 'includes/some-include.php', $errors );

		// Files with only classes should NOT be flagged (even without guard).
		$this->assertArrayNotHasKey( 'file-with-class-no-guard.php', $errors );
	}

	/**
	 * Test that files without guards have the correct error message.
	 */
	public function test_run_error_message() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertArrayHasKey( 'file-without-guard.php', $errors );
		$error = $errors['file-without-guard.php'][0][0][0];

		$this->assertSame( 'missing_direct_file_access_protection', $error['code'] );
		$this->assertStringContainsString( 'prevent direct access', $error['message'] );
		$this->assertStringContainsString( 'ABSPATH', $error['message'] );
	}

	/**
	 * Test that check has correct categories.
	 */
	public function test_get_categories() {
		$check      = new Direct_File_Access_Check();
		$categories = $check->get_categories();

		$this->assertContains( Check_Categories::CATEGORY_SECURITY, $categories );
		$this->assertContains( Check_Categories::CATEGORY_PLUGIN_REPO, $categories );
	}

	/**
	 * Test that check has description and documentation URL.
	 */
	public function test_check_metadata() {
		$check = new Direct_File_Access_Check();

		$this->assertNotEmpty( $check->get_description() );
		$this->assertNotEmpty( $check->get_documentation_url() );
		$this->assertStringContainsString( 'developer.wordpress.org', $check->get_documentation_url() );
	}

	/**
	 * Test that files with wrong ABSPATH guard (missing quotes) are flagged.
	 */
	public function test_run_flags_wrong_abspath_guard() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// File with wrong guard (ABSPATH without quotes) should be flagged.
		$this->assertArrayHasKey( 'file-with-wrong-abspath-guard.php', $errors );
	}

	/**
	 * Test that asset files (return-only) are allowed.
	 */
	public function test_run_allows_asset_files() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Asset files with only return statements should not produce errors.
		$this->assertArrayNotHasKey( 'asset-file-return-only.php', $errors );
	}

	/**
	 * Test that files with guards that have echo/header statements are allowed.
	 */
	public function test_run_allows_guards_with_echo_or_headers() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Files with guards (even with echo/header before exit) should pass.
		$this->assertArrayNotHasKey( 'file-with-guard-and-echo.php', $errors );
		$this->assertArrayNotHasKey( 'file-with-guard-and-headers.php', $errors );
	}

	/**
	 * Test that files with only safe function calls are allowed.
	 */
	public function test_run_allows_safe_function_calls() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Files with only safe function calls (class_exists, function_exists) should not produce errors.
		$this->assertArrayNotHasKey( 'file-with-safe-function-calls.php', $errors );
	}

	/**
	 * Test that interface-only files are allowed.
	 */
	public function test_run_allows_interface_only_files() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Files with only interface definitions should not produce errors.
		$this->assertArrayNotHasKey( 'includes/interface-only-file.php', $errors );
	}

	/**
	 * Test that files with only declare statements are allowed.
	 */
	public function test_run_allows_declare_only_files() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-file-access-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Direct_File_Access_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		// Files with only declare(strict_types=1) should not produce errors.
		$this->assertArrayNotHasKey( 'file-with-declare-only.php', $errors );
	}
}
