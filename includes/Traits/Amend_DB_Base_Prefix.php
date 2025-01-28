<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Amend_DB_Base_Prefix
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

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
	 */
	protected function amend_db_base_prefix( string $base_prefix_suffix = 'pc_' ) {
		global $wpdb, $table_prefix;

		/*
		 * On Multisite, the `$table_prefix` global is overwritten to contain the blog specific prefix after the
		 * `$wpdb->base_prefix` property has been set. Therefore we need to rely on `$wpdb->base_prefix` if set.
		 */
		$current_base_prefix = isset( $wpdb->base_prefix ) ? $wpdb->base_prefix : $table_prefix;

		$old_prefix = $wpdb->set_prefix( $current_base_prefix . $base_prefix_suffix );

		return function () use ( $old_prefix ) {
			global $wpdb;

			$wpdb->set_prefix( $old_prefix );
		};
	}
}
