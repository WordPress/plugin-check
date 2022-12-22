<?php
/**
 * Tests for the Plugin_Context class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Plugin_Context;

class Plugin_Context_Tests extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();

		$this->plugin_name    = basename( dirname( __DIR__ ) );
		$this->plugin_context = new Plugin_Context( $this->plugin_name . '/plugin-check.php' );
	}

	public function test_basename() {
		$this->assertSame( $this->plugin_name . '/plugin-check.php', $this->plugin_context->basename() );
	}

	public function test_path() {
		$this->assertSame( $this->plugin_name . '/', $this->plugin_context->path() );
	}

	public function test_path_with_parameter() {
		$this->assertSame( $this->plugin_name . '/another/folder', $this->plugin_context->path( '/another/folder' ) );
	}

	public function test_url() {
		$this->assertSame( WP_PLUGIN_URL . '/' . $this->plugin_name . '/', $this->plugin_context->url() );
	}

	public function test_url_with_parameter() {
		$this->assertSame( WP_PLUGIN_URL . '/' . $this->plugin_name . '/folder/file.css', $this->plugin_context->url( '/folder/file.css' ) );
	}
}
