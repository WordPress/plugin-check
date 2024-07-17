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
 * @since 1.0.0
 */
final class Runtime_Environment_Setup {

	/**
	 * Sets up the WordPress environment for runtime checks
	 *
	 * @since 1.0.0
	 */
	public function set_up() {
		global $wpdb, $table_prefix;

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

			// Do not send post-install notification email, see https://github.com/WordPress/plugin-check/issues/424.
			add_filter( 'pre_wp_mail', '__return_false' );

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

		// Restore the old prefix.
		$wpdb->set_prefix( $old_prefix );
	}

	/**
	 * Cleans up the runtime environment setup.
	 *
	 * @since 1.0.0
	 */
	public function clean_up() {
		global $wpdb, $table_prefix;

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		$old_prefix = $wpdb->set_prefix( $table_prefix . 'pc_' );
		$tables     = $wpdb->tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Restore the old prefix.
		$wpdb->set_prefix( $old_prefix );
	}
}
