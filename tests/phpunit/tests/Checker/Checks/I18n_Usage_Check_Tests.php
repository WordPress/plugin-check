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

		// Restricted textdomain with severity 7.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][35][29], array( 'code' => 'WordPress.WP.I18n.TextDomainMismatch' ) ) );
		$this->assertSame( 7, $errors['load.php'][35][29][0]['severity'] );

		// Mismatched textdomain but not restricted and with severity 5.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][36][29], array( 'code' => 'WordPress.WP.I18n.TextDomainMismatch' ) ) );
		$this->assertSame( 5, $errors['load.php'][36][29][0]['severity'] );

		// Non singular string literal errors.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][40][10], array( 'code' => 'WordPress.WP.I18n.NonSingularStringLiteralSingle' ) ) );
		$this->assertSame( 7, $errors['load.php'][40][10][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][40][19], array( 'code' => 'WordPress.WP.I18n.NonSingularStringLiteralPlural' ) ) );
		$this->assertSame( 7, $errors['load.php'][40][19][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][41][15], array( 'code' => 'WordPress.WP.I18n.NonSingularStringLiteralSingular' ) ) );
		$this->assertSame( 7, $errors['load.php'][41][15][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][41][24], array( 'code' => 'WordPress.WP.I18n.NonSingularStringLiteralPlural' ) ) );
		$this->assertSame( 7, $errors['load.php'][41][24][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][42][10], array( 'code' => 'WordPress.WP.I18n.NonSingularStringLiteralText' ) ) );
		$this->assertSame( 7, $errors['load.php'][42][10][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][42][17], array( 'code' => 'WordPress.WP.I18n.NonSingularStringLiteralContext' ) ) );
		$this->assertSame( 7, $errors['load.php'][42][17][0]['severity'] );

		// Interpolated variable errors.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][45][18], array( 'code' => 'WordPress.WP.I18n.InterpolatedVariableText' ) ) );
		$this->assertSame( 7, $errors['load.php'][45][18][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][46][10], array( 'code' => 'WordPress.WP.I18n.InterpolatedVariableSingle' ) ) );
		$this->assertSame( 7, $errors['load.php'][46][10][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][46][23], array( 'code' => 'WordPress.WP.I18n.InterpolatedVariablePlural' ) ) );
		$this->assertSame( 7, $errors['load.php'][46][23][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][47][15], array( 'code' => 'WordPress.WP.I18n.InterpolatedVariableSingular' ) ) );
		$this->assertSame( 7, $errors['load.php'][47][15][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][47][28], array( 'code' => 'WordPress.WP.I18n.InterpolatedVariablePlural' ) ) );
		$this->assertSame( 7, $errors['load.php'][47][28][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][48][10], array( 'code' => 'WordPress.WP.I18n.InterpolatedVariableText' ) ) );
		$this->assertSame( 7, $errors['load.php'][48][10][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][48][21], array( 'code' => 'WordPress.WP.I18n.InterpolatedVariableContext' ) ) );
		$this->assertSame( 7, $errors['load.php'][48][21][0]['severity'] );

		// Restricted characters.
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][51][29], array( 'code' => 'WordPress.WP.I18n.TextDomainMismatch' ) ) );
		$this->assertSame( 7, $errors['load.php'][51][29][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][52][29], array( 'code' => 'WordPress.WP.I18n.TextDomainMismatch' ) ) );
		$this->assertSame( 7, $errors['load.php'][52][29][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][53][29], array( 'code' => 'WordPress.WP.I18n.TextDomainMismatch' ) ) );
		$this->assertSame( 7, $errors['load.php'][53][29][0]['severity'] );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][54][29], array( 'code' => 'WordPress.WP.I18n.TextDomainMismatch' ) ) );
		$this->assertSame( 7, $errors['load.php'][54][29][0]['severity'] );
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
