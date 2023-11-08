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
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\CLI_Runner;

/**
 * Class providing utility methods to return plugin information based on the request.
 *
 * @since n.e.x.t
 */
class Plugin_Request_Utility {

	/**
	 * Instance of the current runner based on the request.
	 *
	 * @since n.e.x.t
	 * @var Abstract_Check_Runner|null
	 */
	protected static $runner;

	/**
	 * The universal runtime preparation cleanups if applied.
	 *
	 * @since n.e.x.t
	 * @var callable|null
	 */
	protected static $cleanup;

	/**
	 * Returns the plugin basename based on the input provided.
	 *
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
		 * @since n.e.x.t
		 *
		 * @param array $default_ignore_directories An array of directories to ignore.
		 */
		$directories_to_ignore = (array) apply_filters( 'wp_plugin_check_ignore_directories', $default_ignore_directories );

		return $directories_to_ignore;
	}

	/**
	 * Checks if single file plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @return bool true if the single file plugin, otherwise false.
	 */
	public static function is_single_file_plugin( Check_Result $result ) {
		if ( $result->plugin()->path() === $result->plugin()->location() ) {
			return false;
		}

		return true;
	}
}
