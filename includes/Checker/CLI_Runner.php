<?php
/**
 * Class WordPress\Plugin_Check\Checker\CLI_Runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;
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
		if ( Plugin_Request_Utility::is_plugin_check() ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the plugin parameter based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The plugin parameter.
	 *
	 * @throws Exception Thrown if the plugin parameter is empty.
	 */
	protected function get_plugin_param() {
		// Get the plugin parameter from the command line arguments.
		$plugin = isset( $_SERVER['argv'][3] ) ? $_SERVER['argv'][3] : '';

		if ( empty( $plugin ) ) {
			throw new Exception(
				__( 'Invalid plugin: Plugin parameter must not be empty.', 'plugin-check' )
			);
		}

		return $plugin;
	}

	/**
	 * Returns an array of Check slugs to run based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check slugs to run.
	 */
	protected function get_check_slugs_param() {
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
