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

		$this->plugin_name    = basename( TESTS_PLUGIN_DIR );
		$this->plugin_context = new Plugin_Context( WP_PLUGIN_CHECK_MAIN_FILE );
	}

	public function test_basename() {
		$this->assertSame( plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE ), $this->plugin_context->basename() );
	}

	public function test_path() {
		$this->assertSame( WP_PLUGIN_DIR . '/' . $this->plugin_name . '/', $this->plugin_context->path() );
	}

	public function test_path_with_parameter() {
		$this->assertSame( WP_PLUGIN_DIR . '/' . $this->plugin_name . '/another/folder', $this->plugin_context->path( '/another/folder' ) );
	}

	public function test_url() {
		$this->assertSame( WP_PLUGIN_URL . '/' . $this->plugin_name . '/', $this->plugin_context->url() );
	}

	public function test_url_with_parameter() {
		$this->assertSame( WP_PLUGIN_URL . '/' . $this->plugin_name . '/folder/file.css', $this->plugin_context->url( '/folder/file.css' ) );
	}

	public function test_location() {
		$this->assertSame( WP_PLUGIN_DIR . '/' . $this->plugin_name . '/', $this->plugin_context->location() );
	}

	public function test_location_with_single_file_plugin() {
		$single_file = WP_PLUGIN_DIR . '/single-file-plugin.php';
		$context     = new Plugin_Context( $single_file );

		$this->assertSame( $single_file, $context->location() );
	}

	public function test_is_single_file_plugin() {
		$single_file = WP_PLUGIN_DIR . '/single-file-plugin.php';
		$context     = new Plugin_Context( $single_file );

		$this->assertTrue( $context->is_single_file_plugin() );
	}
}
