<?php
/**
 * Tests for the Default_Check_Repository class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Repository;
use WordPress\Plugin_Check\Checker\Default_Check_Repository;
use WordPress\Plugin_Check\Test_Data\Empty_Check;
use WordPress\Plugin_Check\Test_Data\Runtime_Check;
use WordPress\Plugin_Check\Test_Data\Static_Check;

class Default_Check_Repository_Tests extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		$this->repository = new Default_Check_Repository();
	}

	public function test_register_static_check() {
		$check = new Static_Check();
		$this->repository->register_check( 'static_check', $check );

		$this->assertSame( array( $check ), $this->repository->get_checks() );
	}

	public function test_register_runtime_check() {
		$check = new Runtime_Check();
		$this->repository->register_check( 'runtime_check', $check );

		$this->assertSame( array( $check ), $this->repository->get_checks() );
	}

	public function test_register_exception_thrown_for_invalid_check() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Check must be an instance of Runtime_Check or Static_Check.' );

		$this->repository->register_check( 'empty_check', new Empty_Check() );
	}

	public function test_register_exception_thrown_for_existing_check_slug() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Check slug "static_check" is already in use.' );

		$this->repository->register_check( 'static_check', new Static_Check() );
		$this->repository->register_check( 'static_check', new Static_Check() );
	}

	public function test_get_checks_returns_all_checks() {
		$static_check  = new Static_Check();
		$runtime_check = new Runtime_Check();

		$this->repository->register_check( 'static_check', $static_check );
		$this->repository->register_check( 'runtime_check', $runtime_check );

		$this->assertSame( array( $static_check, $runtime_check ), $this->repository->get_checks() );
	}

	public function test_get_checks_returns_static_checks_via_flag() {
		$static_check  = new Static_Check();
		$runtime_check = new Runtime_Check();

		$this->repository->register_check( 'static_check', $static_check );
		$this->repository->register_check( 'runtime_check', $runtime_check );

		$this->assertSame( array( $static_check ), $this->repository->get_checks( Check_Repository::TYPE_STATIC ) );
	}

	public function test_get_checks_returns_runtime_checks_via_flag() {
		$static_check  = new Static_Check();
		$runtime_check = new Runtime_Check();

		$this->repository->register_check( 'static_check', $static_check );
		$this->repository->register_check( 'runtime_check', $runtime_check );

		$this->assertSame( array( $runtime_check ), $this->repository->get_checks( Check_Repository::TYPE_RUNTIME ) );
	}

	public function test_get_checks_returns_checks_via_slug() {
		$static_check  = new Static_Check();
		$runtime_check = new Runtime_Check();

		$this->repository->register_check( 'static_check', $static_check );
		$this->repository->register_check( 'runtime_check', $runtime_check );

		$this->assertSame( array( $static_check ), $this->repository->get_checks( Check_Repository::TYPE_ALL, array( 'static_check' ) ) );
	}

	public function test_get_checks_throws_exception_for_invalid_flag() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Invalid check flags passed.' );

		$this->repository->get_checks( 5 );
	}

	public function test_get_checks_throws_exception_for_invalid_check_slug() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Check with the slug "invalid_check" does not exist.' );

		$this->repository->get_checks( Check_Repository::TYPE_ALL, array( 'invalid_check' ) );
	}
}
