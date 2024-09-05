<?php
/**
 * Class WordPress\Plugin_Check\Checker\Preparations\Force_Single_Plugin_Preparation
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Preparations;

use Exception;
use WordPress\Plugin_Check\Checker\Preparation;
use WP_Plugin_Dependencies;

/**
 * Class for the preparation to force the plugin to be checked as the only active plugin.
 *
 * This ensures the plugin is checked as much in isolation as possible.
 *
 * @since 1.0.0
 */
class Force_Single_Plugin_Preparation implements Preparation {

	/**
	 * Plugin slug of the plugin to check.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * Sets the plugin slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_basename Slug of the plugin, E.g. "akismet\akismet.php".
	 */
	public function __construct( $plugin_basename ) {
		$this->plugin_basename = $plugin_basename;
	}

	/**
	 * Runs this preparation step for the environment and returns a cleanup function.
	 *
	 * @since 1.0.0
	 *
	 * @return callable Cleanup function to revert any changes made here.
	 *
	 * @throws Exception Thrown when preparation fails.
	 */
	public function prepare() {
		$valid_plugin = validate_plugin( $this->plugin_basename );

		// Check if the plugin exists.
		if ( is_wp_error( $valid_plugin ) ) {

			throw new Exception(
				sprintf(
					/* translators: 1: plugin basename, 2: error message */
					__( 'Invalid plugin %1$s: %2$s', 'plugin-check' ),
					$this->plugin_basename,
					$valid_plugin->get_error_message()
				)
			);
		}

		add_filter( 'option_active_plugins', array( $this, 'filter_active_plugins' ) );
		add_filter( 'default_option_active_plugins', array( $this, 'filter_active_plugins' ) );

		// Return the cleanup function.
		return function () {
			remove_filter( 'option_active_plugins', array( $this, 'filter_active_plugins' ) );
			remove_filter( 'default_option_active_plugins', array( $this, 'filter_active_plugins' ) );
		};
	}

	/**
	 * Filters active plugins to only include required ones.
	 *
	 * This means:
	 *
	 * * The plugin being tested
	 * * All dependencies of the plugin being tested
	 * * Plugin Check itself
	 * * All plugins depending on Plugin Check (they could be adding new checks)
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Now includes dependencies and dependents.
	 *
	 * @param mixed $active_plugins List of active plugins.
	 * @return mixed List of active plugins.
	 */
	public function filter_active_plugins( $active_plugins ) {
		if ( ! is_array( $active_plugins ) ) {
			return $active_plugins;
		}

		// The plugin being tested isn't actually active yet.
		if ( ! in_array( $this->plugin_basename, $active_plugins, true ) ) {
			return $active_plugins;
		}

		if ( defined( 'WP_PLUGIN_CHECK_MAIN_FILE' ) ) {
			$plugin_check_file = WP_PLUGIN_CHECK_MAIN_FILE;
		} else {
			$plugin_check_file = basename( dirname( __DIR__, 3 ) ) . '/plugin.php';
		}

		$plugin_check_basename = plugin_basename( $plugin_check_file );

		$new_active_plugins = array(
			$this->plugin_basename, // Plugin to test.
			$plugin_check_basename, // Plugin Check itself.
		);

		// Plugin dependencies support was added in WordPress 6.5.
		if ( class_exists( 'WP_Plugin_Dependencies' ) ) {
			WP_Plugin_Dependencies::initialize();

			$new_active_plugins = array_merge(
				$new_active_plugins,
				WP_Plugin_Dependencies::get_dependencies( $this->plugin_basename )
			);

			$new_active_plugins = array_merge(
				$new_active_plugins,
				// Include any dependents of Plugin Check, but only if they were already active.
				array_filter(
					WP_Plugin_Dependencies::get_dependents( dirname( $plugin_check_basename ) ),
					static function ( $dependent ) use ( $active_plugins ) {
						return in_array( $dependent, $active_plugins, true );
					}
				)
			);
		}

		// Removes duplicates, for example if Plugin Check is the plugin being tested.
		return array_unique( $new_active_plugins );
	}
}
