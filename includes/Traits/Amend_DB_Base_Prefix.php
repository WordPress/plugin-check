<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Amend_DB_Base_Prefix
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use RuntimeException;
use wpdb;

/**
 * Trait for amending the database table base prefix.
 *
 * @since 1.5.0
 */
trait Amend_DB_Base_Prefix {

	/**
	 * Amends the database table base prefix by appending the given suffix to it.
	 *
	 * This will cause all database table references to point to tables identified by the new base prefix.
	 *
	 * Examples:
	 * * On a single WordPress site, e.g. `wp_pc_posts` and `wp_pc_users` instead of `wp_posts` and `wp_users`.
	 * * On a WordPress Multisite, e.g. `wp_pc_3_posts` and `wp_pc_users` instead of `wp_3_posts` and `wp_users`.
	 *
	 * @since 1.5.0
	 *
	 * @global wpdb   $wpdb         WordPress database abstraction object.
	 * @global string $table_prefix The database table prefix.
	 *
	 * @param string $base_prefix_suffix Optional. Suffix to append to the base prefix. Default 'pc_'.
	 * @return callable Closure to revert the database table prefix to its previous value.
	 *
	 * @throws RuntimeException Thrown if the WordPress database object is not initialized.
	 */
	protected function amend_db_base_prefix( string $base_prefix_suffix = 'pc_' ) {
		/**
		 * WordPress database abstraction object.
		 *
		 * @var wpdb $wpdb
		 */
		global $wpdb;

		/*
		 * On a single WordPress site, we could in theory use the `$table_prefix` global. On Multisite however, the
		 * `$table_prefix` global is overwritten to contain the blog specific prefix after the `$wpdb->base_prefix`
		 * property has been set. Therefore we need to rely on `$wpdb->base_prefix`, which should always be already
		 * set, even when PCP is initializing early.
		 */
		// @phpstan-ignore-next-line isset.property
		if ( ! isset( $wpdb->base_prefix ) ) {
			throw new RuntimeException(
				esc_html__( 'Cannot amend database table prefix as wpdb appears to not be initialized yet.', 'plugin-check' )
			);
		}

		$old_prefix = $wpdb->set_prefix( $wpdb->base_prefix . $base_prefix_suffix );

		return function () use ( $old_prefix ) {
			global $wpdb;

			$wpdb->set_prefix( $old_prefix );
		};
	}
}
