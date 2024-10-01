<?php
/**
 * Tests for the Offloading_Files_Check class.
 *
 * @package plugin-check
 */

namespace phpunit\tests\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Offloading_Files_Check;
use WP_UnitTestCase;

class Offloading_Files_Check_Test extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$enqueued_scripts_in_footer_check = new Offloading_Files_Check();
		$check_context                    = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-offloaded-files-check-with-errors/load.php' );
		$check_result                     = new Check_Result( $check_context );

		$enqueued_scripts_in_footer_check->run( $check_result );

		$warnings = $check_result->get_warnings();
		$errors   = $check_result->get_errors();

		$this->assertEmpty( $warnings );
		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertSame( 5, $check_result->get_error_count() );

		$this->assertArrayHasKey( 8, $errors['load.php'] );
		$this->assertSame( 'PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent', $errors['load.php'][8][2][0]['code'] );
		$this->assertArrayHasKey( 16, $errors['load.php'] );
		$this->assertSame( 'PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent', $errors['load.php'][16][2][0]['code'] );
		$this->assertArrayHasKey( 23, $errors['load.php'] );
		$this->assertSame( 'PluginCheck.CodeAnalysis.Offloading.OffloadedContent', $errors['load.php'][23][1][0]['code'] );
		$this->assertArrayHasKey( 25, $errors['load.php'] );
		$this->assertSame( 'PluginCheck.CodeAnalysis.Offloading.OffloadedContent', $errors['load.php'][25][1][0]['code'] );
		$this->assertArrayHasKey( 27, $errors['load.php'] );
		$this->assertSame( 'PluginCheck.CodeAnalysis.Offloading.OffloadedContent', $errors['load.php'][27][1][0]['code'] );
	}
}
