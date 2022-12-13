<?php
/**
 * Basic test to ensure PHPUnit is working.
 *
 * @package plugin-check
 */

class Basic_Tests extends WP_UnitTestCase {
	/**
	 * Basic test that asserts true.
	 *
	 * @test
	 */
	public function it_should_assert_true() {
		$this->assertTrue( true );
	}
}
