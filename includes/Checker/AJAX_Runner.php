<?php
/**
 * Class WordPress\Plugin_Check\Checker\AJAX_Runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;

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
	 * Returns the plugin basename based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The plugin basename.
	 *
	 * @throws Exception Thrown if the plugin basename is invalid.
	 */
	protected function determine_plugin_basename() {
		if ( ! isset( $_REQUEST['plugin'] ) ) {
			throw new Exception(
				__( 'Invalid plugin slug: Plugin slug must not be empty.', 'plugin-check' )
			);
		}

		return sanitize_text_field( $_REQUEST['plugin'] );
	}

	/**
	 * Returns an array of Check slugs to run based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check slugs to run.
	 */
	protected function determine_check_slugs_to_run() {
		$checks = array();

		if ( isset( $_REQUEST['checks'] ) ) {
			// Checks are passed as a comma separated string.
			$checks = wp_parse_list( $_REQUEST['checks'] );
		}

		return $checks;
	}
}
