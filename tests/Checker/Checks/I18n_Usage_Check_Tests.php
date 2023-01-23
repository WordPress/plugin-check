<?php
/**
 * Tests for the I18n_Usage_Check_Tests class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Checks\I18n_Usage_Check;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;

class I18n_Usage_Check_Tests extends WP_UnitTestCase {

	protected $i18n_usage_check;

	public function set_up() {
		parent::set_up();

		$this->i18n_usage_check = new I18n_Usage_Check();
	}

	/**
	 * @covers I18n_Usage_Check::get_args()
	 */
	public function test_get_args() {

		$sniffs = $this->i18n_usage_check->get_args();

		$this->assertEquals(
			array(
				'extensions' => 'php',
				'standard'   => 'WordPress,WordPress-Core,WordPress-Docs,WordPress-Extra',
				'sniffs'     => 'WordPress.WP.I18n',
			),
			$sniffs
		);
	}

	/**
	 * @covers I18n_Usage_Check::run()
	 */
	public function test_run_with_errors() {

		$check_context = new Check_Context( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'tests/test-plugin-with-errors/test-plugin-with-errors.php' );

		$check_result = new Check_Result( $check_context );

		$this->i18n_usage_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );

		$this->assertArrayHasKey( 'i18-usage-error-file.php', $errors );

		$this->assertEquals( 2, $check_result->get_error_count() );

		// Check for WordPress.WP.I18n.MissingTranslatorsComment error on Line no 9 and column no at 5.
		$this->assertArrayHasKey( 9, $errors['i18-usage-error-file.php'] );
		$this->assertArrayHasKey( 5, $errors['i18-usage-error-file.php'][9] );
		$this->assertArrayHasKey( 'code', $errors['i18-usage-error-file.php'][9][5][0] );
		$this->assertEquals( 'WordPress.WP.I18n.MissingTranslatorsComment', $errors['i18-usage-error-file.php'][9][5][0]['code'] );

		// Check for WordPress.WP.I18n.NonSingularStringLiteralDomain error on Line no 15 and column no at 29.
		$this->assertArrayHasKey( 15, $errors['i18-usage-error-file.php'] );
		$this->assertArrayHasKey( 29, $errors['i18-usage-error-file.php'][15] );
		$this->assertArrayHasKey( 'code', $errors['i18-usage-error-file.php'][15][29][0] );
		$this->assertEquals( 'WordPress.WP.I18n.NonSingularStringLiteralDomain', $errors['i18-usage-error-file.php'][15][29][0]['code'] );
	}

	/**
	 * @covers I18n_Usage_Check::run()
	 */
	public function test_run_without_errors() {

		$check_context = new Check_Context( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'tests/test-plugin-without-errors/test-plugin-without-errors.php' );

		$check_result = new Check_Result( $check_context );

		$this->i18n_usage_check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertEmpty( $errors );
		$this->assertEquals( 0, $check_result->get_error_count() );

		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}

}
