<?php
/**
 * Tests for the Checks class.
 *
 * @package plugin-check
 */

use Exception;
use WordPress\Plugin_Check\Checker\Checks;

class Checks_Tests extends WP_UnitTestCase {

	protected $checks;

	public function set_up() {
		parent::set_up();

		$this->checks = new Checks( 'test-plugin/test-plugin.php' );
	}

	public function test_prepare_returns_callable() {
		$cleanup = $this->checks->prepare();

		$this->assertIsCallable( $cleanup );
	}

	public function test_get_checks_returns_array() {
		$checks = $this->checks->get_checks();

		$this->assertIsArray( $checks );
	}

	public function test_run_all_checks_throws_exception_if_not_prepared() {
		$this->expectException(Exception::class);

		$this->checks->run_all_checks();
	}

	public function test_run_single_check_throws_exception_if_not_prepared() {
		$this->expectException(Exception::class);

		$this->checks->run_single_check( 'check' );
	}
}
