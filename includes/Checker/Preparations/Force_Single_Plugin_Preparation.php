<?php
/**
 * Class WordPress\Plugin_Check\Checker\Preparations\Force_Single_Plugin_Preparation
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Preparations;

use WordPress\Plugin_Check\Checker\Preparation;
use Exception;

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
	 * @global array $wp_theme_directories
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
					// translators: plugin basename.
					__( 'The plugin %s does not exists', 'plugin-check' ),
					$this->plugin_basename
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
	 *
	 * @return array List of active plugins.
	 */
	public function filter_active_plugins( $active_plugins = array() ) {

		if ( in_array( $this->plugin_basename, $active_plugins, true ) ) {

			return array(
				$this->plugin_basename,
				'plugin-check/plugin-check.php', // At the moment it is added static, we can update this with constant.
			);
		}

		return $active_plugins;
	}
}
