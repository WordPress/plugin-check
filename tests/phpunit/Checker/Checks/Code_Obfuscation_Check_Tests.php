<?php
/**
 * Tests for the Code_Obfuscation_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Code_Obfuscation_Check;

class Code_Obfuscation_Check_Tests extends WP_UnitTestCase {

	/**
	 * @dataProvider data_obfuscation_services
	 */
	public function test_run_with_obfuscation_errors( $type_flag, $plugin_basename, $expected_file ) {
		// Test given plugin with relevant obfuscation.
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/' . $plugin_basename );
		$check_result  = new Check_Result( $check_context );

		$check = new Code_Obfuscation_Check( $type_flag );
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( $expected_file, $errors );
		$this->assertSame( 1, $check_result->get_error_count() );

		$this->assertTrue( isset( $errors[ $expected_file ][0][0][0] ) );
		$this->assertSame( 'obfuscated_code_detected', $errors[ $expected_file ][0][0][0]['code'] );
	}

	public function data_obfuscation_services() {
		return array(
			'Zend Guard'      => array(
				Code_Obfuscation_Check::TYPE_ZEND,
				'test-plugin-code-obfuscation-zendguard-errors/load.php',
				'obfuscated.php',
			),
			'Source Guardian' => array(
				Code_Obfuscation_Check::TYPE_SOURCEGUARDIAN,
				'test-plugin-code-obfuscation-sourceguardian-errors/load.php',
				'obfuscated.php',
			),
			'ionCube'         => array(
				Code_Obfuscation_Check::TYPE_IONCUBE,
				'test-plugin-code-obfuscation-ioncube-errors/load.php',
				'load.php',
			),
		);
	}

	public function test_run_without_any_obfuscation_errors() {
		// Test plugin without any obfuscation.
		$check_context = new Check_Context( TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/test-plugin-i18n-usage-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Code_Obfuscation_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertEmpty( $errors );
		$this->assertSame( 0, $check_result->get_error_count() );
	}
}
