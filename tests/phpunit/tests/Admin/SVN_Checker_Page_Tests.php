<?php
/**
 * Tests for the SVN_Checker_Page class.
 *
 * @package plugin-check
 */

namespace Admin;

use WordPress\Plugin_Check\Admin\SVN_Checker_Page;
use WP_UnitTestCase;

class SVN_Checker_Page_Tests extends WP_UnitTestCase {

	protected $svn_checker_page;

	public function set_up() {
		parent::set_up();
		$this->svn_checker_page = new SVN_Checker_Page();
	}

	public function test_add_hooks() {
		$this->svn_checker_page->add_hooks();
		$this->assertEquals( 10, has_action( 'admin_menu', array( $this->svn_checker_page, 'add_page' ) ) );
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', array( $this->svn_checker_page, 'enqueue_scripts' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_' . SVN_Checker_Page::ACTION_CHECK, array( $this->svn_checker_page, 'handle_check' ) ) );
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

		$this->svn_checker_page->add_page();
		$parent_pages = $_parent_pages;

		set_current_screen( $current_screen );

		$this->assertArrayHasKey( SVN_Checker_Page::MENU_SLUG, $parent_pages );
		$this->assertEquals( 'tools.php', $parent_pages[ SVN_Checker_Page::MENU_SLUG ] );
	}

	public function test_render_page() {
		ob_start();
		$this->svn_checker_page->render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'plugin-check-svn-checker-form', $output );
		$this->assertStringContainsString( 'name="plugin_slug"', $output );
	}

	public function test_render_page_with_slug_from_query_arg() {
		$_GET['plugin_slug'] = 'hello-dolly';

		ob_start();
		$this->svn_checker_page->render_page();
		$output = ob_get_clean();

		unset( $_GET['plugin_slug'] );

		$this->assertStringContainsString( 'value="hello-dolly"', $output );
	}

	public function test_enqueue_scripts_does_nothing_without_matching_hook() {
		$this->svn_checker_page->enqueue_scripts( 'some-other-page' );

		$this->assertFalse( wp_style_is( 'plugin-check-svn-checker', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'plugin-check-svn-checker', 'enqueued' ) );
	}
}
