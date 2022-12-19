<?php
/**
 * Tests for the Plugin_Context class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Plugin_Context;

class Plugin_Context_Tests extends WP_UnitTestCase {

	/**
	 * Create a single Plugin_Context to test.
	 */
	public function set_up() {
		parent::set_up();

		$this->plugin_context = new Plugin_Context( 'plugin-check/plugin-check.php' );
	}

	/**
	 * Test class can be instantiated.
	 *
	 * @test
	 */
	public function it_can_be_instantiated() {
		$main = new Plugin_Context( 'plugin-check/plugin-check.php' );

		$this->assertIsObject( $main );
		$this->assertInstanceOf( Plugin_Context::class, $main );
	}

	/**
	 * Test basename method.
	 *
	 * @test
	 */
	public function it_can_return_the_basename() {
		$basename = $this->plugin_context->basename();

		$this->assertSame( $basename, 'plugin-check/plugin-check.php' );
	}

	/**
	 * Test path method.
	 *
	 * @test
	 */
	public function it_can_return_the_path() {
		$basename = $this->plugin_context->path();

		$this->assertSame( $basename, 'plugin-check/' );
	}

	/**
	 * Test path method with parameter.
	 *
	 * @test
	 */
	public function it_can_return_the_path_with_parameter() {
		$basename = $this->plugin_context->path( '/another/folder' );

		$this->assertSame( $basename, 'plugin-check/another/folder' );
	}

	/**
	 * Test URL method.
	 *
	 * @test
	 */
	public function it_can_return_the_url() {
		$basename = $this->plugin_context->path();

		$this->assertSame( $basename, 'plugin-check/' );
	}

	/**
	 * Test URL method.
	 *
	 * @test
	 */
	public function it_can_return_the_url_with_parameter() {
		$basename = $this->plugin_context->path( '/folder/file.css' );

		$this->assertSame( $basename, 'plugin-check/folder/file.css' );
	}
}
