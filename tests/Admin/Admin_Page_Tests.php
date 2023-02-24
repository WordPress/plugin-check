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
		$this->assertEquals( 10, has_filter( 'plugin_action_links', array( $this->admin_page, 'filter_plugin_action_links' ) ) );
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

		$parent_pages = $_parent_pages;

		set_current_screen( $current_screen );

		$this->assertArrayHasKey( 'plugin-check', $parent_pages );
		$this->assertEquals( 'tools.php', $parent_pages['plugin-check'] );
	}

	public function test_render_page() {

		ob_start();
		$this->admin_page->render_page();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertStringContainsString( 'Check the Plugin', $output );
		$this->assertStringContainsString( ' id="plugin-check__plugins"', $output );
		$this->assertStringContainsString( ' name="plugin_check_plugins"', $output );
		$this->assertStringContainsString( 'Select Plugin', $output );
		$this->assertStringContainsString( ' type="submit"', $output );
		$this->assertStringContainsString( ' value="Check it!"', $output );
		$this->assertStringNotContainsString( plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE ), $output );
	}

	public function test_filter_plugin_action_links() {

		$current_screen = get_current_screen();

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );

		if ( is_multisite() ) {
			grant_super_admin( $admin_user );
		}

		wp_set_current_user( $admin_user );

		$this->admin_page->add_page();

		$base_file = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );

		$action_links = $this->admin_page->filter_plugin_action_links( array(), $base_file );

		$this->assertEquals(
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url() . 'tools.php?page=plugin-check&plugin=' . $base_file ),
				esc_html__( 'Check this plugin', 'plugin-check' )
			),
			$action_links[0]
		);
	}
}
