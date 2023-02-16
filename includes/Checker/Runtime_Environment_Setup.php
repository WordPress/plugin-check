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
class Runtime_Environment_Setup {

	/**
	 * Sets up the WordPress environment for runtime checks
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function setup() {
		global $wpdb, $wp_filesystem;

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		// Set the new prefix.
		$prefix = $wpdb->set_prefix( 'wppc_' );

		// Create and populate the test database tables if they do not exist.
		if ( 'wppc_posts' !== $wpdb->get_var( "SHOW TABLES LIKE 'wppc_posts'" ) ) {
			wp_install(
				'Plugin Check',
				'plugincheck',
				'demo@plugincheck.test',
				false
			);
		}

		$wpdb->set_prefix( $prefix );

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
	 *
	 * @return void
	 */
	public function cleanup() {
		global $wpdb, $wp_filesystem;

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		$prefix = $wpdb->set_prefix( 'wppc_' );
		$tables = $wpdb->tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$wpdb->set_prefix( $prefix );

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
