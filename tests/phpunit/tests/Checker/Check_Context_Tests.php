<?php
/**
 * Tests for the Check_Context class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;

class Check_Context_Tests extends WP_UnitTestCase {
	/**
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * @var Check_Context
	 */
	protected $check_context;

	public function set_up() {
		parent::set_up();

		$this->plugin_name   = basename( TESTS_PLUGIN_DIR );
		$this->check_context = new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE );
	}

	public function test_basename() {
		$this->assertSame( plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE ), $this->check_context->basename() );
	}

	public function test_path() {
		$this->assertSame( WP_PLUGIN_DIR . '/' . $this->plugin_name . '/', $this->check_context->path() );
	}

	public function test_path_with_parameter() {
		$this->assertSame( WP_PLUGIN_DIR . '/' . $this->plugin_name . '/another/folder', $this->check_context->path( '/another/folder' ) );
	}

	public function test_url() {
		$this->assertSame( WP_PLUGIN_URL . '/' . $this->plugin_name . '/', $this->check_context->url() );
	}

	public function test_url_with_parameter() {
		$this->assertSame( WP_PLUGIN_URL . '/' . $this->plugin_name . '/folder/file.css', $this->check_context->url( '/folder/file.css' ) );
	}
}
