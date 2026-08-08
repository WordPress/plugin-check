<?php
/**
 * Tests for the Public_Content_Export_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Security\Public_Content_Export_Check;

class Public_Content_Export_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Public_Content_Export_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-public-content-export-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		// This is an advisory check — all findings are warnings, never errors.
		$this->assertEmpty( $errors );
		$this->assertSame( 0, $check_result->get_error_count() );

		// Should have warnings for post content export patterns.
		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );

		// Verify the expected warning code is present.
		$warning_codes = array();
		$warning_count = 0;
		foreach ( $warnings['load.php'] as $line => $columns ) {
			foreach ( $columns as $column => $messages ) {
				foreach ( $messages as $message ) {
					$warning_codes[] = $message['code'];
					++$warning_count;
				}
			}
		}

		$this->assertContains( 'PluginCheck.Security.PublicContentExport.PostContentExport', $warning_codes );
		$this->assertSame( 6, $warning_count );
	}

	public function test_run_without_errors() {
		$check         = new Public_Content_Export_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-public-content-export-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		// Should have no errors or warnings when access-control guards are present.
		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertSame( 0, $check_result->get_error_count() );
		$this->assertSame( 0, $check_result->get_warning_count() );
	}

	public function test_get_description() {
		$check = new Public_Content_Export_Check();
		$this->assertNotEmpty( $check->get_description() );
	}

	public function test_get_documentation_url() {
		$check = new Public_Content_Export_Check();
		$url   = $check->get_documentation_url();
		$this->assertNotEmpty( $url );
		$this->assertStringContainsString( 'developer.wordpress.org', $url );
	}
}
