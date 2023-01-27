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
 * Class for the preparation step to force the plugin to be checks as the only active plugin.
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
					'%1$s %2$s',
					__( 'Plugin is not exists at', 'plugin-check' ),
					$this->plugin_basename
				)
			);
		}

		// Return the cleanup function.
		return function() {
			global $wp_theme_directories;

			remove_filter( 'template', array( $this, 'get_theme_slug' ) );
			remove_filter( 'stylesheet', array( $this, 'get_theme_slug' ) );
			remove_filter( 'pre_option_template', array( $this, 'get_theme_slug' ) );
			remove_filter( 'pre_option_stylesheet', array( $this, 'get_theme_slug' ) );
			remove_filter( 'pre_option_current_theme', array( $this, 'get_theme_name' ) );

			remove_filter( 'pre_option_template_root', array( $this, 'get_theme_root' ) );
			remove_filter( 'pre_option_stylesheet_root', array( $this, 'get_theme_root' ) );

			if ( ! empty( $this->themes_dir ) ) {
				$index = array_search( untrailingslashit( $this->themes_dir ), $wp_theme_directories, true );
				if ( false !== $index ) {
					array_splice( $wp_theme_directories, $index, 1 );
					$wp_theme_directories = array_values( $wp_theme_directories );

					// Force new directory scan to remove the test theme directory.
					search_theme_directories( true );
				}
			}
		};
	}

	/**
	 * Filter active plugins.
	 *
	 * @param array $active_plugins List of active plugins.
	 *
	 * @return void
	 */
	public function filter_active_plugins( $active_plugins ) {

		if ( in_array( $this->plugin_basename, $active_plugins, true ) ) {

			// $active_plugins
		}
	}
}
