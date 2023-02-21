<?php
/**
 * Tests for the Admin_Page class.
 *
 * @package plugin-check
 */

namespace Admin;

use WordPress\Plugin_Check\Admin\Admin_Page;
use WP_UnitTestCase;

class Admin_Page_Tests extends WP_UnitTestCase {

	protected $admin_page;

	public function set_up() {
		parent::set_up();
		$this->admin_page = new Admin_Page();
	}

	public function test_add_hooks() {
		$this->admin_page->add_hooks();
		$this->assertEquals( 10, has_action( 'admin_menu', array( $this->admin_page, 'add_page' ) ) );
	}

	public function test_add_page() {
		global $_parent_pages;

		$current_screen = get_current_screen();

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );

		if ( is_multisite() ) {
			grant_super_admin( $admin_user );
		}

		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );

		$this->admin_page->add_page();

		$this->assertArrayHasKey( 'plugin-check', $_parent_pages );
		$this->assertEquals( 'tools.php', $_parent_pages['plugin-check'] );

		set_current_screen( $current_screen );
	}

	public function test_render_page() {
		$available_plugins      = get_plugins();
		$plugin_check_base_name = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );

		if ( isset( $available_plugins[ $plugin_check_base_name ] ) ) {
			unset( $available_plugins[ $plugin_check_base_name ] );
		}

		ob_start();
		$this->admin_page->render_page();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertStringContainsString( 'Check the Plugin', $output );
		$this->assertStringContainsString( ' id="plugin-check__plugins"', $output );
		$this->assertStringContainsString( ' name="plugin_check_plugins"', $output );
		$this->assertStringContainsString( 'Select Plugin', $output );
		$this->assertStringNotContainsString( ' name="Check it!"', $output );
	}
}
