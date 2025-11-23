<?php
/**
 * Tests for the Amend_DB_Base_Prefix trait.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Traits\Amend_DB_Base_Prefix;

class Amend_DB_Base_Prefix_Tests extends WP_UnitTestCase {

	use Amend_DB_Base_Prefix;

	protected static $extra_site_id;
	protected $orig_base_prefix;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		if ( ! is_multisite() ) {
			return;
		}

		self::$extra_site_id = $factory->blog->create();
	}

	public static function wpTearDownAfterClass() {
		if ( ! is_multisite() ) {
			return;
		}

		wp_delete_site( self::$extra_site_id );
	}

	public function set_up() {
		global $wpdb;

		parent::set_up();

		$this->orig_base_prefix = $wpdb->base_prefix;
	}

	public function tear_down() {
		global $wpdb;

		$wpdb->base_prefix = $this->orig_base_prefix;

		parent::tear_down();
	}

	public function test_amend_db_base_prefix() {
		global $wpdb;

		$cleanup        = $this->amend_db_base_prefix();
		$changed_prefix = $wpdb->base_prefix;

		$cleanup();
		$restored_prefix = $wpdb->base_prefix;

		$this->assertSame( $this->orig_base_prefix . 'pc_', $changed_prefix );
		$this->assertSame( $this->orig_base_prefix, $restored_prefix );
	}

	public function test_amend_db_base_prefix_no_base_prefix() {
		global $wpdb;

		$this->expectException( RuntimeException::class );

		/*
		 * This should never be done. This test purely simulates a scenario where either the method is called too early
		 * or someone tampered with the database object in unexpected ways.
		 */
		unset( $wpdb->base_prefix );
		$this->amend_db_base_prefix();
		$unchanged_prefix = $wpdb->base_prefix;

		$this->assertSame( $this->orig_base_prefix, $unchanged_prefix );
	}

	/**
	 * @group ms-required
	 */
	public function test_amend_db_base_prefix_with_subsite() {
		global $wpdb;

		switch_to_blog( self::$extra_site_id );

		$cleanup        = $this->amend_db_base_prefix();
		$changed_prefix = $wpdb->base_prefix;

		$cleanup();
		$restored_prefix = $wpdb->base_prefix;

		restore_current_blog();

		$this->assertSame( $this->orig_base_prefix . 'pc_', $changed_prefix );
		$this->assertSame( $this->orig_base_prefix, $restored_prefix );
	}
}
