<?php
/**
 * Tests for the Universal_Runtime_Preparation class.
 *
 * @package plugin-check
 */

namespace Checker\Preparations;

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Preparations\Universal_Runtime_Preparation;
use WP_UnitTestCase;

class Universal_Runtime_Preparation_Tests extends WP_UnitTestCase {

	public function test_prepare() {

		$plugin_basename_file = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );

		// Remove the WP tests active plugins filter which interfers with this test.
		remove_filter( 'pre_option_active_plugins', 'wp_tests_options' );

		$check_context = new Check_Context( 'test-plugin/test-plugin.php' );

		$universal_runtime_preparation = new Universal_Runtime_Preparation( $check_context );

		$plugins = array(
			'akismet/akismet.php',
			$plugin_basename_file,
			'wp-reset/wp-reset.php',
		);

		update_option( 'active_plugins', $plugins );

		$cleanup = $universal_runtime_preparation->prepare();

		$cleanup();

		$this->assertEquals( 'wp-empty-theme', get_option( 'template' ) );

		$active_plugins = get_option( 'active_plugins' );

		$this->assertSame( array( $plugin_basename_file ), $active_plugins );
	}

}
