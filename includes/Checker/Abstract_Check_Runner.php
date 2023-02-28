<?php
/**
 * Class WordPress\Plugin_Check\Checker\Abstract_Check_runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use WordPress\Plugin_Check\Checker\Preparations\Universal_Runtime_Preparation;

/**
 * Abstract Check Runner class.
 *
 * @since n.e.x.t
 */
abstract class Abstract_Check_Runner implements Check_Runner {

	/**
	 * True if the class was initialized early in the WordPress load process.
	 *
	 * @since n.e.x.t
	 * @var bool
	 */
	protected $initialized_early;

	/**
	 * The check slugs to run.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	protected $check_slugs;

	/**
	 * The plugin basename to check.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	protected $plugin_basename;

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
	 * @return Checks An instance of the Checks class.
	 */
	abstract protected function get_checks_instance();

	/**
	 * Returns an array of Check slugs to run based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check slugs.
	 */
	abstract protected function get_check_slugs_to_run();

	/**
	 * Sets whether the runner class was initialized early.
	 *
	 * @since n.e.x.t
	 */
	public function __construct() {
		$this->initialized_early = ! did_action( 'muplugins_loaded' );
	}

	/**
	 * Sets the check slugs to be run.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $check_slugs An array of check slugs to be run.
	 */
	public function set_check_slugs_to_run( array $check_slugs ) {
		$this->check_slugs = $check_slugs;
	}

	/**
	 * Sets the plugin basename to be checked.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $plugin_basename The plugin basename to be checked.
	 */
	public function set_plugin_basename( $plugin_basename ) {
		$this->plugin_basename = $plugin_basename;
	}

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
		if ( $this->requires_universal_preparations( $this->get_checks_to_run() ) ) {
			$preparation = new Universal_Runtime_Preparation( $this->get_checks_instance()->context() );
			$cleanup     = $preparation->prepare();

			// Set the database prefix to use the demo tables.
			global $wpdb;
			$old_prefix = $wpdb->set_prefix( 'wppc_' );

			return function() use ( $old_prefix, $cleanup ) {
				global $wpdb;
				$wpdb->set_prefix( $old_prefix );
				$cleanup();
			};
		}

		return function() {};
	}

	/**
	 * Runs the checks against the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @return Check_Result An object containing all check results.
	 */
	public function run() {
		$checks       = $this->get_checks_to_run();
		$preparations = $this->get_shared_preparations( $checks );
		$cleanups     = array();

		foreach ( $preparations as $preparation ) {
			$instance   = new $preparation['class']( ...$preparation['args'] );
			$cleanups[] = $instance->prepare();
		}

		$results = $this->get_checks_instance()->run_checks( $checks );

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
	 * @return array An array of Preparations to run where each item is an array with keys `class` and `args`.
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

		return array_values( $shared_preparations );
	}

	/**
	 * Returns the Check instances to run.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array map of check slugs to Check instances.
	 */
	protected function get_checks_to_run() {
		$check_slugs = $this->get_check_slugs_to_run();
		$all_checks  = $this->get_checks_instance()->get_checks();

		if ( empty( $check_slugs ) ) {
			return $all_checks;
		}

		return array_intersect_key( $all_checks, array_flip( $check_slugs ) );
	}
}
