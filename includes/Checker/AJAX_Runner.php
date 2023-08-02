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
	public static function is_plugin_check() {
		if ( ! wp_doing_ajax() ) {
			return false;
		}

		// PHPCS ignore reason: Nonce check is already happening before this logic in `Admin_AJAX` class.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['action'] ) || 'plugin_check_run_checks' !== $_REQUEST['action'] ) {
			return false;
		}

		return true;
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
		// PHPCS ignore reason: Nonce check is already happening before this logic in `Admin_AJAX` class.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['plugin'] ) ) {
			throw new Exception(
				__( 'Invalid plugin: Plugin parameter must not be empty.', 'plugin-check' )
			);
		}

		// PHPCS ignore reason: Nonce check is already happening before this logic in `Admin_AJAX` class.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_text_field( $_REQUEST['plugin'] );
	}

	/**
	 * Returns an array of Check slugs to run based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check slugs to run.
	 */
	protected function get_check_slugs_param() {
		$checks = filter_input( INPUT_POST, 'checks', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$checks = is_null( $checks ) ? array() : $checks;

		return $checks;
	}

	/**
	 * Returns the include experimental paramater based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool Returns true to include experimental checks else false.
	 */
	protected function get_include_experimental_param() {
		return false;
	}

	/**
	 * Returns an array of categories for filtering the checks.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of categories for filtering the checks.
	 */
	protected function get_categories_param() {
		$categories = filter_input( INPUT_POST, 'categories', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$categories = is_null( $categories ) ? array() : $categories;

		return $categories;
	}
}
