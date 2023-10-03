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

		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}

	/**
	 * @dataProvider data_trademarks_check
	 */
	public function test_trademarks_with_different_scenarios( $type_flag, $plugin_basename, $expected_file, $expected_code ) {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . $plugin_basename );
		$check_result  = new Check_Result( $check_context );

		$check = new Trademarks_Check( $type_flag );
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		if ( $expected_code ) {
			$this->assertNotEmpty( $errors );
			$this->assertArrayHasKey( $expected_file, $errors );
			$this->assertSame( 1, $check_result->get_error_count() );

			$this->assertTrue( isset( $errors[ $expected_file ][0][0][0] ) );
			$this->assertSame( 'trademarked_term', $errors[ $expected_file ][0][0][0]['code'] );
			$this->assertStringContainsString( $expected_code, $errors[ $expected_file ][0][0][0]['message'] );
		} else {
			$this->assertEmpty( $errors );
			$this->assertEquals( 0, $check_result->get_error_count() );
		}
	}

	public function data_trademarks_check() {
		return array(
			'Plugin readme - Test Plugin with readme'     => array(
				Trademarks_Check::TYPE_README,
				'test-trademarks-plugin-readme-errors/load.php',
				'readme.txt',
				'"plugin"',
			),
			'Plugin header - Test Trademarks Plugin Header Name Errors' => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-name-errors/load.php',
				'load.php',
				'"plugin"',
			),
			'Plugin slug - test-trademarks-plugin-header-slug-errors' => array(
				Trademarks_Check::TYPE_SLUG,
				'test-trademarks-plugin-header-slug-errors/load.php',
				'load.php',
				'"plugin"',
			),
			'Plugin headers - WooCommerce Example String' => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-woocommerce-string/load.php',
				'load.php',
				'"woocommerce"',
			),
			'Plugin headers - Example String for WooCommerce' => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-example-string-for-woocommerce/load.php',
				'load.php',
				'',
			),
			'Plugin headers - WooCommerce String for WooCommerce' => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-woocommerce-string-for-woocommerce/load.php',
				'load.php',
				'"woocommerce"',
			),
			'Plugin headers - WordPress String for WooCommerce' => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-wordpress-string-for-woocommerce/load.php',
				'load.php',
				'"wordpress"',
			),
			'Plugin headers portmanteaus'                 => array(
				Trademarks_Check::TYPE_NAME,
				'test-trademarks-plugin-header-portmanteaus/load.php',
				'load.php',
				'"woo"',
			),
		);
	}
}
