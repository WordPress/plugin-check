<?php
/**
 * Tests for the Force_Single_Plugin_Preparation class.
 *
 * @package plugin-check
 */

namespace Checker\Preparations;

use WordPress\Plugin_Check\Checker\Preparations\Force_Single_Plugin_Preparation;
use WP_UnitTestCase;
use Exception;

class Force_Single_Plugin_Preparation_Tests extends WP_UnitTestCase {

//	public function test_prepare_plugin_exists() {
//
//		$preparation = new Force_Single_Plugin_Preparation( 'akismet/akismet.php' );
//		$message     = '';
//
//		try {
//			$preparation->prepare();
//		} catch ( Exception $e ) {
//			$message = $e->getMessage();
//		}
//
//		$this->assertEquals( 'Invalid plugin akismet/akismet.php: Plugin file does not exist.', $message );
//	}

	public function test_prepare() {

		$plugin_check_base_file = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );

		$preparation = new Force_Single_Plugin_Preparation( $plugin_check_base_file );

		$plugins = array(
			'akismet/akismet.php',
			$plugin_check_base_file,
			'wp-reset/wp-reset.php',
		);

		update_option( 'active_plugins', $plugins );

		$cleanup = $preparation->prepare();

		$active_plugins = get_option( 'active_plugins' );

		$cleanup();

		$this->assertIsCallable( $cleanup );

		$this->assertEquals(
			array(
				$plugin_check_base_file,
			),
			$active_plugins
		);
	}

	public function test_filter_active_plugins() {

		$preparation = new Force_Single_Plugin_Preparation( 'wp-reset/wp-reset.php' );

		$plugins = array(
			'akismet/akismet.php',
			'plugin-check/plugin-check.php',
			'wp-reset/wp-reset.php',
		);

		$active_plugins = $preparation->filter_active_plugins( $plugins );

		$this->assertEquals(
			array(
				'wp-reset/wp-reset.php',
				'plugin-check/plugin-check.php',
			),
			$active_plugins
		);
	}
}
