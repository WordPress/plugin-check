<?php
/**
 * Class WordPress\Plugin_Check\Checker\Runtime_Environment_Setup
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use WordPress\Plugin_Check\Traits\Amend_DB_Base_Prefix;

/**
 * Class to setup the Runtime Environment for Runtime checks.
 *
 * @since 1.0.0
 */
final class Runtime_Environment_Setup {
	use Amend_DB_Base_Prefix;

	/**
	 * Name of the option that records which plugin-owned tables the runtime environment created.
	 *
	 * The record is what cleanup deletes, so that a table the runtime environment did not create can never be
	 * dropped, even if its name happens to match the runtime environment's prefix.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	const CUSTOM_TABLES_OPTION = 'plugin_check_runtime_custom_tables';

	/**
	 * Sets up the WordPress environment for runtime checks
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb               $wpdb          WordPress database abstraction object.
	 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
	 */
	public function set_up() {
		global $wpdb, $wp_filesystem;

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		// Get the existing site URL.
		$site_url = get_option( 'siteurl' );

		// Get the existing active plugins.
		$active_plugins = get_option( 'active_plugins' );

		// Get the existing active theme.
		$active_theme = get_option( 'stylesheet' );

		// Get the existing permalink structure.
		$permalink_structure = get_option( 'permalink_structure' );

		// Get the actual site's base prefix, before it is amended below.
		$base_prefix = $wpdb->base_prefix;

		// Set the new prefix.
		$prefix_cleanup = $this->amend_db_base_prefix();

		/*
		 * Duplicate the schema of any tables owned by other plugins.
		 *
		 * Installing WordPress below only creates the WordPress core tables. Plugins usually register their own tables
		 * in an activation hook, which never runs in the runtime environment, so those tables would be missing even
		 * though the same plugins are activated here and will query them.
		 *
		 * This has to happen before the install, because the install itself already triggers hooks such as
		 * 'update_option' and 'user_register' that active plugins may respond to by querying their own tables.
		 *
		 * See https://github.com/WordPress/plugin-check/issues/234.
		 */
		$created_tables = $this->create_custom_tables( $base_prefix, $wpdb->base_prefix );

		// Create and populate the test database tables if they do not exist.
		if ( $wpdb->posts !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->posts ) ) ) {
			/*
			 * Set the same permalink structure *before* install finishes,
			 * so that wp_install_maybe_enable_pretty_permalinks() does not flush rewrite rules.
			 *
			 * See https://github.com/WordPress/plugin-check/issues/330
			 */
			add_action(
				'populate_options',
				static function () use ( $permalink_structure ) {
					/*
					 * If pretty permalinks are not used, temporarily enable them by setting a permalink structure, to
					 * avoid flushing rewrite rules in wp_install_maybe_enable_pretty_permalinks().
					 * Afterwards, on the 'wp_install' action, set the original (empty) permalink structure.
					 */
					if ( ! $permalink_structure ) {
						add_action(
							'wp_install',
							static function () use ( $permalink_structure ) {
								update_option( 'permalink_structure', $permalink_structure );
							}
						);
						$permalink_structure = '/%postname%/';
					}
					add_option( 'permalink_structure', $permalink_structure );
				}
			);

			$this->install_wordpress( $site_url, $active_theme, $active_plugins );
		}

		// Restore the old prefix.
		$prefix_cleanup();

		// Record the created tables, so that cleanup only ever deletes those. Requires the actual site's prefix.
		$this->record_custom_tables( $created_tables );

		// Return early if the plugin check object cache already exists.
		if ( defined( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION' ) && WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION ) {
			return;
		}

		// Create the object-cache.php file.
		if ( $wp_filesystem || WP_Filesystem() ) {
			// Do not replace the object-cache.php file if it already exists.
			if ( ! $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
				$wp_filesystem->copy( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'drop-ins/object-cache.copy.php', WP_CONTENT_DIR . '/object-cache.php' );
			}
		}
	}

	/**
	 * Cleans up the runtime environment setup.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb               $wpdb          WordPress database abstraction object.
	 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
	 */
	public function clean_up() {
		global $wpdb, $wp_filesystem;

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		// Read the record of created tables while the actual site's prefix is still in place.
		$custom_tables = (array) get_option( self::CUSTOM_TABLES_OPTION, array() );

		$prefix_cleanup = $this->amend_db_base_prefix();
		$tables         = $wpdb->tables();

		$tables = $this->ignore_custom_tables( $tables );

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Remove the tables that were duplicated for other plugins as well.
		$this->drop_custom_tables( $wpdb->base_prefix, $custom_tables );

		// Restore the old prefix.
		$prefix_cleanup();

		delete_option( self::CUSTOM_TABLES_OPTION );

		// Return early if the plugin check object cache does not exist.
		if ( ! defined( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION' ) || ! WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION ) {
			return;
		}

		// Remove the object-cache.php file.
		if ( $wp_filesystem || WP_Filesystem() ) {
			if ( ! $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
				return;
			}

			// Check the drop-in file matches the copy.
			$original_content = $wp_filesystem->get_contents( WP_CONTENT_DIR . '/object-cache.php' );
			$copy_content     = $wp_filesystem->get_contents( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'drop-ins/object-cache.copy.php' );

			if ( $original_content && $original_content === $copy_content ) {
				$wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
			}
		}
	}

	/**
	 * Excludes the custom user and user meta tables from the list of tables to be deleted.
	 *
	 * @since 1.7.0
	 *
	 * @param array $tables List of WordPress database tables.
	 * @return array List of WordPress database tables to delete.
	 */
	private function ignore_custom_tables( array $tables ): array {
		// Do not remove custom tables (which by definition weren't duplicated because we cannot override constants).
		if ( isset( $tables['users'] ) && defined( 'CUSTOM_USER_TABLE' ) && CUSTOM_USER_TABLE === $tables['users'] ) {
			unset( $tables['users'] );
		}
		if ( isset( $tables['usermeta'] ) && defined( 'CUSTOM_USER_META_TABLE' ) && CUSTOM_USER_META_TABLE === $tables['usermeta'] ) {
			unset( $tables['usermeta'] );
		}
		return $tables;
	}

	/**
	 * Returns the names of the database tables that installing WordPress creates.
	 *
	 * The `$wpdb->tables` property is deliberately not used here: it is a public property that plugins append their
	 * own tables to, e.g. WooCommerce does so for its lookup and meta tables. Relying on it would classify exactly
	 * those tables as core ones and therefore skip them. `wp_get_db_schema()` is WordPress's own definition of what
	 * the install creates, and it stays accurate as core changes.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $base_prefix    The actual site's database table base prefix.
	 * @param string $runtime_prefix The runtime environment's database table base prefix.
	 * @return string[] List of table names, without any prefix.
	 */
	private function get_core_table_names( string $base_prefix, string $runtime_prefix ): array {
		// `wp_get_db_schema()` lives in this file, which callers are not required to have loaded already.
		require_once ABSPATH . 'wp-admin/includes/schema.php';

		if ( ! preg_match_all( '/CREATE TABLE (?:IF NOT EXISTS )?`?([0-9a-zA-Z$_]+)`?/i', wp_get_db_schema( 'all' ), $matches ) ) {
			return array();
		}

		$table_names = array();

		foreach ( $matches[1] as $table ) {
			/*
			 * The schema uses whichever prefix is currently in place, so strip either one. The runtime prefix is
			 * checked first because it starts with the base prefix.
			 */
			foreach ( array( $runtime_prefix, $base_prefix ) as $prefix ) {
				if ( str_starts_with( $table, $prefix ) ) {
					$table = substr( $table, strlen( $prefix ) );
					break;
				}
			}

			// Strip the site ID segment that Multisite tables carry, e.g. `3_posts`.
			$table_names[] = preg_replace( '/^\d+_/', '', $table );
		}

		return $table_names;
	}

	/**
	 * Returns the names of the database tables that belong to plugins rather than to WordPress core.
	 *
	 * The returned names have the base prefix stripped, so that they can be combined with either the actual site's
	 * base prefix or the runtime environment's base prefix.
	 *
	 * @since n.e.x.t
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $base_prefix    The actual site's database table base prefix.
	 * @param string $runtime_prefix The runtime environment's database table base prefix.
	 * @return string[] List of table names, without the base prefix.
	 */
	private function get_custom_table_names( string $base_prefix, string $runtime_prefix ): array {
		global $wpdb;

		$core_tables = array_flip( $this->get_core_table_names( $base_prefix, $runtime_prefix ) );

		// `SHOW FULL TABLES` is used over `SHOW TABLES` so that views can be told apart from base tables.
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SHOW FULL TABLES LIKE %s', $wpdb->esc_like( $base_prefix ) . '%' ),
			ARRAY_N
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$custom_tables = array();

		foreach ( $rows as $row ) {
			if ( ! isset( $row[0] ) ) {
				continue;
			}

			// Views cannot be duplicated with `CREATE TABLE ... LIKE`. Any other table type can.
			if ( isset( $row[1] ) && false !== stripos( (string) $row[1], 'VIEW' ) ) {
				continue;
			}

			// Skip the runtime environment's own tables, which also match the site's base prefix.
			if ( str_starts_with( $row[0], $runtime_prefix ) ) {
				continue;
			}

			$table_name = substr( $row[0], strlen( $base_prefix ) );

			/*
			 * On Multisite, the tables of sites other than the main site are prefixed with their site ID, e.g.
			 * `wp_3_posts`. Strip that segment so that those tables are still recognized as core tables.
			 */
			$without_site_id = preg_replace( '/^\d+_/', '', $table_name );

			if ( isset( $core_tables[ $table_name ] ) || isset( $core_tables[ $without_site_id ] ) ) {
				continue;
			}

			$custom_tables[] = $table_name;
		}

		return $custom_tables;
	}

	/**
	 * Duplicates the schema of all plugin-owned database tables into the runtime environment.
	 *
	 * Only the table structure is duplicated, never any data, so that runtime checks cannot read or modify the actual
	 * site's content.
	 *
	 * A table that already exists is skipped rather than replaced, and is not reported as created. It could be a
	 * leftover from an earlier run, but it could equally be a table of the actual site that happens to match the
	 * runtime environment's prefix, and such a table must never be written to or, later on, dropped.
	 *
	 * @since n.e.x.t
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $base_prefix    The actual site's database table base prefix.
	 * @param string $runtime_prefix The runtime environment's database table base prefix.
	 * @return string[] List of the table names that were created, without the base prefix.
	 */
	private function create_custom_tables( string $base_prefix, string $runtime_prefix ): array {
		global $wpdb;

		$created = array();

		foreach ( $this->get_custom_table_names( $base_prefix, $runtime_prefix ) as $table_name ) {
			$source = $base_prefix . $table_name;
			$target = $runtime_prefix . $table_name;

			if ( $target === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $target ) ) ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( false !== $wpdb->query( "CREATE TABLE `$target` LIKE `$source`" ) ) {
				$created[] = $table_name;
			}
		}

		return $created;
	}

	/**
	 * Records which plugin-owned tables the runtime environment created.
	 *
	 * Must be called while the actual site's database table prefix is in place, so that the record is stored in the
	 * site's own options table rather than the runtime environment's.
	 *
	 * The names are merged with any already recorded, so that a second setup without an intermediate cleanup does not
	 * cause the tables of the first one to be forgotten and left behind.
	 *
	 * @since n.e.x.t
	 *
	 * @param string[] $table_names List of table names that were created, without the base prefix.
	 */
	private function record_custom_tables( array $table_names ): void {
		$recorded = (array) get_option( self::CUSTOM_TABLES_OPTION, array() );
		$merged   = array_values( array_unique( array_merge( $recorded, $table_names ) ) );

		if ( $merged === $recorded ) {
			return;
		}

		update_option( self::CUSTOM_TABLES_OPTION, $merged, false );
	}

	/**
	 * Removes the plugin-owned database tables that the runtime environment created.
	 *
	 * The names come from the record written during setup, never from the database, so that only tables the runtime
	 * environment created itself can be dropped.
	 *
	 * @since n.e.x.t
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $runtime_prefix The runtime environment's database table base prefix.
	 * @param array  $table_names    List of table names to drop, without the base prefix.
	 */
	private function drop_custom_tables( string $runtime_prefix, array $table_names ): void {
		global $wpdb;

		foreach ( $table_names as $table_name ) {
			if ( ! is_string( $table_name ) || '' === $table_name ) {
				continue;
			}

			$table = $runtime_prefix . $table_name;

			$wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Tests if the runtime environment is currently set up.
	 *
	 * This returns true when the plugin's object-cache.php drop-in is active in the current request and/or when the
	 * custom runtime environment database tables are present.
	 *
	 * @since 1.3.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return bool True if the runtime environment is set up, false if not.
	 */
	public function is_set_up() {
		global $wpdb;

		if ( defined( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION' ) ) {
			return true;
		}

		// Set the custom prefix to check for the runtime environment tables.
		$prefix_cleanup = $this->amend_db_base_prefix();

		$tables_present = $wpdb->posts === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->posts ) );

		// Restore the old prefix.
		$prefix_cleanup();

		return $tables_present;
	}

	/**
	 * Checks if the WordPress Environment can be set up for runtime checks.
	 *
	 * @since 1.0.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
	 *
	 * @return bool Returns true if the runtime environment can be set up, false if not.
	 */
	public function can_set_up() {
		global $wp_filesystem;

		if ( defined( 'CUSTOM_USER_TABLE' ) || defined( 'CUSTOM_USER_META_TABLE' ) ) {
			// When these constants are defined, we cannot duplicate the user tables for testing.
			return false;
		}

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		if ( ! is_object( $wp_filesystem ) && ! WP_Filesystem() ) {
			return false;
		}

		// Check if the object-cache.php file exists.
		if ( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
			// Check If the object-cache.php file is the Plugin Check version.
			if ( defined( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION' ) && WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION ) {
				return true;
			}
		} else {
			// Get the correct Plugin Check directory when run too early.
			if ( ! defined( 'WP_PLUGIN_CHECK_PLUGIN_DIR_PATH' ) ) {
				$object_cache_copy = dirname( __DIR__, 2 ) . '/plugin-check/drop-ins/object-cache.copy.php';
			} else {
				$object_cache_copy = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'drop-ins/object-cache.copy.php';
			}

			// If the file does not exist, check if we can place it.
			$wp_filesystem->copy( $object_cache_copy, WP_CONTENT_DIR . '/object-cache.php' );

			/**
			 * PHPStan ignore reason: PHPStan raised an issue because we have redundant file existence checks in our code.
			 * We perform this double check because we want to ensure that we can write the file we're testing.
			 *
			 * @phpstan-ignore-next-line
			 */
			if ( $wp_filesystem->exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
				// Remove the file before returning.
				$wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );

				return true;
			}
		}

		return false;
	}

	/**
	 * Installs WordPress, while providing tweaks to allow for early execution of the install process.
	 *
	 * @since 1.3.0
	 *
	 * @param string   $active_siteurl The actual site's site URL.
	 * @param string   $active_theme   The actual site's theme slug.
	 * @param string[] $active_plugins The actual site's list of plugin basenames.
	 */
	private function install_wordpress( string $active_siteurl, string $active_theme, array $active_plugins ): void {
		if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
			$site_url             = $active_siteurl;
			$_SERVER['HTTP_HOST'] = preg_replace( '#^https?://#', '', rtrim( $site_url, '/' ) );
		}

		// Do not send post-install notification email, see https://github.com/WordPress/plugin-check/issues/424.
		add_filter( 'pre_wp_mail', '__return_false' );

		// The `wp_install()` function requires the WP_DEFAULT_THEME constant to be set.
		if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
			define( 'WP_DEFAULT_THEME', $active_theme );
		}

		// The `wp_install()` function requires some pluggable functions like `get_user_by()` to be loaded.
		if ( ! function_exists( 'get_user_by' ) ) {
			require_once ABSPATH . '/wp-includes/pluggable.php';
		}

		/*
		 * Cookie constants need to be set before installation, which normally happens immediately after
		 * 'muplugins_loaded', which is when the logic here typically runs. It is therefore safe to call these
		 * functions here already.
		 */
		if ( doing_action( 'muplugins_loaded' ) || ! did_action( 'muplugins_loaded' ) ) {
			if ( is_multisite() ) {
				ms_cookie_constants();
			}
			wp_cookie_constants();
		}

		wp_install(
			'Plugin Check',
			'plugincheck',
			'demo@plugincheck.test',
			false
		);

		remove_filter( 'pre_wp_mail', '__return_false' );

		// Activate the same plugins in the test environment.
		update_option( 'active_plugins', $active_plugins );
	}
}
