<?php
/**
 * Tests for the Checks class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Test_Utils\Traits\With_Mock_Filesystem;

class Runtime_Environment_Setup_Tests extends WP_UnitTestCase {

	use With_Mock_Filesystem;

	public function test_set_up() {
		global $wp_filesystem, $wpdb, $table_prefix;

		$this->set_up_mock_filesystem();

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, $table_prefix . 'pc_' ) );
	}

	public function test_clean_up() {
		global $wp_filesystem, $wpdb, $table_prefix;

		$this->set_up_mock_filesystem();

		$runtime_setup = new Runtime_Environment_Setup();
		$runtime_setup->set_up();
		$runtime_setup->clean_up();

		$this->assertTrue( 0 <= strpos( $wpdb->last_query, $table_prefix . 'pc_' ) );
	}
}
