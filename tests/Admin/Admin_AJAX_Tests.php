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

//	public function test_render_page() {
//		global $wp_object_cache;
//
//		// Backup original plugins in the object cache.
//		$original_plugins = $wp_object_cache->get( 'plugins', 'plugins' );
//
//		// Create the basic information required get_available_plugins.
//		$expected_plugins = array(
//			'hello.php'            => array(
//				'Name' => 'Hello Dolly',
//			),
//			'akismet/akismet.php'  => array(
//				'Name' => 'Akistmet',
//			),
//			'Fake-plugin/load.php' => array(
//				'Name' => 'Fake Plugin',
//			),
//		);
//
//		// Include the Plugin Checker plugin.
//		$plugin_basename                      = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );
//		$expected_plugins[ $plugin_basename ] = array( 'Name' => 'Plugin Checker' );
//
//		// Set the expected plugins in the cache.
//		$wp_object_cache->set( 'plugins', array( '' => $expected_plugins ), 'plugins' );
//
//		// Render the admin page.
//		ob_start();
//		$this->admin_page->render_page();
//		$output = ob_get_contents();
//		ob_end_clean();
//
//		// Restore the original cache.
//		$wp_object_cache->set( 'plugins', $original_plugins, 'plugins' );
//
//		// Remove the plugin checker from exptected plugins for testing.
//		unset( $expected_plugins[ $plugin_basename ] );
//
//		// Assert the Plugin Checker does not appear in the select dropdown.
//		$this->assertStringNotContainsString( $plugin_basename, $output );
//
//		// Assert the expected plugins appear in the select dropdown.
//		foreach ( $expected_plugins as $plugin => $data ) {
//			$this->assertStringContainsString( '<option value="' . $plugin . '">', $output );
//			$this->assertStringContainsString( $data['Name'], $output );
//		}
//	}
//
//	public function test_render_page_with_no_plugins() {
//		global $wp_object_cache;
//
//		// Backup original plugins in the object cache.
//		$original_plugins = $wp_object_cache->get( 'plugins', 'plugins' );
//
//		// Set the expected plugins to be empty in the cache.
//		$wp_object_cache->add( 'plugins', array( '' => array() ), 'plugins' );
//
//		// Render the admin page.
//		ob_start();
//		$this->admin_page->render_page();
//		$output = ob_get_contents();
//		ob_end_clean();
//
//		// Restore the original cache.
//		$wp_object_cache->set( 'plugins', $original_plugins, 'plugins' );
//
//		$this->assertStringContainsString( 'No plugins available.', $output );
//		$this->assertStringNotContainsString( '<select id="plugin-check__plugins"', $output );
//	}
}
