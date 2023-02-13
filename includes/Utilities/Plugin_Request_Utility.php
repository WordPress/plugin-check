<?php
/**
 * Class WordPress\Plugin_Check\Utilities
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

use Exception;

/**
 * Class providing utility methods to return plugin information based on the request.
 *
 * @since n.e.x.t
 */
class Plugin_Request_Utility {

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
			throw new Exception( 'Missing positional argument. Please provide the plugin slug as first positional argument.' );
		}

		$plugins = get_plugins();

		// Is the provided value is a full plugin basename?
		if ( isset( $plugins[ $plugin_slug ] ) ) {
			return $plugin_slug;
		}

		if ( strpos( $plugin_slug, '/' ) ) {
			throw new Exception(
				sprintf(
					'Invalid positional argument. Plugin with basename %s is not installed.',
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
				'Invalid positional argument. Plugin with slug %s is not installed.',
				$plugin_slug
			)
		);
	}
}
