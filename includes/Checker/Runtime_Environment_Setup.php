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

		// Set the new prefix.
		$prefix_cleanup = $this->amend_db_base_prefix();

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

		$prefix_cleanup = $this->amend_db_base_prefix();
		$tables         = $wpdb->tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Restore the old prefix.
		$prefix_cleanup();

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
