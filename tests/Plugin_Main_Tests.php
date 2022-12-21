<?php
/**
 * Tests for the Plugin_Main class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Plugin_Main;
use WordPress\Plugin_Check\Plugin_Context;

class Plugin_Main_Tests extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();

		$this->plugin_main = new Plugin_Main( 'plugin-check/plugin-check.php' );
	}

	public function test_context() {
		$context = $this->plugin_main->context();

		$this->assertIsObject( $context );
		$this->assertInstanceOf( Plugin_Context::class, $context );
	}
}
