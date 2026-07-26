<?php
/**
 * Test-helper preparation used by Abstract_Check_Runner_Tests.
 *
 * Stable class whose `prepare()` returns a closure that runs the test's
 * recorded cleanup callbacks. Acts as the class-args key inside
 * `get_shared_preparations()`.
 *
 * @package plugin-check
 */

/**
 * Stable preparation class whose `prepare()` delegates to the test's
 * recorded cleanup closures.
 */
class Abstract_Check_Runner_Tests_Preparation {

	/**
	 * Closures captured by reference from the runner.
	 *
	 * @var array
	 */
	public $cleanups;

	public function __construct( array &$cleanups ) {
		$this->cleanups = &$cleanups;
	}

	public function prepare() {
		$cleanups = $this->cleanups;

		return function () use ( $cleanups ) {
			foreach ( $cleanups as $cleanup ) {
				( $cleanup )();
			}
		};
	}
}
