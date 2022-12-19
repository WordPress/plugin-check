<?php
/**
 * Tests for the Plugin_Main class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Plugin_Main;

class Plugin_Main_Tests extends WP_UnitTestCase {
	/**
	 * Test class can be instantiated.
	 *
	 * @test
	 */
	public function it_can_be_instantiated() {
		$main = new Plugin_Main( __DIR__ . '/../plugin-check.php' );

		$this->assertIsObject( $main );
		$this->assertInstanceOf( Plugin_Main::class, $main );
	}
}
