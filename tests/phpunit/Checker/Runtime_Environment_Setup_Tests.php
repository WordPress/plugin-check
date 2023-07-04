<?php
/**
 * Tests for the Checks class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Test_Utils\Traits\With_Mock_Filesystem;

class Runtime_Environment_Setup_Tests extends WP_UnitTestCase {

	use With_Mock_Filesystem;

	public function test_set_up() {
		global $wp_filesystem, $wpdb, $table_prefix;

		$this->set_up_mock_filesystem();

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, $table_prefix . 'pc_' ) );
		$this->assertTrue( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
		$this->assertSame( file_get_contents( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'drop-ins/object-cache.copy.php' ), $wp_filesystem->get_contents( WP_CONTENT_DIR . '/object-cache.php' ) );
	}

	public function test_setup_with_existing_object_cache() {
		global $wp_filesystem, $wpdb, $table_prefix;

		$this->set_up_mock_filesystem();

		// Simulate a different object-cache.php.
		$dummy_file_content = '<?php /* Empty object-cache.php drop-in file. */';
		$wp_filesystem->put_contents( WP_CONTENT_DIR . '/object-cache.php', $dummy_file_content );

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, $table_prefix . 'pc_' ) );
		$this->assertTrue( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
		$this->assertSame( $dummy_file_content, $wp_filesystem->get_contents( WP_CONTENT_DIR . '/object-cache.php' ) );
	}

	public function test_cleanup_with_existing_object_cache() {
		global $wp_filesystem, $wpdb, $table_prefix;

		$this->set_up_mock_filesystem();

		// Simulate a different object-cache.php.
		$dummy_file_content = '<?php /* Empty object-cache.php drop-in file. */';
		$wp_filesystem->put_contents( WP_CONTENT_DIR . '/object-cache.php', $dummy_file_content );

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();
		$runtime_setup->clean_up();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, $table_prefix . 'pc_' ) );
		$this->assertTrue( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
		$this->assertSame( $dummy_file_content, $wp_filesystem->get_contents( WP_CONTENT_DIR . '/object-cache.php' ) );
	}

	public function test_can_set_up() {
		$this->set_up_mock_filesystem();

		$runtime_setup = new Runtime_Environment_Setup();

		$this->assertTrue( $runtime_setup->can_set_up() );
	}

	public function test_can_set_up_with_existing_object_cache() {
		global $wp_filesystem;

		$this->set_up_mock_filesystem();

		// Simulate a different object-cache.php.
		$dummy_file_content = '<?php /* Empty object-cache.php drop-in file. */';
		$wp_filesystem->put_contents( WP_CONTENT_DIR . '/object-cache.php', $dummy_file_content );

		$runtime_setup = new Runtime_Environment_Setup();

		$this->assertFalse( $runtime_setup->can_set_up() );
	}

	public function test_can_set_up_with_failing_filesystem() {
		global $wp_filesystem;

		$this->set_up_failing_mock_filesystem();

		$runtime_setup = new Runtime_Environment_Setup();

		$this->assertFalse( $runtime_setup->can_set_up() );
	}

	public function test_clean_up() {
		global $wp_filesystem, $wpdb, $table_prefix;

		$this->set_up_mock_filesystem();

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		// Simulate file exists by setting constant found in object-cache.php.
		define( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION', 1 );

		$runtime_setup->clean_up();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, $table_prefix . 'pc_' ) );
		$this->assertFalse( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) );
	}
}
