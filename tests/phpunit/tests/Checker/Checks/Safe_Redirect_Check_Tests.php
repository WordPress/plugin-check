<?php
/**
 * Tests for the Safe_Redirect_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Security\Safe_Redirect_Check;

class Safe_Redirect_Check_Tests extends WP_UnitTestCase {

	public function test_get_categories() {
		$check      = new Safe_Redirect_Check();
		$categories = $check->get_categories();

		$this->assertIsArray( $categories );
		$this->assertContains( Check_Categories::CATEGORY_SECURITY, $categories );
		$this->assertContains( Check_Categories::CATEGORY_PLUGIN_REPO, $categories );
		$this->assertCount( 2, $categories );
	}

	public function test_run_with_errors() {
		$check         = new Safe_Redirect_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-safe-redirect/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertEquals( 1, $check_result->get_warning_count() );

		// Check for WordPress.Security.SafeRedirect warning.
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][12][1], array( 'code' => 'WordPress.Security.SafeRedirect.wp_redirect_wp_redirect' ) ) );
	}
}
