<?php
/**
 * Tests for the Admin_AJAX class.
 *
 * @package plugin-check
 */

namespace Admin;

use WordPress\Plugin_Check\Admin\Admin_AJAX;
use WordPress\Plugin_Check\Admin\Admin_Page;
use WP_UnitTestCase;

class Admin_AJAX_Tests extends WP_UnitTestCase {

	protected $admin_ajax;

	public function set_up() {
		parent::set_up();
		$this->admin_ajax = new Admin_AJAX();
	}

	public function test_add_hooks() {
		$this->admin_ajax->add_hooks();
		$this->assertEquals( 10, has_action( 'wp_ajax_plugin_check_run_check', array( $this->admin_ajax, 'run_check' ) ) );
	}

	public function test_get_nonce() {
		$this->assertNotEmpty( $this->admin_ajax->get_nonce() );
	}
}
