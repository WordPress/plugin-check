<?php
/**
 * Class WordPress\Plugin_Check\Plugin_Context
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check;

use Exception;
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
	 * Plugin slug.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	protected $slug;

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
	 * @since 1.2.0 Second argument $slug was introduced.
	 *
	 * @param string $main_file The absolute path to the plugin main file.
	 * @param string $slug      The plugin slug.
	 *
	 * @throws Exception Throws exception if not called via regular WP-CLI or WordPress bootstrap order.
	 */
	public function __construct( $main_file, $slug = '' ) {
		if ( function_exists( 'wp_normalize_path' ) ) {
			$this->main_file = wp_normalize_path( $main_file );
		} elseif ( function_exists( '\WP_CLI\Utils\normalize_path' ) ) {
			$this->main_file = normalize_path( $main_file );
		} else {
			throw new Exception(
				__( 'Unknown environment, normalize_path function not found', 'plugin-check' )
			);
		}

		if ( false === strpos( $this->main_file, '.php' ) ) {
			$files = glob( $this->main_file . '/*.php' );
			foreach ( $files as $file ) {
				$plugin_data = get_plugin_data( $file );
				if ( ! empty( $plugin_data['Name'] ) ) {
					$this->main_file = $file;
					break;
				}
			}
		}

		if ( ! empty( $slug ) ) {
			$this->slug = $slug;
		} else {
			$this->slug = basename( dirname( $this->main_file ) );
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
	 * Returns the plugin main file.
	 *
	 * @since 1.0.2
	 *
	 * @return string Plugin main file.
	 */
	public function main_file() {
		return $this->main_file;
	}

	/**
	 * Returns the plugin slug.
	 *
	 * @since 1.2.0
	 *
	 * @return string Plugin slug.
	 */
	public function slug() {
		return $this->slug;
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
		if ( is_dir( $this->main_file ) ) {
			return trailingslashit( $this->main_file ) . ltrim( $relative_path, '/' );
		} else {
			return plugin_dir_path( $this->main_file ) . ltrim( $relative_path, '/' );
		}
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

	/**
	 * Checks if the file is editable.
	 *
	 * @since 1.1.0
	 *
	 * @param string $file Filename.
	 * @return bool true if the file is editable, otherwise false.
	 */
	public function is_file_editable( $file ) {
		$editable = false;

		$editable_extensions = wp_get_plugin_file_editable_extensions( $this->basename() );

		$info = pathinfo( $file );

		$filename  = $info['filename'];
		$dirname   = $info['dirname'];
		$extension = isset( $info['extension'] ) ? strtolower( $info['extension'] ) : '';

		if (
			in_array( $extension, $editable_extensions, true )
			&& file_exists( dirname( $this->main_file() ) . '/' . $file )
			&& ( ! empty( $filename ) && ( '.' !== $filename[0] ) )
			&& ! ( '.' === $dirname[0] && strlen( $dirname ) > 1 )
		) {
			$editable = true;
		}

		return $editable;
	}
}
