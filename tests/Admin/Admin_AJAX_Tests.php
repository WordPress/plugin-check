<?php
/**
 * Tests for the Admin_AJAX class.
 *
 * @package plugin-check
 */

namespace Admin;

use WordPress\Plugin_Check\Admin\Admin_AJAX;
use WP_UnitTestCase;

class Admin_AJAX_Tests extends WP_UnitTestCase {

	protected $admin_ajax;

	public function set_up() {
		parent::set_up();
		$this->admin_ajax = new Admin_AJAX();
	}

	public function test_add_hooks() {
		$this->admin_ajax->add_hooks();
		$this->assertEquals( 10, has_action( 'wp_ajax_plugin_check_run_checks', array( $this->admin_ajax, 'run_checks' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_plugin_check_get_checks_to_run', array( $this->admin_ajax, 'get_checks_to_run' ) ) );
	}

	public function test_get_nonce() {
		$this->assertNotFalse(
			wp_verify_nonce( $this->admin_ajax->get_nonce(), Admin_AJAX::NONCE_KEY )
		);
	}
}
