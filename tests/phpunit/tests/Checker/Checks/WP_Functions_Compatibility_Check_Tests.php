<?php
/**
 * Tests for the WP_Functions_Compatibility_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\WP_Functions_Compatibility_Check;

class WP_Functions_Compatibility_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-wp-functions-compatibility-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new WP_Functions_Compatibility_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertArrayHasKey( 'uses-new-function.php', $errors );
		$this->assertCount( 1, wp_list_filter( $errors['uses-new-function.php'][8][0], array( 'code' => 'wp_function_not_compatible_with_requires_wp' ) ) );
	}

	public function test_run_without_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-wp-functions-compatibility-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new WP_Functions_Compatibility_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();
		$this->assertEmpty( wp_list_filter( $errors['uses-compatible-function.php'][8][0] ?? array(), array( 'code' => 'wp_function_not_compatible_with_requires_wp' ) ) );
	}

	public function test_run_with_function_exists_guard_without_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-wp-functions-compatibility-with-function-exists-guard/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new WP_Functions_Compatibility_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();
		$this->assertArrayNotHasKey( 'uses-guarded-function.php', $errors );
	}

	public function test_run_with_polyfill_file_with_warnings() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-wp-functions-compatibility-with-polyfill-file/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new WP_Functions_Compatibility_Check();
		$check->run( $check_result );

		$this->assertEmpty( $check_result->get_errors() );

		$warnings = $check_result->get_warnings();
		$expected = array(
			8  => 'includes/compat.php',
			9  => 'includes/compat.php',
			10 => 'includes/braced-namespaces.php',
			11 => 'includes/commented-declaration.php',
			12 => 'includes/class-compat-loader.php',
		);
		foreach ( $expected as $line => $declaring_file ) {
			$messages = wp_list_filter( $warnings['uses-polyfilled-function.php'][ $line ][0], array( 'code' => 'wp_function_declared_by_plugin' ) );
			$this->assertCount( 1, $messages );
			$this->assertStringContainsString( " in {$declaring_file},", reset( $messages )['message'] );
		}
	}

	public function test_run_with_non_global_declarations_with_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-wp-functions-compatibility-with-non-global-declarations/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new WP_Functions_Compatibility_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertArrayHasKey( 'uses-new-function.php', $errors );
		$this->assertCount( 1, wp_list_filter( $errors['uses-new-function.php'][8][0], array( 'code' => 'wp_function_not_compatible_with_requires_wp' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['uses-new-function.php'][9][0], array( 'code' => 'wp_function_not_compatible_with_requires_wp' ) ) );
	}

	public function test_run_with_php_serialize_without_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-wp-functions-compatibility-with-php-serialize/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new WP_Functions_Compatibility_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();
		$this->assertEmpty( wp_list_filter( $errors['uses-php-serialize.php'][8][0] ?? array(), array( 'code' => 'wp_function_not_compatible_with_requires_wp' ) ) );
	}
}
