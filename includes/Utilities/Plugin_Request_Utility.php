<?php
/**
 * Class WordPress\Plugin_Check\Utilities
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

use Exception;
use WordPress\Plugin_Check\Checker\Abstract_Check_Runner;
use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\CLI_Runner;

/**
 * Class providing utility methods to return plugin information based on the request.
 *
 * @since 1.0.0
 */
class Plugin_Request_Utility {

	/**
	 * Instance of the current runner based on the request.
	 *
	 * @since 1.0.0
	 * @var Abstract_Check_Runner|null
	 */
	protected static $runner;

	/**
	 * The universal runtime preparation cleanups if applied.
	 *
	 * @since 1.0.0
	 * @var callable|null
	 */
	protected static $cleanup;

	/**
	 * Returns the plugin basename based on the input provided.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_slug The plugin slug or basename.
	 * @return string The plugin basename.
	 *
	 * @throws Exception Thrown if an invalid basename or plugin slug is provided.
	 */
	public static function get_plugin_basename_from_input( $plugin_slug ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( empty( $plugin_slug ) ) {
			throw new Exception( 'Invalid plugin slug: Plugin slug must not be empty.' );
		}

		$plugins = get_plugins();

		// Is the provided value is a full plugin basename?
		if ( isset( $plugins[ $plugin_slug ] ) ) {
			return $plugin_slug;
		}

		if ( strpos( $plugin_slug, '/' ) ) {
			throw new Exception(
				sprintf(
					'Invalid plugin basename: Plugin with basename %s is not installed.',
					$plugin_slug
				)
			);
		}

		foreach ( array_keys( $plugins ) as $plugin_basename ) {
			if ( strpos( $plugin_basename, $plugin_slug . '/' ) === 0 ) {
				return $plugin_basename;
			}
		}

		throw new Exception(
			sprintf(
				'Invalid plugin slug: Plugin with slug %s is not installed.',
				$plugin_slug
			)
		);
	}

	/**
	 * Initializes the runner classes.
	 *
	 * @since 1.0.0
	 */
	public static function initialize_runner() {
		$runners = array(
			new CLI_Runner(),
			new AJAX_Runner(),
		);

		foreach ( $runners as $runner ) {
			if ( $runner->is_plugin_check() ) {
				add_action(
					'muplugins_loaded',
					function () use ( $runner ) {
						static::$cleanup = $runner->prepare();
						static::$runner  = $runner;
					}
				);

				break;
			}
		}
	}

	/**
	 * Get the Runner class for the current request.
	 *
	 * @since 1.0.0
	 *
	 * @return Abstract_Check_Runner|null The Runner class for the request or null.
	 */
	public static function get_runner() {
		if ( null !== static::$runner ) {
			return static::$runner;
		}

		return null;
	}

	/**
	 * Runs the cleanup functions and destroys the runner.
	 *
	 * @since 1.0.0
	 */
	public static function destroy_runner() {
		// Run the cleanup functions.
		if ( null !== self::$cleanup ) {
			call_user_func( self::$cleanup );
		}

		static::$runner = null;
	}

	/**
	 * Gets the directories to ignore using the filter.
	 *
	 * @since 1.0.0
	 */
	public static function get_directories_to_ignore() {
		// By default, ignore the '.git', 'vendor' and 'node_modules' directories.
		$default_ignore_directories = array(
			'.git',
			'vendor',
			'node_modules',
		);

		/**
		 * Filters the directories to ignore.
		 *
		 * @since 1.0.0
		 *
		 * @param array $default_ignore_directories An array of directories to ignore.
		 */
		$directories_to_ignore = (array) apply_filters( 'wp_plugin_check_ignore_directories', $default_ignore_directories );

		return $directories_to_ignore;
	}

	/**
	 * Gets the files to ignore using the filter.
	 *
	 * @since 1.0.2
	 */
	public static function get_files_to_ignore() {
		$default_ignore_files = array();

		/**
		 * Filters the files to ignore.
		 *
		 * @since 1.0.2
		 *
		 * @param array $default_ignore_files An array of files to ignore.
		 */
		$files_to_ignore = (array) apply_filters( 'wp_plugin_check_ignore_files', $default_ignore_files );

		return $files_to_ignore;
	}

	/**
	 * Returns the plugin basename after downloading and installing the plugin.
	 *
	 * @since 1.1.0
	 *
	 * @param string $plugin_url The URL of the plugin to download.
	 * @return string The plugin basename after downloading and installing the plugin.
	 *
	 * @throws Exception Thrown if an invalid URL given or zip could be extracted properly.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public static function download_plugin( $plugin_url ) {
		$args     = array(
			'timeout' => 60,
		);
		$response = wp_safe_remote_get( $plugin_url, $args );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw new Exception(
				__( 'Downloading the zip file failed.', 'plugin-check' )
			);
		}

		// Prevents URL from wporg.
		if ( false !== strpos( $plugin_url, '#wporgapi:' ) ) {
			$plugin_info_url = substr( $plugin_url, strpos( $plugin_url, '#wporgapi:' ) );
			$plugin_info_url = str_replace( '#wporgapi:', '', $plugin_info_url );
			$plugin_url      = substr( $plugin_url, 0, strpos( $plugin_url, '#' ) );
		}

		$basename = basename( $plugin_url );

		require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem();

		global $wp_filesystem;

		// Create the name of the file and the declare the directory and path.
		$plugin_check_dir = self::get_upload_dir();

		$response_zip_body = wp_remote_retrieve_body( $response );

		$file_path = $plugin_check_dir . $basename;

		if ( ! $wp_filesystem->put_contents( $plugin_check_dir . $basename, $response_zip_body ) ) {
			throw new Exception(
				__( 'Saving zip file failed.', 'plugin-check' )
			);
		}

		$temp_dir = $plugin_check_dir . strtotime( 'now' ) . '/';

		// Unzip file.
		$unzip_file = unzip_file( $file_path, $temp_dir );

		if ( true !== $unzip_file ) {
			throw new Exception( $unzip_file->get_error_message() );
		}

		// Remove zip file.
		unlink( $file_path );

		if ( ! empty( $plugin_info_url ) && filter_var( $plugin_info_url, FILTER_VALIDATE_URL ) ) {
			$response_json = wp_safe_remote_get( $plugin_info_url );

			if ( is_wp_error( $response_json ) || 200 !== wp_remote_retrieve_response_code( $response_json ) ) {
				throw new Exception(
					__( 'Fetching data failed.', 'plugin-check' )
				);
			}

			$response_body = wp_remote_retrieve_body( $response_json );

			json_decode( $response_body );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				throw new Exception(
					__( 'Invalid JSON content.', 'plugin-check' )
				);
			}

			if ( ! $wp_filesystem->put_contents( $plugin_check_dir . 'plugin-info.json', $response_body ) ) {
				throw new Exception(
					__( 'Saving JSON file failed.', 'plugin-check' )
				);
			}
		}

		$files              = scandir( $temp_dir );
		$files              = array_diff( $files, array( '.', '..' ) );
		$target_folder_name = ! empty( $files ) && 1 === count( $files ) ? reset( $files ) : '';

		return $temp_dir . $target_folder_name;
	}

	/**
	 * Get the upload directory for the plugin check.
	 *
	 * @since 1.1.0
	 *
	 * @return string The upload directory for the plugin check.
	 */
	public static function get_upload_dir() {
		$upload_dir = trailingslashit( get_temp_dir() ) . 'plugin-check/';

		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir, 0755, true );
		}

		return $upload_dir;
	}

	/**
	 * Checks if the directory is a valid plugin.
	 *
	 * @since 1.1.0
	 *
	 * @param string $directory Directory.
	 * @return bool true if the directory is valid plugin, otherwise false.
	 */
	public static function is_directory_valid_plugin( $directory ) {
		$is_valid = false;

		if ( is_dir( $directory ) ) {
			$files = glob( $directory . '/*.php' );

			foreach ( $files as $file ) {
				$plugin_data = get_plugin_data( $file );
				if ( ! empty( $plugin_data['Name'] ) ) {
					$is_valid = true;
					break;
				}
			}
		}

		return $is_valid;
	}
}
