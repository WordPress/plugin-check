<?php
/**
 * Tests for the Direct_DB_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Security\Direct_DB_Check;

class Direct_DB_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Direct_DB_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-direct-db/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$errors = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertEquals( 5, $check_result->get_error_count() );

		$this->assertEquals( 'PluginCheck.Security.DirectDB.UnescapedDBParameter', $errors['load.php'][22][9][0]['code'] );
		$this->assertEquals( 'PluginCheck.Security.DirectDB.UnescapedDBParameter', $errors['load.php'][29][9][0]['code'] );
		$this->assertEquals( 'PluginCheck.Security.DirectDB.UnescapedDBParameter', $errors['load.php'][36][9][0]['code'] );
		$this->assertEquals( 'PluginCheck.Security.DirectDB.UnescapedDBParameter', $errors['load.php'][43][9][0]['code'] );
		$this->assertEquals( 'PluginCheck.Security.DirectDB.UnescapedDBParameter', $errors['load.php'][50][9][0]['code'] );
	}
}
