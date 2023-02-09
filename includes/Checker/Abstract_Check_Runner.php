<?php
/**
 * Class WordPress\Plugin_Check\Checker\Abstract_Check_runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Preparations\Universal_Runtime_Preparation;

/**
 * Abstract Check Runner class.
 *
 * @since n.e.x.t
 */
abstract class Abstract_Check_Runner implements Check_Runner {

	/**
	 * Determines if the current request is intended for the plugin checker.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool Returns true if the check is for plugin else false.
	 */
	abstract public function is_plugin_check();

	/**
	 * Creates and returns an instance of the Checks class based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return Checks An instances of the Checks class.
	 */
	abstract protected function get_checks();

	/**
	 * Creates and returns the Check_Context for the plugin to check based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return Check_Context The Check_Context for the plugin to check.
	 */
	abstract protected function get_context();

	/**
	 * Creates and returns an array of Check instances to run based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check instances.
	 */
	abstract protected function get_checks_to_run();

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
		if ( ! $this->requires_universal_preparations( $this->get_checks_to_run() ) ) {
			$preparation = new Universal_Runtime_Preparation( $this->get_context() );
			return $preparation->prepare();
		}

		return function() {};
	}

	/**
	 * Runs the checks against the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @return Check_Result
	 */
	public function run() {
		$checks       = $this->get_checks_to_run();
		$preparations = $this->get_shared_preparations( $checks );
		$cleanups     = array();

		foreach ( $preparations as $preparation ) {
			$instance   = new $preparation['class']( ...$preparation['args'] );
			$cleanups[] = $instance->prepare();
		}

		$results = $this->get_checks()->run_checks( $checks );

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
	 * @return bool Returns true if one or more checks requires the universal runtime preparation.
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
