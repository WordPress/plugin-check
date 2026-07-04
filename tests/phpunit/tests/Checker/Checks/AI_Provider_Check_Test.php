<?php
/**
 * Tests for the AI_Provider_Check class.
 *
 * @package plugin-check
 */

namespace phpunit\tests\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\General\AI_Provider_Check;
use WP_UnitTestCase;

class AI_Provider_Check_Test extends WP_UnitTestCase {

	public function test_run_with_warnings() {
		$check         = new AI_Provider_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-ai-provider-check-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();
		$errors   = $check_result->get_errors();

		$this->assertEmpty( $errors );
		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );

		// Only the two actual provider integrations should be flagged.
		$this->assertSame( 2, $check_result->get_warning_count() );
		$this->assertArrayHasKey( 20, $warnings['load.php'] );
		$this->assertArrayHasKey( 28, $warnings['load.php'] );

		$column = key( $warnings['load.php'][20] );
		$this->assertSame(
			'PluginCheck.CodeAnalysis.AIProvider.DirectIntegration',
			$warnings['load.php'][20][ $column ][0]['code']
		);
	}
}
