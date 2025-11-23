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
 * @since 1.0.0
 */
class CLI_Runner extends Abstract_Check_Runner {

	/**
	 * An instance of the Checks class.
	 *
	 * @since 1.0.0
	 * @var Checks
	 */
	protected $checks;

	/**
	 * Checks if the current request is a CLI request for the Plugin Checker.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Returns true if is an CLI request for the plugin check else false.
	 */
	public static function is_plugin_check() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return false;
		}

		if ( empty( $_SERVER['argv'] ) || 3 > count( $_SERVER['argv'] ) ) {
			return false;
		}

		if (
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
	 * @since 1.0.0
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
			static function ( $val ) {
				return ! str_starts_with( $val, '--' );
			}
		);

		// Use only first element. We don't support checking multiple plugins at once yet!
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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

	/**
	 * Returns plugin slug parameter.
	 *
	 * @since 1.2.0
	 *
	 * @return string Plugin slug parameter.
	 */
	protected function get_slug_param() {
		$slug = '';

		foreach ( $_SERVER['argv'] as $value ) {
			if ( false !== strpos( $value, '--slug=' ) ) {
				$slug = str_replace( '--slug=', '', $value );
				break;
			}
		}

		return $slug;
	}

	/**
	 * Returns the mode parameter.
	 *
	 * @since 1.7.0
	 *
	 * @return string The mode parameter.
	 */
	protected function get_mode_param() {
		$mode = 'new';

		foreach ( $_SERVER['argv'] as $value ) {
			if ( false !== strpos( $value, '--mode=' ) ) {
				$mode = str_replace( '--mode=', '', $value );
				break;
			}
		}

		// Validate mode parameter.
		if ( ! in_array( $mode, array( 'new', 'update' ), true ) ) {
			$mode = 'new';
		}

		return $mode;
	}

	/**
	 * Initializes the runtime environment so that runtime checks can be run against a separate set of database tables.
	 *
	 * @since 1.3.0
	 *
	 * @return callable[] Array of cleanup functions to run after the process has completed.
	 */
	protected function initialize_runtime(): array {
		/*
		 * Since for WP-CLI all checks are run in a single process, we should set up the runtime environment (i.e.
		 * install the separate database tables) as part of this step.
		 * This way it runs before the regular runtime preparations, just like it does for the AJAX based flow, where
		 * they are invoked in a separate request prior to the requests performing actual checks.
		 */
		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		$cleanup_functions   = parent::initialize_runtime();
		$cleanup_functions[] = function () use ( $runtime_setup ) {
			$runtime_setup->clean_up();
		};

		return $cleanup_functions;
	}

	/**
	 * Checks whether the current environment allows for runtime checks to be used.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if runtime checks are allowed, false otherwise.
	 */
	protected function allow_runtime_checks(): bool {
		/*
		 * For WP-CLI, everything happens in one request. So if the runner was not initialized early, we won't be
		 * able to set that up, since the object-cache.php drop-in would only become effective in subsequent requests.
		 */
		if ( ! $this->initialized_early ) {
			return false;
		}

		return parent::allow_runtime_checks();
	}
}
