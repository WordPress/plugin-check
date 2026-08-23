<?php
/**
 * Test-harness AJAX_Runner subclass used by Abstract_Check_Runner_Tests.
 *
 * Stubs the production dependencies that Abstract_Check_Runner::run()
 * touches (checks execution and shared preparations) so the real `run()`
 * can be exercised against test-controlled inputs.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Concrete AJAX_Runner that stubs `get_checks_instance()` and
 * `get_shared_preparations()` so we can drive Abstract_Check_Runner::run()
 * against a synthetic check and a synthetic cleanup cascade.
 */
class Abstract_Check_Runner_Tests_Runner extends AJAX_Runner {

	/** @var callable|null Stub for checks execution. May throw. */
	public $do_run_callback;

	/** @var array Cleanup closures whose execution is asserted in tests. */
	public $recorded_cleanups = array();

	protected function get_plugin_param() {
		return 'plugin-check';
	}

	protected function get_check_slugs_param() {
		return array( 'empty-check' );
	}

	protected function get_check_exclude_slugs_param() {
		return array();
	}

	protected function get_include_experimental_param() {
		return false;
	}

	protected function get_categories_param() {
		return array();
	}

	protected function get_slug_param() {
		return '';
	}

	protected function get_mode_param() {
		return 'new';
	}

	/**
	 * Stub checks execution.
	 *
	 * Returns a plain object (not a Checks subclass, which is final) exposing
	 * `run_checks()`. The production `run()` only calls this method, so the
	 * returned value does not need to satisfy any interface.
	 *
	 * @return object
	 */
	protected function get_checks_instance() {
		$runner = $this;

		return new class( $runner ) {

			/** @var Abstract_Check_Runner_Tests_Runner */
			private $runner;

			public function __construct( $runner ) {
				$this->runner = $runner;
			}

			public function run_checks( $context, $checks, $check_runner ) {
				if ( $this->runner->do_run_callback ) {
					return ( $this->runner->do_run_callback )( $context );
				}

				// Production run() calls set_ai_analysis()/set_ai_stats() on
				// the return value, so it must be a real Check_Result and not
				// the raw Check_Context.
				return new Check_Result( $context );
			}
		};
	}

	protected function get_shared_preparations( array $checks ) {
		return array(
			array(
				'class' => Abstract_Check_Runner_Tests_Preparation::class,
				'args'  => array( $this->recorded_cleanups ),
			),
		);
	}
}
