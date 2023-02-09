<?php
/**
 * Class WordPress\Plugin_Check\Checker\Abstract_Check_runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use WordPress\Plugin_Check\Checker\Check_Runner;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Preparations\Universal_Runtime_Preparation;

/**
 * Abstract Check Runner class.
 *
 * @since n.e.x.t
 */
abstract class Abstract_Check_Runner implements Check_Runner {

	/**
	 * Instance of the Checks class.
	 *
	 * @since n.e.x.t
	 * @var Checks
	 */
	protected $checks;

	/**
	 * Instance of the Check_Context class.
	 *
	 * @since n.e.x.t
	 * @var Check_Context
	 */
	protected $context;

	/**
	 * Array of Check instances to run.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	protected $checks_to_run;

	/**
	 * Determines if the current request is intended for the plugin checker.
	 *
	 * @since n.e.x.t
	 *
	 * @return boolean Returns true if the check is for plugin else false.
	 */
	abstract public function is_plugin_check();

	/**
	 * Sets up the check context, checks and preparations based on the request.
	 *
	 * @since n.e.x.t
	 */
	abstract protected function setup_checks();

	/**
	 * Prepares the environment for running the requested checks.
	 *
	 * @since n.e.x.t
	 *
	 * @return callable Cleanup function to revert any changes made here.
	 *
	 * @throws Exception Thrown exception when preparation fails.
	 */
	public function prepare() {
		if ( ! $this->requires_universal_preparations( $this->checks_to_run ) ) {
			$preparation = new Universal_Runtime_Preparation( $this->context );
			return $preparation->prepare();
		}

		return null;
	}

	/**
	 * Runs the checks against the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @return Check_Result
	 */
	public function run() {
		$checks = $this->checks->get_checks( $this->checks_to_run );

		$preparations = $this->get_shared_preparations( $checks );
		$cleanups     = array();

		foreach ( $preparations as $preparation ) {
			$instance   = new $preparation['class']( ...$preparation['args'] );
			$cleanups[] = $instance->prepare();
		}

		$results = $this->checks->run_checks( $checks );

		if ( ! empty( $cleanups ) ) {
			foreach ( $cleanups as $cleanup ) {
				$cleanup();
			}
		}

		return $results;
	}

	/**
	 * Determines if any of the checks requires the universal runtime preparation.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks An array of check instances to run.
	 *
	 * @return boolean Returns true if one or more checks requires the universal runtime preparation.
	 */
	protected function requires_universal_preparations( array $checks ) {
		foreach ( $checks as $check ) {
			if ( $check instanceof Runtime_Check ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns all shared preparations used by the checks to run.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks An array of Check instances to run.
	 *
	 * @return array An array of reparations to run.
	 */
	private function get_shared_preparations( array $checks ) {
		$shared_preparations = array();

		foreach ( $checks as $check ) {
			if ( ! $check instanceof With_Shared_Preparations ) {
				continue;
			}

			$preparations = $check->get_shared_preparations();

			foreach ( $preparations as $class => $args ) {
				$key = $class . '::' . md5( json_encode( $args ) );

				if ( ! isset( $shared_preparations[ $key ] ) ) {
					$shared_preparations[ $key ] = array(
						'class' => $class,
						'args'  => $args,
					);
				}
			}
		}

		return $shared_preparations;
	}
}
