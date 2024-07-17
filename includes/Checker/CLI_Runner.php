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
		return true;
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
		return $this->plugin;
	}

	/**
	 * Returns an array of Check slugs to run based on the request.
	 *
	 * @since 1.0.0
	 *
	 * @return array An array of Check slugs to run.
	 */
	protected function get_check_slugs_param() {
		return $this->check_slugs ?? array();
	}

	/**
	 * Returns an array of Check slugs to exclude based on the request.
	 *
	 * @since 1.0.0
	 *
	 * @return array An array of Check slugs to run.
	 */
	protected function get_check_exclude_slugs_param() {
		return $this->check_exclude_slugs ?? array();
	}

	/**
	 * Returns the include experimental parameter based on the request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Returns true to include experimental checks else false.
	 */
	protected function get_include_experimental_param() {
		return (bool) $this->include_experimental;
	}

	/**
	 * Returns an array of categories for filtering the checks.
	 *
	 * @since 1.0.0
	 *
	 * @return array An array of categories.
	 */
	protected function get_categories_param() {
		return $this->check_categories ?? array();
	}
}
