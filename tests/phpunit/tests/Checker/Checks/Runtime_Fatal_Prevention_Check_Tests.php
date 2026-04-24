<?php
/**
 * Tests for the Runtime_Fatal_Prevention_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Runtime_Fatal_Prevention_Check;

class Runtime_Fatal_Prevention_Check_Tests extends WP_UnitTestCase {

	/**
	 * Ensures risky runtime patterns are reported with line information.
	 */
	public function test_run_with_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-runtime-fatal-prevention-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Runtime_Fatal_Prevention_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();
		$this->assertArrayHasKey( 'load.php', $errors );

		$issues = $this->flatten_file_issues( $errors['load.php'] );
		$codes  = array_values( array_unique( wp_list_pluck( $issues, 'code' ) ) );

		$this->assertContains( 'unguarded_dynamic_require', $codes );
		$this->assertContains( 'missing_function_exists_guard_for_integration_call', $codes );
		$this->assertContains( 'missing_class_exists_guard_for_optional_class', $codes );
		$this->assertContains( 'missing_is_callable_guard_for_dynamic_callback', $codes );
		$this->assertContains( 'unguarded_hook_callback_method', $codes );

		foreach ( $issues as $issue ) {
			$this->assertNotEmpty( $issue['line'] );
			$this->assertGreaterThan( 0, (int) $issue['line'] );
		}
	}

	/**
	 * Ensures clearly guarded patterns are not reported.
	 */
	public function test_run_without_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-runtime-fatal-prevention-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Runtime_Fatal_Prevention_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();
		$this->assertArrayNotHasKey( 'load.php', $errors );
	}

	/**
	 * Tests check metadata.
	 */
	public function test_check_metadata() {
		$check = new Runtime_Fatal_Prevention_Check();

		$this->assertContains( Check_Categories::CATEGORY_PLUGIN_REPO, $check->get_categories() );
		$this->assertNotEmpty( $check->get_description() );
		$this->assertNotEmpty( $check->get_documentation_url() );
		$this->assertStringContainsString( 'developer.wordpress.org', $check->get_documentation_url() );
	}

	/**
	 * Flattens nested file issue data.
	 *
	 * @param array $file_errors Nested issue structure.
	 * @return array
	 */
	private function flatten_file_issues( array $file_errors ) {
		$issues = array();

		foreach ( $file_errors as $line_items ) {
			foreach ( $line_items as $column_items ) {
				foreach ( $column_items as $issue ) {
					$issues[] = $issue;
				}
			}
		}

		return $issues;
	}
}
