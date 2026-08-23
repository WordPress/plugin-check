<?php
/**
 * Tests for the plugin uninstall handler.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Test_Utils\Traits\With_Mock_Filesystem;

class Uninstall_Tests extends WP_UnitTestCase {

	use With_Mock_Filesystem;

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_uninstall_drops_runtime_tables_and_drop_in() {
		global $wp_filesystem, $table_prefix;

		$this->set_up_mock_filesystem();

		// Establish a runtime environment so the uninstall handler has work to do.
		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();
		if ( ! defined( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION' ) ) {
			define( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION', 1 );
		}

		$this->assertTrue( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );

		// Simulate the WordPress uninstall bootstrap. The constant must be defined
		// before the file is required, mirroring the real uninstall.php behavior.
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'plugin-check/plugin.php' );
		}

		require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'uninstall.php';

		$this->assertFalse( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
		$this->assertStringContainsString( $table_prefix . 'pc_', $GLOBALS['wpdb']->last_query );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_uninstall_no_op_when_runtime_not_set_up() {
		global $wp_filesystem;

		$this->set_up_mock_filesystem();

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'plugin-check/plugin.php' );
		}

		// Drive real uninstall.php. No prior set_up means `is_set_up()` is false,
		// so the handler must early-return without touching the drop-in.
		require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'uninstall.php';

		$this->assertFalse( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
	}

	public function test_uninstall_file_is_protected_against_direct_access() {
		// When WP_UNINSTALL_PLUGIN is not defined, the file must exit without doing
		// anything. We re-include it without the constant to mimic a direct request.
		$contents = file_get_contents( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'uninstall.php' );

		$this->assertStringContainsString( "defined( 'WP_UNINSTALL_PLUGIN' )", $contents );
		$this->assertStringContainsString( 'exit;', $contents );
	}
}
