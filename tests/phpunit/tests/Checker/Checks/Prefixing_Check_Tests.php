<?php
/**
 * Tests for the Prefixing_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Check_Types;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Prefixing_Check;

class Prefixing_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Prefixing_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-prefixing-errors/load.php' );
		$check_result  = new Check_Result( $check_context, Check_Types::get_type_slugs() );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );

		$this->assertCount( 1, wp_list_filter( $errors['load.php'][18][9], array( 'code' => 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][20][1], array( 'code' => 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][28][1], array( 'code' => 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound' ) ) );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][41][1], array( 'code' => 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound' ) ) );
	}
}
