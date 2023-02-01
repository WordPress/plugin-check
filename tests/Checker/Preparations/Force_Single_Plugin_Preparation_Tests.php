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

	public function test_prepare_plugin_exists() {

		$preparation = new Force_Single_Plugin_Preparation( 'akismet/akismet.php' );
		$message     = '';

		try {
			$preparation->prepare();
		} catch ( Exception $e ) {
			$message = $e->getMessage();
		}

		$this->assertEquals( 'The plugin akismet/akismet.php does not exists', $message );
	}

	public function test_prepare() {

		$preparation = new Force_Single_Plugin_Preparation( 'plugin-check/plugin-check.php' );

		$plugins = array(
			'akismet/akismet.php',
			'plugin-check/plugin-check.php',
			'wp-reset/wp-reset.php',
		);

		update_option( 'active_plugins', $plugins );

		$cleanup = $preparation->prepare();

		$active_plugins = get_option( 'active_plugins' );

		$cleanup();

		$this->assertIsCallable( $cleanup );

		$this->assertContains( 'plugin-check/plugin-check.php', $active_plugins );

		$this->assertEquals(
			array(
				'plugin-check/plugin-check.php',
				'plugin-check/plugin-check.php',
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
