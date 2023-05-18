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
	public function setup() {
		global $wpdb, $table_prefix, $wp_filesystem;

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		// Get the existing active plugins.
		$active_plugins = get_option( 'active_plugins' );

		// Set the new prefix.
		$old_prefix = $wpdb->set_prefix( $table_prefix . 'pc_' );

		// Create and populate the test database tables if they do not exist.
		if ( $wpdb->posts !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->posts ) ) ) {
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
				$wp_filesystem->copy( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'object-cache.copy.php', WP_CONTENT_DIR . '/object-cache.php' );
			}
		}
	}

	/**
	 * Cleans up the runtime environment setup.
	 *
	 * @since n.e.x.t
	 */
	public function cleanup() {
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
			$copy_content     = $wp_filesystem->get_contents( WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'object-cache.copy.php' );

			if ( $original_content && $original_content === $copy_content ) {
				$wp_filesystem->delete( WP_CONTENT_DIR . '/object-cache.php' );
			}
		}
	}
}
