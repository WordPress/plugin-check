<?php
/**
 * Class WordPress\Plugin_Check\Checker\Runtime_Environment_Setup
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Class to setup the Runtime Environment for Runtime checks.
 *
 * @since n.e.x.t
 */
final class Runtime_Environment_Setup {

	/**
	 * Sets up the WordPress environment for runtime checks
	 *
	 * @since n.e.x.t
	 */
	public function set_up() {
		global $wpdb, $table_prefix, $wp_filesystem;

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		// Get the existing active plugins.
		$active_plugins = get_option( 'active_plugins' );

		// Get the existing permalink structure.
		$permalink_structure = get_option( 'permalink_structure' );

		// Set the new prefix.
		$old_prefix = $wpdb->set_prefix( $table_prefix . 'pc_' );

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
					add_option( 'permalink_structure', $permalink_structure );
				}
			);

			wp_install(
				'Plugin Check',
				'plugincheck',
				'demo@plugincheck.test',
				false
			);

			// Activate the same plugins in the test environment.
			update_option( 'active_plugins', $active_plugins );
		}

		// Restore the old prefix.
		$wpdb->set_prefix( $old_prefix );

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
	 * @since n.e.x.t
	 */
	public function clean_up() {
		global $wpdb, $table_prefix, $wp_filesystem;

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		$old_prefix = $wpdb->set_prefix( $table_prefix . 'pc_' );
		$tables     = $wpdb->tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Restore the old prefix.
		$wpdb->set_prefix( $old_prefix );

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
	 * Checks if the WordPress Environment can be set up for runtime checks.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool Returns true if the runtime environment can be set up, false if not.
	 */
	public function can_set_up() {
		global $wp_filesystem;

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
}
