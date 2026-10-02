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

	/**
	 * Name of a table simulating one owned by another active plugin, without the base prefix.
	 */
	const CUSTOM_TABLE = 'plugin_check_custom_table';

	public function tear_down() {
		global $wpdb;

		$table = $wpdb->base_prefix . self::CUSTOM_TABLE;

		$wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$table = $wpdb->base_prefix . 'pc_' . self::CUSTOM_TABLE;

		$wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$wpdb->tables = array_values( array_diff( $wpdb->tables, array( self::CUSTOM_TABLE ) ) );

		delete_option( Runtime_Environment_Setup::CUSTOM_TABLES_OPTION );

		parent::tear_down();
	}

	/**
	 * Creates a table simulating one owned by another active plugin, with a single row of data in it.
	 *
	 * @return string The full table name, including the base prefix.
	 */
	private function create_custom_table() {
		global $wpdb;

		$table = $wpdb->base_prefix . self::CUSTOM_TABLE;

		$this->create_table( $table );

		return $table;
	}

	/**
	 * Creates a table with a single row of data in it.
	 *
	 * @param string $table Full table name, including the base prefix.
	 */
	private function create_table( string $table ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			"CREATE TABLE `$table` (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				label varchar(20) NOT NULL,
				PRIMARY KEY (id)
			)"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$wpdb->insert( $table, array( 'label' => 'actual site data' ) );
	}

	public function test_set_up_duplicates_custom_plugin_tables() {
		global $wpdb;

		$this->set_up_mock_filesystem();

		$this->create_custom_table();
		$runtime_table = $wpdb->base_prefix . 'pc_' . self::CUSTOM_TABLE;

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		$this->assertSame(
			$runtime_table,
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $runtime_table ) ),
			'The table of the other plugin was not duplicated into the runtime environment.'
		);
	}

	public function test_set_up_duplicates_tables_registered_on_the_wpdb_tables_property() {
		global $wpdb;

		$this->set_up_mock_filesystem();

		$this->create_custom_table();

		/*
		 * `$wpdb->tables` is a public property that plugins append their own tables to, e.g. WooCommerce does so for
		 * its lookup and meta tables. Those tables still have to be duplicated.
		 */
		$wpdb->tables[] = self::CUSTOM_TABLE;

		$runtime_table = $wpdb->base_prefix . 'pc_' . self::CUSTOM_TABLE;

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		$this->assertSame(
			$runtime_table,
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $runtime_table ) ),
			'A table registered on the $wpdb->tables property was treated as a core table and skipped.'
		);
	}

	public function test_set_up_does_not_copy_data_from_custom_plugin_tables() {
		global $wpdb;

		$this->set_up_mock_filesystem();

		$this->create_custom_table();
		$runtime_table = $wpdb->base_prefix . 'pc_' . self::CUSTOM_TABLE;

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		$this->assertSame(
			'0',
			$wpdb->get_var( "SELECT COUNT(*) FROM `$runtime_table`" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'The duplicated table contains data from the actual site.'
		);
	}

	public function test_clean_up_removes_duplicated_custom_plugin_tables() {
		global $wpdb;

		$this->set_up_mock_filesystem();

		$source_table  = $this->create_custom_table();
		$runtime_table = $wpdb->base_prefix . 'pc_' . self::CUSTOM_TABLE;

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();
		$runtime_setup->clean_up();

		$this->assertNull(
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $runtime_table ) ),
			'The duplicated table was not removed from the runtime environment.'
		);
		$this->assertSame(
			$source_table,
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $source_table ) ),
			'The actual site table was removed.'
		);
	}

	public function test_clean_up_does_not_drop_tables_it_did_not_create() {
		global $wpdb;

		$this->set_up_mock_filesystem();

		$this->create_custom_table();

		/*
		 * A table of the actual site whose name happens to match the runtime environment's prefix. Cleanup must leave
		 * it alone, since the runtime environment did not create it.
		 */
		$runtime_table = $wpdb->base_prefix . 'pc_' . self::CUSTOM_TABLE;

		$this->create_table( $runtime_table );

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();
		$runtime_setup->clean_up();

		$this->assertSame(
			$runtime_table,
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $runtime_table ) ),
			'A table that the runtime environment did not create was dropped.'
		);
		$this->assertSame(
			'1',
			$wpdb->get_var( "SELECT COUNT(*) FROM `$runtime_table`" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'The data of a table that the runtime environment did not create was lost.'
		);
	}

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
