<?php
/**
 * Abstract Runtime_Check_UnitTestCase.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Test_Utils\TestCase;

use WP_UnitTestCase;
use WordPress\Plugin_Check\Checker\Check;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Checker\Preparation;
use WordPress\Plugin_Check\Checker\With_Shared_Preparations;
use WordPress\Plugin_Check\Checker\Preparations\Universal_Runtime_Preparation;

abstract class Runtime_Check_UnitTestCase extends WP_UnitTestCase {
	/**
	 * Gets the Check_Context for the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $plugin_file The absolute path to the plugin main file.
	 * @return Check_Context The check context for the plugin file.
	 */
	protected function get_context( $plugin_file ) {
		return new Check_Context( $plugin_file );
	}

	/**
	 * Prepares the test environment by running all preparations.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check         $check   The check to prepare the environment for.
	 * @param Check_Context $context The check context to be checked.
	 */
	protected function prepare_environment( Check $check, Check_Context $context ) {
		$cleanups = array();

		// Prepare the Universal Runtime preparation.
		if ( $check instanceof Runtime_Check ) {
			$cleanups[] = ( new Universal_Runtime_Preparation( $context ) )->prepare();
		}

		// Prepare any shared preparations for the check.
		if ( $check instanceof With_Shared_Preparations ) {
			foreach ( $check->get_shared_preparations() as $class => $args ) {
				$cleanups[] = ( new $class( ...$args ) )->prepare();
			}
		}

		// Prepare the check.
		if ( $check instanceof Preparation ) {
			$cleanups[] = $check->prepare();
		}

		// Return the cleanup function.
		return function() use ( $cleanups ) {
			foreach ( $cleanups as $cleanup ) {
				$cleanup();
			}
		};
	}

	/**
	 * Prepares the test environment and runs the check returning the results.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check         $check   The Check instance to run.
	 * @param Check_Context $context The check context for the plugin to be checked.
	 * @return Check_Result An object containing all check results.
	 */
	protected function run_check( Check $check, Check_Context $context ) {
		$results = new Check_Result( $context );
		$cleanup = $this->prepare_environment( $check, $context );

		try {
			$check->run( $results );
		} catch ( \Exception $e ) {
			$cleanup();
			throw $e;
		}
		$cleanup();

		return $results;
	}
}
