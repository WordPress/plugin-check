<?php
/**
 * Class WordPress\Plugin_Check\Plugin_Context
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check;
use WordPress\Plugin_Check\Traits\Find_Readme;
use WordPressdotorg\Plugin_Directory\Readme\Parser;
use function WP_CLI\Utils\normalize_path;

/**
 * Class representing the context in which the plugin is running.
 *
 * @since 1.0.0
 */
class Plugin_Context {

	use Find_Readme;

	/**
	 * Absolute path of the plugin main file.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $main_file;

	/**
	 * The minimum supported WordPress version of the plugin.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $minimum_supported_wp;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $main_file The absolute path to the plugin main file.
	 */
	public function __construct( $main_file ) {
		if ( function_exists( '\WP_CLI\Utils\normalize_path' ) ) {
			$this->main_file = normalize_path( $main_file );
		} elseif ( function_exists( 'wp_normalize_path' ) ) {
			$this->main_file = wp_normalize_path( $main_file );
		} else {
			$this->main_file = $main_file;
		}
	}

	/**
	 * Returns the plugin basename.
	 *
	 * @since 1.0.0
	 *
	 * @return string Plugin basename.
	 */
	public function basename() {
		return plugin_basename( $this->main_file );
	}

	/**
	 * Returns the absolute path for a relative path to the plugin directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative_path Optional. Relative path. Default '/'.
	 * @return string Absolute path.
	 */
	public function path( $relative_path = '/' ) {
		return plugin_dir_path( $this->main_file ) . ltrim( $relative_path, '/' );
	}

	/**
	 * Returns the full URL for a path relative to the plugin directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative_path Optional. Relative path. Default '/'.
	 * @return string Full URL.
	 */
	public function url( $relative_path = '/' ) {
		return plugin_dir_url( $this->main_file ) . ltrim( $relative_path, '/' );
	}

	/**
	 * Returns the plugin location.
	 *
	 * @since 1.0.0
	 *
	 * @return string The plugin file if single file plugin. Or the plugin folder.
	 */
	public function location() {
		$path = $this->path();

		// Return the plugin path and basename if the path matches the plugin directory.
		if ( WP_PLUGIN_DIR . '/' === $path ) {
			return $path . $this->basename();
		}

		return $path;
	}

	/**
	 * Checks if the plugin is a single file plugin without a dedicated directory.
	 *
	 * This is the case when the single file is directly placed within `wp-content/plugins`.
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if the single file plugin, otherwise false.
	 */
	public function is_single_file_plugin() {
		return $this->path() !== $this->location();
	}

	/**
	 * Determine the minimum supported WordPress version of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string The minimum version supported, or empty string if unknown.
	 */
	public function minimum_supported_wp() {
		if ( ! is_null( $this->minimum_supported_wp ) ) {
			return $this->minimum_supported_wp;
		}

		$this->minimum_supported_wp = '';

		$headers = get_plugin_data( $this->main_file );
		if ( ! empty( $headers['RequiresWP'] ) ) {
			$this->minimum_supported_wp = $headers['RequiresWP'];
		} elseif ( ! $this->is_single_file_plugin() ) {
			// Look for the readme.
			$readme_files = glob( $this->path() . '*' );
			$readme_files = $this->filter_files_for_readme( $readme_files, $this->path() );
			$readme_file  = reset( $readme_files );
			if ( $readme_file ) {
				$parser = new Parser( $readme_file );

				if ( ! empty( $parser->requires ) ) {
					$this->minimum_supported_wp = $parser->requires;
				}
			}
		}

		return $this->minimum_supported_wp;
	}
}
