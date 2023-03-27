<?php
/**
 * Class WordPress\Plugin_Check\Checker\Abstract_Check_runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
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
	 * The plugin parameter.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	protected $plugin;

	/**
	 * An instance of the Checks class.
	 *
	 * @since n.e.x.t
	 * @var Checks
	 */
	protected $checks;

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
	 * Returns the plugin parameter based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The plugin paramater from the request.
	 */
	abstract protected function get_plugin_param();

	/**
	 * Returns an array of Check slugs to run based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check slugs.
	 */
	abstract protected function get_check_slugs_param();

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
	 *
	 * @throws Exception Thrown if the checks do not match those in the original request.
	 */
	public function set_check_slugs( array $check_slugs ) {
		if ( $this->initialized_early ) {
			// Compare the check slugs to see if there was an error.
			if ( $check_slugs !== $this->get_check_slugs_param() ) {
				throw new Exception(
					__( 'Invalid checks: The checks to run do not match the original request.', 'plugin-check' )
				);
			}
		}

		$this->check_slugs = $check_slugs;
	}

	/**
	 * Sets the plugin slug or basename to be checked.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $plugin The plugin slug or basename to be checked.
	 *
	 * @throws Exception Thrown if the plugin set does not match the original request parameter.
	 */
	public function set_plugin( $plugin ) {
		if ( $this->initialized_early ) {
			// Compare the plugin parameter to see if there was an error.
			if ( $plugin !== $this->get_plugin_param() ) {
				throw new Exception(
					__( 'Invalid plugin: The plugin set does not match the original request parameter.', 'plugin-check' )
				);
			}
		}

		$this->plugin = $plugin;
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
		if ( $this->has_runtime_check( $this->get_checks_to_run() ) ) {
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
	 * Determines if any of the checks are a runtime check.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks An array of check instances to run.
	 * @return bool Returns true if one or more checks is a runtime check.
	 */
	protected function has_runtime_check( array $checks ) {
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
	 *
	 * @throws Exception Thrown exception when a runtime check is requested and the plugin inactive.
	 */
	public function get_checks_to_run() {
		$check_slugs   = $this->get_check_slugs();
		$all_checks    = $this->get_checks_instance()->get_checks();
		$plugin_active = is_plugin_active( $this->get_plugin_basename() );

		if ( ! empty( $check_slugs ) ) {
			// Get the check instances based on the requested checks.
			$checks_to_run = array_intersect_key( $all_checks, array_flip( $check_slugs ) );

			// Check the following conditions if at least one runtime check is requested.
			if ( $this->has_runtime_check( $checks_to_run ) ) {
				// Throw an error if the plugin is not active.
				if ( ! $plugin_active ) {
					throw new Exception( __( 'Runtime checks cannot be run against inactive plugins.', 'plugin-check' ) );
				}

				// Throw and error if the runner was not initialized early and the runtime environment was not set up.
				if ( ! $this->initialized_early ) {
					throw new Exception( __( 'Runtime checks cannot be run as the runtime environment was not set up.', 'plugin-check' ) );
				}
			}
		} else {
			// Run all checks for the plugin.
			$checks_to_run = $all_checks;

			// Only run static checks if the plugin is inactive or the runtime environment was not set up.
			if ( ! $plugin_active || ! $this->initialized_early ) {
				$checks_to_run = array_filter(
					$checks_to_run,
					function ( $check ) {
						return ! $check instanceof Runtime_Check;
					}
				);
			}
		}

		return $checks_to_run;
	}

	/**
	 * Creates and returns the Check instance.
	 *
	 * @since n.e.x.t
	 *
	 * @return Checks An instance of the Checks class.
	 *
	 * @throws Exception Thrown if the plugin slug is invalid.
	 */
	protected function get_checks_instance() {
		if ( isset( $this->checks ) ) {
			return $this->checks;
		}

		$plugin_basename = $this->get_plugin_basename();
		$this->checks    = new Checks( WP_PLUGIN_DIR . '/' . $plugin_basename );

		return $this->checks;
	}

	/**
	 * Returns the check slugs to run.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of check slugs to run.
	 */
	protected function get_check_slugs() {
		if ( isset( $this->check_slugs ) ) {
			return $this->check_slugs;
		}

		return $this->get_check_slugs_param();
	}

	/**
	 * Returns the plugin basename.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The plugin basename to check.
	 */
	public function get_plugin_basename() {
		if ( ! isset( $this->plugin_basename ) ) {
			$plugin                = isset( $this->plugin ) ? $this->plugin : $this->get_plugin_param();
			$this->plugin_basename = Plugin_Request_Utility::get_plugin_basename_from_input( $plugin );
		}

		return $this->plugin_basename;
	}
}
