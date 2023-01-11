<?php
/**
 * Tests for the Check_Context class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Preparation;
use WordPress\Plugin_Check\Checker\Preparations\Use_Minimal_Theme_Preparation;

class Use_Minimal_Theme_Preparation_Tests extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();

		$this->theme_slug = 'wp-empty-theme';
		$this->theme_dir  = WP_PLUGIN_DIR . '/' . basename( TESTS_PLUGIN_DIR ) . '/test-content/themes';

		$this->preparation = new Use_Minimal_Theme_Preparation( $this->theme_slug, $this->theme_dir );

		$this->cleanup = $this->preparation->prepare();
	}

	public function tear_down() {
		parent::tear_down();

		// Run the cleanup function.
		( $this->cleanup )();
	}

	public function test_implements_preparation_interface() {
		$this->assertInstanceOf( Preparation::class, $this->preparation );
	}

	public function test_get_theme_slug() {
		$this->assertSame( $this->theme_slug, $this->preparation->get_theme_slug() );
	}

	public function test_get_theme_name() {
		$this->assertSame( 'WP Empty Theme', $this->preparation->get_theme_name() );
	}

	public function test_get_theme_root() {
		$this->assertSame( str_replace( WP_CONTENT_DIR, '', $this->theme_dir ), $this->preparation->get_theme_root() );
	}
}
