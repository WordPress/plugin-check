<?php
/**
 * Class WordPress\Plugin_Check\Checker\Preparations\Universal_Runtime_Preparation
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Preparations;

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Preparation;
use Exception;
use function PHPUnit\Framework\callback;

/**
 * Class handle all preparations required for when at least one `Runtime_Check` is being run.
 *
 * @since n.e.x.t
 */
class Universal_Runtime_Preparation implements Preparation {

	/**
	 * Context for the plugin to check.
	 *
	 * @since n.e.x.t
	 * @var Check_Context
	 */
	protected $check_context;

	/**
	 * Sets the context for the plugin to check.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Context $check_context Check context instance for the plugin.
	 */
	public function __construct( Check_Context $check_context ) {
		$this->check_context = $check_context;
	}

	/**
	 * Runs the preparations for the theme and plugin. And returns a cleanup function.
	 *
	 * @since n.e.x.t
	 *
	 * @return callable Cleanup function to revert changes made by theme and plugin preparation classes.
	 *
	 * @throws Exception Thrown when preparation fails.
	 */
	public function prepare() {

		$cleanup_functions = array();

		$use_minimal_theme_preparation = new Use_Minimal_Theme_Preparation( 'wp-empty-theme', WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . '/test-content/themes' );
		$cleanup_functions[]           = $use_minimal_theme_preparation->prepare();

		$force_single_plugin_preparation = new Force_Single_Plugin_Preparation( 'plugin-check/plugin-check.php' );
		$cleanup_functions[]             = $force_single_plugin_preparation->prepare();

		// Return the cleanup function.
		return function () use ( $cleanup_functions ) {

			foreach ( $cleanup_functions as $cleanup_function ) {

				callback( $cleanup_function );
			}
		};
	}
}
