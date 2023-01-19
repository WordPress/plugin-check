<?php
/**
 * Tests for the Checks class.
 *
 * @package plugin-check
 */

use Exception;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks;

class Checks_Tests extends WP_UnitTestCase {

	protected $checks;

	public function set_up() {
		parent::set_up();

		$this->checks = new Checks( 'test-plugin/test-plugin.php' );
	}

	public function test_get_checks_returns_array_of_expected_checks() {
		$expected = array(
			new WordPress\Plugin_Check\Tests\Empty_Check(),
			new WordPress\Plugin_Check\Tests\Error_Check(),
		);

		add_filter(
			'wp_plugin_check_checks',
			function( $checks ) use ( $expected ) {
				return $expected;
			}
		);

		$checks = $this->checks->get_checks();

		$this->assertIsArray( $checks );
		$this->assertSame( $expected, $checks );
	}

	public function test_run_checks() {
		$expected = array(
			new WordPress\Plugin_Check\Tests\Empty_Check(),
		);

		add_filter(
			'wp_plugin_check_checks',
			function( $checks ) use ( $expected ) {
				return $expected;
			}
		);

		$results = $this->checks->run_checks( array( 'Example_Check' ) );

		$this->assertInstanceOf( Check_Result::class, $results );
		$this->assertEmpty( $results->get_warnings() );
		$this->assertEmpty( $results->get_errors() );
	}

	public function test_run_checks_with_error() {
		$expected = array(
			new WordPress\Plugin_Check\Tests\Error_Check(),
		);

		add_filter(
			'wp_plugin_check_checks',
			function( $checks ) use ( $expected ) {
				return $expected;
			}
		);

		$results = $this->checks->run_checks( array( 'Error_Check' ) );

		$this->assertEmpty( $results->get_warnings() );
		$this->assertNotEmpty( $results->get_errors() );
	}
}
