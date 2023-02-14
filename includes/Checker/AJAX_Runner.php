<?php
/**
 * Class WordPress\Plugin_Check\Checker\AJAX_Runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

/**
 * AJAX Runner class.
 *
 * @since n.e.x.t
 */
class AJAX_Runner extends Abstract_Check_Runner {

	/**
	 * An instances of the Checks class.
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
	 * @return bool
	 */
	public function is_plugin_check() {
		if ( 0 !== strpos( $_SERVER['REQUEST_URI'], '/admin-ajax.php' ) ) {
			return false;
		}

		if ( ! isset( $_REQUEST['action'] ) || 'plugin_check_run_checks' !== $_REQUEST['action'] ) {
			return false;
		}

		if ( ! check_ajax_referer( 'plugin_check_run_checks' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Retruns an instance of the Checks class.
	 *
	 * @since n.e.x.t
	 *
	 * @return Checks
	 *
	 * @throws Exception Thrown if the plugin main file cannot be found based on the AJAX input.
	 */
	protected function get_checks_instance() {
		if ( ! isset( $this->checks ) ) {
			// Get the plugin name from the AJAX request.
			$plugin_slug = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
			$plugin_file = Plugin_Request_Utility::get_plugin_basename_from_input( $plugin_slug );

			$this->checks = new Checks( WP_PLUGIN_DIR . '/' . $plugin_file );
		}

		return $this->checks;
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

		if ( isset( $_REQUEST['checks'] ) ) {
			// Checks are passed as a comma separated string.
			$checks = explode( ',', $_REQUEST['checks'] );
		}

		return $checks;
	}
}
