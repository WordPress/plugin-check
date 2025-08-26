<?php
/**
 * Tests for the I18n_Text_Domain_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\I18n_Text_Domain_Check;

class I18n_Text_Domain_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new I18n_Text_Domain_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-i18n-text-domain-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );

		$this->assertCount( 1, wp_list_filter( $errors['load.php'][24][1], array( 'code' => 'PluginCheck.CodeAnalysis.I18nTextDomain.MissingDomainRequired' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][25][1], array( 'code' => 'PluginCheck.CodeAnalysis.I18nTextDomain.MissingDomainRequired' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][26][1], array( 'code' => 'PluginCheck.CodeAnalysis.I18nTextDomain.MissingDomainRequired' ) ) );

		// Check severity level.
		$error = wp_list_filter( $errors['load.php'][24][1], array( 'code' => 'PluginCheck.CodeAnalysis.I18nTextDomain.MissingDomainRequired' ) );
		$this->assertSame( 7, $error[0]['severity'] );
	}
}
