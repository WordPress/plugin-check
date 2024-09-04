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

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );

		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'plugin_header_invalid_plugin_uri_domain' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'plugin_header_invalid_plugin_description' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'plugin_header_invalid_author_uri' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'plugin_header_invalid_requires_plugins' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'plugin_header_invalid_requires_wp' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'plugin_header_invalid_requires_php' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'textdomain_mismatch' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][0][0], array( 'code' => 'plugin_header_nonexistent_domain_path' ) ) );
	}
}
