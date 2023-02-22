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
	 * An instance of the Checks class.
	 *
	 * @since n.e.x.t
	 * @var Checks
	 */
	protected $checks;

	/**
	 * Checks if the current request is a CLI request for the Plugin Checker.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool Returns true if is an CLI request for the plugin check else false.
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
	 * Creates and returns an instance of the Checks class based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return Checks An instance of the Checks class.
	 *
	 * @throws Exception Thrown if the plugin main file cannot be found based on the CLI input.
	 */
	protected function get_checks_instance() {
		if ( ! isset( $this->checks ) ) {
			// Get the plugin name from the command line arguments.
			$plugin_slug = isset( $_SERVER['argv'][3] ) ? $_SERVER['argv'][3] : '';
			$plugin_file = Plugin_Request_Utility::get_plugin_basename_from_input( $plugin_slug );

			$this->checks = new Checks( WP_PLUGIN_DIR . '/' . $plugin_file );
		}

		return $this->checks;
	}

	/**
	 * Returns an array of Check slugs to run based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check slugs to run.
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
