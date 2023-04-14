<?php
/**
 * Tests for the Force_Single_Plugin_Preparation class.
 *
 * @package plugin-check
 */

namespace Checker\Preparations;

use Exception;
use WordPress\Plugin_Check\Checker\Preparations\Force_Single_Plugin_Preparation;
use WP_UnitTestCase;

class Force_Single_Plugin_Preparation_Tests extends WP_UnitTestCase {

	protected $plugin_basename_file;

	public function set_up() {
		parent::set_up();

		$this->plugin_basename_file = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );
	}

	public function test_prepare_plugin_exists() {

		$preparation = new Force_Single_Plugin_Preparation( 'akismet/akismet.php' );

		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Invalid plugin akismet/akismet.php: Plugin file does not exist.' );
		$preparation->prepare();
	}

	/**
	 * @throws Exception Throw exception.
	 */
	public function test_prepare() {
		// Remove the WP tests active plugins filter which interfers with this test.
		remove_filter( 'pre_option_active_plugins', 'wp_tests_options' );

		$preparation    = new Force_Single_Plugin_Preparation( $this->plugin_basename_file );
		$active_plugins = array(
			'akismet/akismet.php',
			$this->plugin_basename_file,
			'wp-reset/wp-reset.php',
		);

		update_option( 'active_plugins', $active_plugins );

		$cleanup = $preparation->prepare();
		$before  = get_option( 'active_plugins' );
		$cleanup();
		$after = get_option( 'active_plugins' );

		$this->assertSame( array( $this->plugin_basename_file ), $before );
		$this->assertSame( $active_plugins, $after );
	}

	public function test_filter_active_plugins() {

		$preparation = new Force_Single_Plugin_Preparation( 'wp-reset/wp-reset.php' );

		$plugins = array(
			'akismet/akismet.php',
			$this->plugin_basename_file,
			'wp-reset/wp-reset.php',
		);

		$active_plugins = $preparation->filter_active_plugins( $plugins );

		$this->assertSame(
			array(
				'wp-reset/wp-reset.php',
				$this->plugin_basename_file,
			),
			$active_plugins
		);

		$plugins = array(
			'akismet/akismet.php',
			$this->plugin_basename_file,
			'test-plugin/test-plugin.php',
		);

		$active_plugins = $preparation->filter_active_plugins( $plugins );

		$this->assertSame( $plugins, $active_plugins );
	}
}
