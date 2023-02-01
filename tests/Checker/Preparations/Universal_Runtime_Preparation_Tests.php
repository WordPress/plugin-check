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

		$check_context = new Check_Context( 'test-plugin/test-plugin.php' );

		$universal_runtime_preparation = new Universal_Runtime_Preparation( $check_context );

		$plugins = array(
			'akismet/akismet.php',
			'plugin-check/plugin-check.php',
			'wp-reset/wp-reset.php',
		);

		update_option( 'active_plugins', $plugins );

		$cleanup = $universal_runtime_preparation->prepare();

		$cleanup();

		$this->assertIsCallable( $cleanup );

		$this->assertEquals( 'wp-empty-theme', get_option( 'template' ) );

		$active_plugins = get_option( 'active_plugins' );

		$this->assertContains( 'plugin-check/plugin-check.php', $active_plugins );

		$this->assertEquals(
			array(
				'plugin-check/plugin-check.php',
				'plugin-check/plugin-check.php',
			),
			$active_plugins
		);
	}

}
