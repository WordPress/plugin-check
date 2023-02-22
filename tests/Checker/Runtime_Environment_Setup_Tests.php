<?php
/**
 * Tests for the Checks class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;

class Runtime_Environment_Setup_Tests extends WP_UnitTestCase {

	public function test_setup() {
		global $wp_filesystem, $wpdb;

		$this->set_up_mock_filesystem();

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->setup();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, 'wppc_' ) );
		$this->assertTrue( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
		$this->assertSame( file_get_contents( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'object-cache.copy.php' ), $wp_filesystem->get_contents( WP_CONTENT_DIR . '/object-cache.php' ) );
	}

	public function test_cleanup() {
		global $wp_filesystem, $wpdb;

		$this->set_up_mock_filesystem();

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->setup();

		// Simulate file exists by setting constant found in object-cache.php.
		define( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION', 1 );

		$runtime_setup->cleanup();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, 'wppc_' ) );
		$this->assertFalse( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
	}

	public function test_setup_with_existing_object_cache() {
		global $wp_filesystem, $wpdb;

		$this->set_up_mock_filesystem();

		// Simulate a different object-cache.php.
		$dummy_file_content = '<?php /* Empty object-cache.php drop-in file. */';
		$wp_filesystem->put_contents( WP_CONTENT_DIR . '/object-cache.php', $dummy_file_content );

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->setup();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, 'wppc_' ) );
		$this->assertTrue( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
		$this->assertSame( $dummy_file_content, $wp_filesystem->get_contents( WP_CONTENT_DIR . '/object-cache.php' ) );
	}

	public function test_cleanup_with_existing_object_cache() {
		global $wp_filesystem, $wpdb;

		$this->set_up_mock_filesystem();

		// Simulate a different object-cache.php.
		$dummy_file_content = '<?php /* Empty object-cache.php drop-in file. */';
		$wp_filesystem->put_contents( WP_CONTENT_DIR . '/object-cache.php', $dummy_file_content );

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->setup();
		$runtime_setup->cleanup();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, 'wppc_' ) );
		$this->assertTrue( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
		$this->assertSame( $dummy_file_content, $wp_filesystem->get_contents( WP_CONTENT_DIR . '/object-cache.php' ) );
	}

	private function set_up_mock_filesystem() {
		global $wp_filesystem;

		add_filter(
			'filesystem_method_file',
			function() {
				return __DIR__ . '/../testdata/Filesystem/WP_Filesystem_MockFilesystem.php';
			}
		);
		add_filter(
			'filesystem_method',
			function() {
				return 'MockFilesystem';
			}
		);

		WP_Filesystem();

		// Simulate that the original object-cache.copy.php file exists.
		$wp_filesystem->put_contents( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'object-cache.copy.php', file_get_contents( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'object-cache.copy.php' ) );
	}
}
