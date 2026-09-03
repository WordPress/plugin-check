<?php
/**
 * Tests for the Check_Context class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;

class Check_Context_Tests extends WP_UnitTestCase {
	/**
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * @var Check_Context
	 */
	protected $check_context;

	public function set_up() {
		parent::set_up();

		$this->plugin_name   = basename( TESTS_PLUGIN_DIR );
		$this->check_context = new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE );
	}

	public function test_basename() {
		$this->assertSame( plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE ), $this->check_context->basename() );
	}

	public function test_path() {
		$this->assertSame( WP_PLUGIN_DIR . '/' . $this->plugin_name . '/', $this->check_context->path() );
	}

	public function test_path_with_parameter() {
		$this->assertSame( WP_PLUGIN_DIR . '/' . $this->plugin_name . '/test-content/themes', $this->check_context->path( '/test-content/themes' ) );
	}

	public function test_path_with_nonexistent_relative_path() {
		$this->assertSame( WP_PLUGIN_DIR . '/' . $this->plugin_name . '/does-not-exist/file.php', $this->check_context->path( '/does-not-exist/file.php' ) );
	}

	public function test_path_resolves_symlinked_plugin_directory() {
		$target  = WP_PLUGIN_DIR . '/' . $this->plugin_name;
		$symlink = WP_PLUGIN_DIR . '/check-context-symlink-test';

		if ( file_exists( $symlink ) || ! symlink( $target, $symlink ) ) {
			$this->markTestSkipped( 'Could not create symlink fixture.' );
		}

		try {
			$context = new Check_Context( $symlink . '/' . basename( WP_PLUGIN_CHECK_MAIN_FILE ) );

			// The path must resolve to the real (non-symlinked) plugin directory,
			// matching how PHPCS reports resolved (realpath'd) file paths.
			$this->assertSame( realpath( $target ) . '/', $context->path() );
		} finally {
			unlink( $symlink );
		}
	}

	public function test_url() {
		$this->assertSame( WP_PLUGIN_URL . '/' . $this->plugin_name . '/', $this->check_context->url() );
	}

	public function test_url_with_parameter() {
		$this->assertSame( WP_PLUGIN_URL . '/' . $this->plugin_name . '/assets/js/plugin-check-admin.js', $this->check_context->url( '/assets/js/plugin-check-admin.js' ) );
	}
}
