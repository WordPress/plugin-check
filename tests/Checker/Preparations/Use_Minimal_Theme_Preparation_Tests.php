<?php
/**
 * Tests for the Check_Context class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Preparation;
use WordPress\Plugin_Check\Checker\Preparations\Use_Minimal_Theme_Preparation;

class Use_Minimal_Theme_Preparation_Tests extends WP_UnitTestCase {
	protected $theme_slug;
	protected $theme_dir;

	public function set_up() {
		parent::set_up();

		$this->theme_slug = 'wp-empty-theme';
		$this->theme_dir  = WP_PLUGIN_DIR . '/' . basename( TESTS_PLUGIN_DIR ) . '/test-content/themes';
	}

	public function test_get_theme_slug() {
		$preparation = new Use_Minimal_Theme_Preparation( $this->theme_slug, $this->theme_dir );

		$this->assertSame( $this->theme_slug, $preparation->get_theme_slug() );
	}

	public function test_prepare() {
		$preparation = new Use_Minimal_Theme_Preparation( $this->theme_slug, $this->theme_dir );
		$cleanup     = $preparation->prepare();
		$cleanup();

		$this->assertIsCallable( $cleanup );
	}

	public function test_get_theme_name() {
		$preparation = new Use_Minimal_Theme_Preparation( $this->theme_slug, $this->theme_dir );
		$cleanup     = $preparation->prepare();
		$theme_name  = $preparation->get_theme_name();
		$cleanup();

		$this->assertSame( 'WP Empty Theme', $theme_name );
	}

	public function test_get_theme_root() {
		$preparation = new Use_Minimal_Theme_Preparation( $this->theme_slug, $this->theme_dir );
		$cleanup     = $preparation->prepare();
		$theme_root  = $preparation->get_theme_root();
		$cleanup();

		$this->assertSame( str_replace( WP_CONTENT_DIR, '', $this->theme_dir ), $theme_root );
	}
}
