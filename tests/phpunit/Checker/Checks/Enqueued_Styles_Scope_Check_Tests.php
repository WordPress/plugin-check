<?php
/**
 * Tests for the Enqueued_Styles_Scope_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Checks\Enqueued_Styles_Scope_Check;
use WordPress\Plugin_Check\Test_Utils\TestCase\Runtime_Check_UnitTestCase;

class Enqueued_Styles_Scope_Check_Tests extends Runtime_Check_UnitTestCase {

	public function test_run_with_errors() {
		// Load the test plugin.
		require TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-enqueued-styles-scope-check-with-error/load.php';

		$check   = new Enqueued_Styles_Scope_Check();
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertNotEmpty( $warnings );

		$this->assertEquals( 0, $results->get_error_count() );
		$this->assertEquals( 1, $results->get_warning_count() );
	}

	public function test_run_without_errors() {
		// Load the test plugin.
		require TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-enqueued-styles-scope-check-without-error/load.php';

		$check   = new Enqueued_Styles_Scope_Check();
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );

		$this->assertEquals( 0, $results->get_error_count() );
		$this->assertEquals( 0, $results->get_warning_count() );
	}
}
