<?php
/**
 * Class WordPress\Plugin_Check\Checker\CLI_Runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

/**
 * CLI Runner class.
 *
 * @since n.e.x.t
 */
class CLI_Runner extends Abstract_Check_Runner {

	/**
	 * Checks if the current request is a CLI request for the Plugin Checker.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool
	 */
	public function is_plugin_check() {
		if ( empty( $_SERVER['argv'] ) || 3 > count( $_SERVER['argv'] ) ) {
			return false;
		}

		if (
			'wp' === $_SERVER['argv'][0] &&
			'plugin' === $_SERVER['argv'][1] &&
			'check' === $_SERVER['argv'][2]
		) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the plugin main file based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The absolute path to the plugin main file.
	 *
	 * @throws Exception Thrown if an invalid basename or plugin slug is provided.
	 */
	private function get_plugin_main_file() {
		// Get the plugin name from the command line arguments.
		$plugin_slug = isset( $_SERVER['argv'][3] ) ? $_SERVER['argv'][3] : '';
		$plugin_file = Plugin_Request_Utility::get_plugin_basename_from_input( $plugin_slug );

		return WP_PLUGIN_DIR . '/' . $plugin_file;
	}

	/**
	 * Retruns an instance of the Checks class.
	 *
	 * @since n.e.x.t
	 *
	 * @return Checks
	 *
	 * @throws Exception Thrown if the plugin main file cannot be found based on the CLI input.
	 */
	protected function get_checks_instance() {
		return new Checks( $this->get_plugin_main_file() );
	}

	/**
	 * Returns an array of Check instances to run.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check instances to run.
	 */
	protected function get_check_slugs_to_run() {
		$checks = array();

		foreach ( $_SERVER['argv'] as $value ) {
			if ( false !== strpos( $value, '--checks=' ) ) {
				$checks = explode( ',', str_replace( '--checks=', '', $value ) );
				break;
			}
		}

		return $checks;
	}
}
