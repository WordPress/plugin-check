<?php
/**
 * Class WordPress\Plugin_Check\Utilities
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

use Exception;
use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Checker\AJAX_Runner;

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
	 * @var Abstract_Check_Runner
	 */
	protected static $runner;

	/**
	 * The universal runtime preparation cleanups if applied.
	 *
	 * @since n.e.x.t
	 * @var callable
	 */
	protected static $cleanup;

	/**
	 * Returns the plugin basename based on the input provided.
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

		foreach ( $plugins as $plugin_basename => $plugin_data ) {
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
				// @TODO: Handle the cleanup function in later issue with shutdown action or method that returns cleanup functions.
				static::$cleanup = $runner->prepare();
				static::$runner  = $runner;
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
		if ( isset( static::$runner ) ) {
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
		call_user_func( self::$cleanup );
		static::$runner = null;
	}
}
