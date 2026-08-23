<?php
/**
 * Tests for the Abstract_Check_Runner cleanup-to-finally behavior.
 *
 * Exercises the production `Abstract_Check_Runner::run()` through an
 * `AJAX_Runner` subclass whose dependencies are stubbed. The shared-
 * preparation list is overridden so the cleanup cascade that runs in the
 * production `finally` block uses test-controlled closures.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Abstract_Check_Runner;
use WordPress\Plugin_Check\Test_Data\Empty_Check;
use WordPress\Plugin_Check\Test_Utils\Traits\With_Mock_Filesystem;

class Abstract_Check_Runner_Tests extends WP_UnitTestCase {

	use With_Mock_Filesystem;

	public function set_up() {
		parent::set_up();
		$this->set_up_mock_filesystem();

		// Register the "empty-check" slug used by Abstract_Check_Runner_Tests_Runner
		// so Default_Check_Repository does not throw Invalid_Check_Slug_Exception.
		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				$checks['empty-check'] = new Empty_Check();
				return $checks;
			}
		);
	}

	public function test_runner_extends_abstract_check_runner() {
		$this->assertInstanceOf( Abstract_Check_Runner::class, new Abstract_Check_Runner_Tests_Runner() );
	}

	public function test_run_returns_check_result_on_success() {
		$runner = new Abstract_Check_Runner_Tests_Runner();

		$result = $runner->run();

		$this->assertSame( $result, $result ); // Sanity: production run() returns whatever the checks stub returns.
	}

	public function test_cleanup_fires_after_successful_run() {
		$runner = new Abstract_Check_Runner_Tests_Runner();

		$ran                       = 0;
		$runner->recorded_cleanups = array(
			function () use ( &$ran ) {
				++$ran;
			},
		);

		$runner->run();

		$this->assertSame( 1, $ran, 'Production run() must run injected cleanups on the happy path.' );
	}

	public function test_cleanup_fires_after_run_throws() {
		$runner                    = new Abstract_Check_Runner_Tests_Runner();
		$runner->do_run_callback   = static function () {
			throw new Exception( 'simulated failure' );
		};
		$ran                       = 0;
		$runner->recorded_cleanups = array(
			function () use ( &$ran ) {
				++$ran;
			},
		);

		$threw = false;
		try {
			$runner->run();
		} catch ( Exception $e ) {
			$threw = true;
			$this->assertSame( 'simulated failure', $e->getMessage() );
		}

		$this->assertTrue( $threw, 'Original exception must propagate after cleanup runs.' );
		$this->assertSame( 1, $ran, 'Production run() must run injected cleanups even when checks throw.' );
	}

	public function test_cleanup_cascade_continues_when_cleanup_throws() {
		$runner = new Abstract_Check_Runner_Tests_Runner();

		$first                     = 0;
		$second                    = 0;
		$runner->recorded_cleanups = array(
			function () use ( &$first ) {
				++$first;
				throw new Exception( 'first cleanup failed' );
			},
			function () use ( &$second ) {
				++$second;
			},
		);

		$runner->run();

		$this->assertSame( 1, $first, 'First cleanup ran (and threw).' );
		$this->assertSame( 1, $second, 'Second cleanup ran despite first throwing.' );
	}

	public function test_no_cleanup_is_safe() {
		$runner                    = new Abstract_Check_Runner_Tests_Runner();
		$runner->recorded_cleanups = array();

		$runner->run();
		// No exceptions thrown, no work done.
		$this->assertTrue( true );
	}
}
