<?php
/**
 * Class WordPress\Plugin_Check\Checker\Preparations\Use_Minimal_Theme_Preparation
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Preparations;

use WordPress\Plugin_Check\Checker\Preparation;
use Exception;

/**
 * Class for the preparation step to force usage of a minimal theme.
 *
 * This ensures the plugin is checked as much in isolation as possible.
 *
 * @since n.e.x.t
 */
class Use_Minimal_Theme_Preparation implements Preparation {

	/**
	 * Theme slug / directory name.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	protected $theme_slug;

	/**
	 * Absolute path to themes root directory.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	protected $themes_dir;

	/**
	 * Sets the theme slug and themes root directory.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $theme_slug Slug of the theme to enforce.
	 * @param string $themes_dir Optional. Absolute path to themes root directory, if not the regular wp-content/themes.
	 */
	public function __construct( $theme_slug, $themes_dir = '' ) {
		$this->theme_slug = $theme_slug;
		$this->themes_dir = $themes_dir;
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
		// Override the theme slug and name.
		add_filter( 'template', array( $this, 'get_theme_slug' ) );
		add_filter( 'stylesheet', array( $this, 'get_theme_slug' ) );
		add_filter( 'pre_option_template', array( $this, 'get_theme_slug' ) );
		add_filter( 'pre_option_stylesheet', array( $this, 'get_theme_slug' ) );
		add_filter( 'pre_option_current_theme', array( $this, 'get_theme_name' ) );

		// Override the theme directory.
		add_filter( 'pre_option_template_root', array( $this, 'get_theme_root' ) );
		add_filter( 'pre_option_stylesheet_root', array( $this, 'get_theme_root' ) );

		// Registers the custom themes directory if relevant.
		if ( ! empty( $this->themes_dir ) ) {
			register_theme_directory( $this->themes_dir );

			// Force new directory scan to ensure the test theme directory is available.
			search_theme_directories( true );
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
	 * Gets the theme slug.
	 *
	 * Used as a filter callback.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The theme slug.
	 */
	public function get_theme_slug() {
		return $this->theme_slug;
	}

	/**
	 * Gets the theme name.
	 *
	 * Used as a filter callback.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The theme name.
	 */
	public function get_theme_name() {
		$theme = wp_get_theme( $this->theme_slug, $this->themes_dir );
		return $theme->display( 'Name' );
	}

	/**
	 * Gets the theme root.
	 *
	 * Used as a filter callback.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The theme root.
	 */
	public function get_theme_root() {
		return get_raw_theme_root( $this->theme_slug, true );
	}
}
