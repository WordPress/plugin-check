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

	public function test_set_up_fires_setup_environment_hooks() {
		$this->set_up_mock_filesystem();

		$calls = array();

		add_action(
			'wp_plugin_check_before_runtime_setup',
			static function ( $payload ) use ( &$calls ) {
				$calls[] = array( 'before', $payload );
			}
		);

		add_action(
			'wp_plugin_check_after_runtime_setup',
			static function ( $payload ) use ( &$calls ) {
				$calls[] = array( 'after', $payload );
			}
		);

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		$this->assertCount( 2, $calls );
		$this->assertSame( 'before', $calls[0][0] );
		$this->assertSame( 'after', $calls[1][0] );
		// Hooks receive the runtime-setup payload array, not the instance.
		$this->assertIsArray( $calls[0][1] );
		$this->assertIsArray( $calls[1][1] );
		$this->assertArrayHasKey( 'early_exit', $calls[0][1] );
		$this->assertArrayHasKey( 'early_exit', $calls[1][1] );
	}

	public function test_clean_up_fires_cleanup_environment_hooks() {
		$this->set_up_mock_filesystem();

		$calls = array();

		add_action(
			'wp_plugin_check_before_runtime_cleanup',
			static function ( $payload ) use ( &$calls ) {
				$calls[] = array( 'before', $payload );
			}
		);

		add_action(
			'wp_plugin_check_after_runtime_cleanup',
			static function ( $payload ) use ( &$calls ) {
				$calls[] = array( 'after', $payload );
			}
		);

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->clean_up();

		$this->assertCount( 2, $calls );
		$this->assertSame( 'before', $calls[0][0] );
		$this->assertSame( 'after', $calls[1][0] );
		// Hooks receive the runtime-cleanup payload array, not the instance.
		$this->assertIsArray( $calls[0][1] );
		$this->assertIsArray( $calls[1][1] );
		$this->assertArrayHasKey( 'early_exit', $calls[0][1] );
		$this->assertArrayHasKey( 'early_exit', $calls[1][1] );
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

	/**
	 * Ensures the after_setup_environment action fires when set_up() returns early.
	 *
	 * This test relies on the drop-in version constant being defined at this point in the run.
	 * `test_clean_up` defines it earlier in the same process, so set_up() takes the early-return
	 * branch guarded by `WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION`. The after_* action lives
	 * in a `finally` block and must still fire.
	 */
	public function test_set_up_fires_after_action_on_early_return() {
		$this->set_up_mock_filesystem();

		if ( ! defined( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION' ) ) {
			define( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION', 1 );
		}

		$this->assertTrue(
			defined( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION' ) && WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION,
			'Pre-condition: the drop-in version constant must be defined to force the early-return branch.'
		);

		$after_called = false;
		add_action(
			'wp_plugin_check_after_runtime_setup',
			static function () use ( &$after_called ) {
				$after_called = true;
			}
		);

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		$this->assertTrue(
			$after_called,
			'wp_plugin_check_after_runtime_setup must fire even when set_up() returns early.'
		);
	}
}
