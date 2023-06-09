<?php
/**
 * Tests for the Plugin_Header_Text_Domain_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Header_Text_Domain_Check;

class Plugin_Header_Text_Domain_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$plugin_header_text_domain_check = new Plugin_Header_Text_Domain_Check();
		$check_context                   = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-i18n-usage-errors/load.php' );
		$check_result                    = new Check_Result( $check_context );

		$plugin_header_text_domain_check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );
		$this->assertEquals( 1, $check_result->get_warning_count() );

		$this->assertArrayHasKey( 0, $warnings['load.php'] );
		$this->assertArrayHasKey( 0, $warnings['load.php'][0] );
		$this->assertArrayHasKey( 'code', $warnings['load.php'][0][0][0] );
		$this->assertEquals( 'textdomain_mismatch', $warnings['load.php'][0][0][0]['code'] );
	}

	public function test_run_without_errors() {
		$plugin_header_text_domain_check = new Plugin_Header_Text_Domain_Check();
		$check_context                   = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-header-text-domain-without-errors/load.php' );
		$check_result                    = new Check_Result( $check_context );

		$plugin_header_text_domain_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
