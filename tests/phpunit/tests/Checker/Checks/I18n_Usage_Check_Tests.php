<?php
/**
 * Tests for the I18n_Usage_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\General\I18n_Usage_Check;

class I18n_Usage_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$i18n_usage_check = new I18n_Usage_Check();
		$check_context    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-i18n-usage-errors/load.php' );
		$check_result     = new Check_Result( $check_context );

		$i18n_usage_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );

		// Check for WordPress.WP.I18n.MissingTranslatorsComment error on Line no 26 and column no at 5.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][26][5], array( 'code' => 'WordPress.WP.I18n.MissingTranslatorsComment' ) ) );

		// Check for WordPress.WP.I18n.TextDomainMismatch error on Line no 26 and column no at 29.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][26][29], array( 'code' => 'WordPress.WP.I18n.TextDomainMismatch' ) ) );

		// Check for WordPress.WP.I18n.NonSingularStringLiteralDomain error on Line no 33 and column no at 29.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][33][29], array( 'code' => 'WordPress.WP.I18n.NonSingularStringLiteralDomain' ) ) );
	}

	public function test_run_without_errors() {
		$i18n_usage_check = new I18n_Usage_Check();
		$check_context    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-i18n-usage-without-errors/load.php' );
		$check_result     = new Check_Result( $check_context );

		$i18n_usage_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}

	public function test_run_without_default_textdomain() {
		$i18n_usage_check = new I18n_Usage_Check();
		$check_context    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-i18n-usage-with-default/load.php' );
		$check_result     = new Check_Result( $check_context );

		$i18n_usage_check->run( $check_result );

		// Explicitly using the 'default' text domain is a warning, omitting a text domain is an error.
		$this->assertNotEmpty( $check_result->get_errors() );
		$this->assertNotEmpty( $check_result->get_warnings() );
		$this->assertEquals( 1, $check_result->get_error_count() );
		$this->assertEquals( 1, $check_result->get_warning_count() );
	}
}
