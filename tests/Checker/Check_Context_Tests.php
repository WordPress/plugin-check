<?php
/**
 * Tests for the Check_Context class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;

class Check_Context_Tests extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();

		$this->check_context = new Check_Context( 'test-plugin/test-plugin.php' );
	}

	public function test_basename() {
		$basename = $this->check_context->basename();

		$this->assertSame( $basename, 'test-plugin/test-plugin.php' );
	}

	public function test_path() {
		$path = $this->check_context->path();

		$this->assertSame( $path, 'test-plugin/' );
	}

	public function test_path_with_parameter() {
		$path = $this->check_context->path( '/another/folder' );

		$this->assertSame( $path, 'test-plugin/another/folder' );
	}

	public function test_url() {
		$url = $this->check_context->path();

		$this->assertSame( $url, 'test-plugin/' );
	}

	public function test_url_with_parameter() {
		$url = $this->check_context->path( '/folder/file.css' );

		$this->assertSame( $url, 'test-plugin/folder/file.css' );
	}
}
