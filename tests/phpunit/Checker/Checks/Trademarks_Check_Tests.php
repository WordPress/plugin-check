<?php
/**
 * Tests for the Trademarks_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Trademarks_Check;

class Trademarks_Check_Tests extends WP_UnitTestCase {

	public function test_trademarks_without_errors() {
		$trademarks_check = new Trademarks_Check();
		$check_context    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-trademarks-without-errors/load.php' );
		$check_result     = new Check_Result( $check_context );

		$trademarks_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );

		$this->assertSame( 0, $check_result->get_error_count() );
		$this->assertSame( 0, $check_result->get_warning_count() );
	}

	/**
	 * @dataProvider data_trademarks_check
	 */
	public function test_trademarks_with_different_scenarios( $type_flag, $plugin_basename, $expected_file, $expected_error ) {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . $plugin_basename );
		$check_result  = new Check_Result( $check_context );

		$check = new Trademarks_Check( $type_flag );
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( $expected_file, $errors );
		$this->assertSame( 1, $check_result->get_error_count() );

		$this->assertTrue( isset( $errors[ $expected_file ][0][0][0] ) );
		$this->assertSame( 'trademarked_term', $errors[ $expected_file ][0][0][0]['code'] );
		$this->assertSame( $expected_error, $errors[ $expected_file ][0][0][0]['message'] );
	}

	public function data_trademarks_check() {
		return array(
			'Plugin readme - Test Plugin with readme'     => array(
				Trademarks_Check::TYPE_README,
				'test-trademarks-plugin-readme-errors/load.php',
				'readme.txt',
				'The plugin name includes a restricted term. Your chosen plugin name - <code> Test Plugin with readme </code> - contains the restricted term "plugin" which cannot be used at all in your plugin name.',
			),
			'Plugin header - Test Trademarks Plugin Header Name Errors' => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-name-errors/load.php',
				'load.php',
				'The plugin name includes a restricted term. Your chosen plugin name - <code>Test Trademarks Plugin Header Name Errors</code> - contains the restricted term "plugin" which cannot be used at all in your plugin name.',
			),
			'Plugin slug - test-trademarks-plugin-header-slug-errors' => array(
				Trademarks_Check::TYPE_SLUG,
				'test-trademarks-plugin-header-slug-errors/load.php',
				'load.php',
				'The plugin slug includes a restricted term. Your plugin slug - <code>test-trademarks-plugin-header-slug-errors</code> - contains the restricted term "plugin" which cannot be used at all in your plugin slug.',
			),
			'Plugin headers - WooCommerce Example String' => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-woocommerce-string/load.php',
				'load.php',
				'The plugin name includes a restricted term. Your chosen plugin name - <code>WooCommerce Example String</code> - contains the restricted term "woocommerce" which cannot be used within in your plugin name, unless your plugin name ends with "for woocommerce". The term must still not appear anywhere else in your name.',
			),
			'Plugin headers - WooCommerce String for WooCommerce' => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-woocommerce-string-for-woocommerce/load.php',
				'load.php',
				'The plugin name includes a restricted term. Your chosen plugin name - <code>WooCommerce String for WooCommerce</code> - contains the restricted term "woocommerce" which cannot be used within in your plugin name, unless your plugin name ends with "for woocommerce". The term must still not appear anywhere else in your name.',
			),
			'Plugin headers - WordPress String for WooCommerce' => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-wordpress-string-for-woocommerce/load.php',
				'load.php',
				'The plugin name includes a restricted term. Your chosen plugin name - <code>WordPress String for WooCommerce</code> - contains the restricted term "wordpress" which cannot be used at all in your plugin name.',
			),
			'Plugin headers portmanteaus'                 => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-portmanteaus/load.php',
				'load.php',
				'The plugin name includes a restricted term. Your chosen plugin name - <code>WooXample</code> - contains the restricted term "woo" which cannot be used at all in your plugin name.',
			),
		);
	}

	public function test_trademarks_with_for_woocommerce_exceptions() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-trademarks-plugin-header-example-string-for-woocommerce/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Trademarks_Check( Trademarks_Check::TYPE_NAME );
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertEmpty( $errors );
		$this->assertSame( 0, $check_result->get_error_count() );
	}
}
