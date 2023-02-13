<?php
/**
 * Tests for the Plugin_Request_Utility class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

class Plugin_Request_Utility_Tests extends WP_UnitTestCase {

	public function test_get_plugin_basename_from_input() {
		$plugin = Plugin_Request_Utility::get_plugin_basename_from_input( 'plugin-check' );

		$this->assertSame( plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE ), $plugin );
	}

	public function test_get_plugin_basename_from_input_with_empty_input() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Missing positional argument. Please provide the plugin slug as first positional argument.' );

		Plugin_Request_Utility::get_plugin_basename_from_input( '' );
	}

	public function test_get_plugin_basename_from_input_with_invalid_input() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Invalid positional argument. Plugin with slug invalid is not installed.' );

		Plugin_Request_Utility::get_plugin_basename_from_input( 'invalid' );
	}
}
