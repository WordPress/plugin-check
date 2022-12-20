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

		$this->plugin_context = new Plugin_Context( 'plugin-check/plugin-check.php' );
	}

	public function test_basename() {
		$basename = $this->plugin_context->basename();

		$this->assertSame( $basename, 'plugin-check/plugin-check.php' );
	}

	public function test_path() {
		$path = $this->plugin_context->path();

		$this->assertSame( $path, 'plugin-check/' );
	}

	public function test_path_with_parameter() {
		$path = $this->plugin_context->path( '/another/folder' );

		$this->assertSame( $path, 'plugin-check/another/folder' );
	}

	public function test_url() {
		$url = $this->plugin_context->path();

		$this->assertSame( $url, 'plugin-check/' );
	}

	public function test_url_with_parameter() {
		$url = $this->plugin_context->path( '/folder/file.css' );

		$this->assertSame( $url, 'plugin-check/folder/file.css' );
	}
}
