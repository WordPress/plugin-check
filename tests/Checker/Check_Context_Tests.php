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
		$this->assertSame( 'test-plugin/test-plugin.php', $this->check_context->basename() );
	}

	public function test_path() {
		$this->assertSame( 'test-plugin/', $this->check_context->path() );
	}

	public function test_path_with_parameter() {
		$this->assertSame( 'test-plugin/another/folder', $this->check_context->path( '/another/folder' ) );
	}

	public function test_url() {
		$this->assertSame( site_url( '/wp-content/plugins/test-plugin/' ), $this->check_context->url() );
	}

	public function test_url_with_parameter() {
		$this->assertSame( site_url( '/wp-content/plugins/test-plugin/folder/file.css' ), $this->check_context->url( '/folder/file.css' ) );
	}
}
