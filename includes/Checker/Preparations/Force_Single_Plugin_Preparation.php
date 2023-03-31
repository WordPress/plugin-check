<?php
/**
 * Class WordPress\Plugin_Check\Checker\Preparations\Force_Single_Plugin_Preparation
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Preparations;

use Exception;
use WordPress\Plugin_Check\Checker\Preparation;

/**
 * Class for the preparation to force the plugin to be checked as the only active plugin.
 *
 * This ensures the plugin is checked as much in isolation as possible.
 *
 * @since n.e.x.t
 */
class Force_Single_Plugin_Preparation implements Preparation {

	/**
	 * Plugin slug.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * Sets the plugin slug.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $plugin_basename Slug of the plugin, E.g. "akismet\akismet.php".
	 */
	public function __construct( $plugin_basename ) {
		$this->plugin_basename = $plugin_basename;
	}

	/**
	 * Runs this preparation step for the environment and returns a cleanup function.
	 *
	 * @since n.e.x.t
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
		return function() {
			remove_filter( 'option_active_plugins', array( $this, 'filter_active_plugins' ) );
			remove_filter( 'default_option_active_plugins', array( $this, 'filter_active_plugins' ) );
		};
	}

	/**
	 * Filter active plugins.
	 *
	 * @param array $active_plugins List of active plugins.
	 * @return array List of active plugins.
	 */
	public function filter_active_plugins( $active_plugins ) {
		if ( is_array( $active_plugins ) && in_array( $this->plugin_basename, $active_plugins, true ) ) {

			if ( defined( 'WP_PLUGIN_CHECK_MAIN_FILE' ) ) {
				$plugin_check_file = WP_PLUGIN_CHECK_MAIN_FILE;
			} else {
				$plugins_dir       = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
				$plugin_check_file = $plugins_dir . '/plugin-check/plugin-check.php';
			}

			$plugin_base_file = plugin_basename( $plugin_check_file );

			// If the plugin-check is the only available plugin then return that one only.
			if ( $this->plugin_basename === $plugin_base_file ) {

				return array(
					$plugin_base_file,
				);
			}

			return array(
				$this->plugin_basename,
				$plugin_base_file,
			);
		}

		return $active_plugins;
	}
}
