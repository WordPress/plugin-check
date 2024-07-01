<?php
/**
 * Tests for the Default_Check_Collection class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Empty_Check_Repository;
use WordPress\Plugin_Check\Checker\Runtime_Check as Runtime_Check_Interface;
use WordPress\Plugin_Check\Test_Data\Runtime_Check;
use WordPress\Plugin_Check\Test_Data\Static_Check;

class Default_Check_Collection_Tests extends WP_UnitTestCase {

	private $checks;
	private $collection;

	public function set_up() {
		parent::set_up();

		$this->checks = array(
			'static_check'  => new Static_Check(),
			'runtime_check' => new Runtime_Check(),
		);

		$repository = new Empty_Check_Repository();
		foreach ( $this->checks as $slug => $check ) {
			$repository->register_check( $slug, $check );
		}

		$this->collection = $repository->get_checks();
	}

	public function test_to_array() {
		$this->assertSame(
			array_values( $this->checks ),
			$this->collection->to_array()
		);
	}

	public function test_to_map() {
		$this->assertSame(
			$this->checks,
			$this->collection->to_map()
		);
	}

	public function test_filter() {
		$this->assertSame(
			array( $this->checks['runtime_check'] ),
			$this->collection->filter(
				function ( $check ) {
					return $check instanceof Runtime_Check_Interface;
				}
			)->to_array()
		);
	}

	public function test_include() {
		$this->assertSame(
			array( $this->checks['static_check'] ),
			$this->collection->include( array( 'static_check' ) )->to_array()
		);
	}

	public function test_include_with_empty() {
		$this->assertSame(
			array_values( $this->checks ),
			$this->collection->include( array() )->to_array()
		);
	}

	public function test_include_with_invalid() {
		$this->assertSame(
			array( $this->checks['runtime_check'] ),
			$this->collection->include( array( 'runtime_check', 'invalid_check' ) )->to_array()
		);
	}

	public function test_require() {
		$this->assertSame(
			array_values( $this->checks ),
			$this->collection->require( array( 'static_check' ) )->to_array()
		);
	}

	public function test_require_with_invalid() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Check with the slug "invalid_check" does not exist.' );

		$this->collection->require( array( 'static_check', 'invalid_check' ) );
	}

	public function test_exclude() {
		$this->assertSame(
			array( $this->checks['runtime_check'] ),
			$this->collection->exclude( array( 'static_check' ) )->to_array()
		);
	}

	public function test_exclude_with_empty() {
		$this->assertSame(
			array_values( $this->checks ),
			$this->collection->exclude( array() )->to_array()
		);
	}

	public function test_exclude_with_invalid() {
		$this->assertSame(
			array( $this->checks['static_check'], $this->checks['runtime_check'] ),
			$this->collection->exclude( array( 'invalid_check' ) )->to_array()
		);
	}
}
