<?php
/**
 * Tests for the Credential_Storage_Check class.
 *
 * @package plugin-check
 */

namespace phpunit\tests\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Security\Credential_Storage_Check;
use WP_UnitTestCase;

class Credential_Storage_Check_Test extends WP_UnitTestCase {

	public function test_run_with_warnings() {
		$check         = new Credential_Storage_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-credential-storage-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $check_result->get_errors() );
		$this->assertArrayHasKey( 'load.php', $warnings );
		$this->assertSame( 2, $check_result->get_warning_count() );
		$this->assertArrayHasKey( 18, $warnings['load.php'] );
		$this->assertArrayHasKey( 20, $warnings['load.php'] );

		$column = key( $warnings['load.php'][18] );
		$this->assertSame(
			'PluginCheck.Security.CredentialStorage.CredentialOutsideSecretsAPI',
			$warnings['load.php'][18][ $column ][0]['code']
		);
	}

	public function test_run_without_warnings() {
		$check         = new Credential_Storage_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-credential-storage-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$this->assertEmpty( $check_result->get_errors() );
		$this->assertEmpty( $check_result->get_warnings() );
	}
}
