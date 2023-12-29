<?php
/**
 * Class WordPress\Plugin_Check\Checker\CLI_Runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;

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
	public static function is_plugin_check() {
		if ( empty( $_SERVER['argv'] ) || 3 > count( $_SERVER['argv'] ) ) {
			return false;
		}

		if (
			'wp' === substr( $_SERVER['argv'][0], -2 ) &&
			'plugin' === $_SERVER['argv'][1] &&
			'check' === $_SERVER['argv'][2]
		) {
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
		// Exclude first three reserved elements.
		$params = array_slice( $_SERVER['argv'], 3 );

		// Remove associative arguments.
		$params = array_filter(
			$params,
			function ( $val ) {
				return ! str_starts_with( $val, '--' );
			}
		);

		// Use only first element. We dont support checking multiple plugins at once yet!
		$plugin = count( $params ) > 0 ? reset( $params ) : '';

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
				$checks = wp_parse_list( str_replace( '--checks=', '', $value ) );
				break;
			}
		}

		return $checks;
	}

	/**
	 * Returns an array of Check slugs to exclude based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check slugs to run.
	 */
	protected function get_check_exclude_slugs_param() {
		$checks = array();

		foreach ( $_SERVER['argv'] as $value ) {
			if ( false !== strpos( $value, '--exclude-checks=' ) ) {
				$checks = wp_parse_list( str_replace( '--exclude-checks=', '', $value ) );
				break;
			}
		}

		return $checks;
	}

	/**
	 * Returns the include experimental parameter based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool Returns true to include experimental checks else false.
	 */
	protected function get_include_experimental_param() {
		if ( in_array( '--include-experimental', $_SERVER['argv'], true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns an array of categories for filtering the checks.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of categories.
	 */
	protected function get_categories_param() {
		$categories = array();

		foreach ( $_SERVER['argv'] as $value ) {
			if ( false !== strpos( $value, '--categories=' ) ) {
				$categories = wp_parse_list( str_replace( '--categories=', '', $value ) );
				break;
			}
		}

		return $categories;
	}
}
