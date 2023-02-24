<?php
/**
 * Tests for the Admin_Page class.
 *
 * @package plugin-check
 */

namespace Admin;

use WordPress\Plugin_Check\Admin\Admin_Page;
use WP_Object_Cache;
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

		$parent_pages = $_parent_pages;

		set_current_screen( $current_screen );

		$this->assertArrayHasKey( 'plugin-check', $parent_pages );
		$this->assertEquals( 'tools.php', $parent_pages['plugin-check'] );
	}

	public function test_render_page() {
		global $wp_object_cache;

		// Backup original plugins in the object cache.
		$original_plugins = $wp_object_cache->get( 'plugins', 'plugins' );

		// Create the basic information required get_available_plugins.
		$expected_plugins = array(
			'hello.php'            => array(
				'Name' => 'Hello Dolly',
			),
			'akismet/akismet.php'  => array(
				'Name' => 'Akistmet',
			),
			'Fake-plugin/load.php' => array(
				'Name' => 'Fake Plugin',
			),
		);

		// Include the Plugin Checker plugin.
		$plugin_basename                      = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );
		$expected_plugins[ $plugin_basename ] = array( 'Name' => 'Plugin Checker' );

		// Set the expected plugins in the cache.
		$wp_object_cache->set( 'plugins', array( '' => $expected_plugins ), 'plugins' );

		// Render the admin page.
		ob_start();
		$this->admin_page->render_page();
		$output = ob_get_contents();
		ob_end_clean();

		// Restore the original cache.
		$wp_object_cache->set( 'plugins', $original_plugins, 'plugins' );

		// Remove the plugin checker from exptected plugins for testing.
		unset( $expected_plugins[ $plugin_basename ] );

		// Assert the Plugin Checker does not appear in the select dropdown.
		$this->assertStringNotContainsString( $plugin_basename, $output );

		// Assert the expected plugins appear in the select dropdown.
		foreach ( $expected_plugins as $plugin => $data ) {
			$this->assertStringContainsString( '<option value="' . $plugin . '">', $output );
			$this->assertStringContainsString( $data['Name'], $output );
		}
	}

	public function test_render_page_with_no_plugins() {
		global $wp_object_cache;

		// Backup original plugins in the object cache.
		$original_plugins = $wp_object_cache->get( 'plugins', 'plugins' );

		// Set the expected plugins to be empty in the cache.
		$wp_object_cache->add( 'plugins', array( '' => array() ), 'plugins' );

		// Render the admin page.
		ob_start();
		$this->admin_page->render_page();
		$output = ob_get_contents();
		ob_end_clean();

		// Restore the original cache.
		$wp_object_cache->set( 'plugins', $original_plugins, 'plugins' );

		$this->assertStringContainsString( 'No plugins available.', $output );
		$this->assertStringNotContainsString( '<select id="plugin-check__plugins"', $output );
	}
}
