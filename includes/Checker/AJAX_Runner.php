<?php
/**
 * Class WordPress\Plugin_Check\Checker\AJAX_Runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

/**
 * AJAX Runner class.
 *
 * @since n.e.x.t
 */
class AJAX_Runner extends Abstract_Check_Runner {

	/**
	 * An instance of the Checks class.
	 *
	 * @since n.e.x.t
	 * @var Checks
	 */
	protected $checks;

	/**
	 * Checks if the current request is an AJAX request for the Plugin Checker.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool Returns true if is an AJAX request for the plugin check else false.
	 */
	public function is_plugin_check() {
		if ( ! wp_doing_ajax() ) {
			return false;
		}

		if ( ! isset( $_REQUEST['action'] ) || 'plugin_check_run_checks' !== $_REQUEST['action'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Creates and returns an instance of the Checks class based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return Checks An instance of the Checks class.
	 *
	 * @throws Exception Thrown if the plugin main file cannot be found based on the AJAX input.
	 */
	protected function get_checks_instance() {
		if ( ! isset( $this->checks ) ) {
			if ( ! isset( $_REQUEST['plugin'] ) ) {
				throw new Exception( 'Invalid plugin slug: Plugin slug must not be empty.' );
			}

			// Get the plugin name from the AJAX request.
			$plugin_file = Plugin_Request_Utility::get_plugin_basename_from_input( $_REQUEST['plugin'] );

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

		if ( isset( $_REQUEST['checks'] ) ) {
			// Checks are passed as a comma separated string.
			$checks = wp_parse_list( $_REQUEST['checks'] );
		}

		return $checks;
	}
}
