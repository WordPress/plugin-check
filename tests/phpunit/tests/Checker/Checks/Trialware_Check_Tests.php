<?php

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Trialware_Check;

class Trialware_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_locked_feature_candidates() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-trialware-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Trialware_Check();
		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertGreaterThanOrEqual( 2, $check_result->get_error_count() );
		$this->assertCount( 1, wp_list_filter( $errors['load.php'][17][3], array( 'code' => 'trialware_locked_feature_candidate' ) ) );
		$this->assertSame( 5, $errors['load.php'][17][3][0]['severity'] );
	}

	public function test_run_without_locked_feature_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-trialware-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Trialware_Check();
		$check->run( $check_result );

		$this->assertEmpty( $check_result->get_errors() );
		$this->assertSame( 0, $check_result->get_error_count() );
	}
}
