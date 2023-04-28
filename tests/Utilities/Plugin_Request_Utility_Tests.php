<?php
/**
 * Tests for the Plugin_Request_Utility class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

class Plugin_Request_Utility_Tests extends WP_UnitTestCase {

	public function test_get_plugin_basename_from_input() {
		$plugin = Plugin_Request_Utility::get_plugin_basename_from_input( 'plugin-check' );

		$this->assertSame( plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE ), $plugin );
	}

	public function test_get_plugin_basename_from_input_with_empty_input() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Invalid plugin slug: Plugin slug must not be empty.' );

		Plugin_Request_Utility::get_plugin_basename_from_input( '' );
	}

	public function test_get_plugin_basename_from_input_with_invalid_input() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Invalid plugin slug: Plugin with slug invalid is not installed.' );

		Plugin_Request_Utility::get_plugin_basename_from_input( 'invalid' );
	}

	public function test_initialize_runner_with_cli() {
		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'plugin-check',
		);

		Plugin_Request_Utility::initialize_runner();
		$runner = Plugin_Request_Utility::get_runner();

		unset( $_SERVER['argv'] );

		$this->assertInstanceOf( CLI_Runner::class, $runner );
	}

	public function test_initialize_runner_with_ajax() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_REQUEST['action'] = 'plugin_check_run_checks';
		$_REQUEST['plugin'] = 'plugin-check';

		Plugin_Request_Utility::initialize_runner();
		$runner = Plugin_Request_Utility::get_runner();

		$this->assertInstanceOf( AJAX_Runner::class, $runner );
	}

	public function test_destroy_runner_with_cli() {
		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'plugin-check',
			'--checks=runtime-check',
		);

		add_filter(
			'wp_plugin_check_checks',
			function( $checks ) {
				return array(
					'runtime-check' => new WordPress\Plugin_Check\Test_Data\Runtime_Check(),
				);
			}
		);

		Plugin_Request_Utility::initialize_runner();
		$runner = Plugin_Request_Utility::get_runner();

		Plugin_Request_Utility::destroy_runner();
		$destroyed = Plugin_Request_Utility::get_runner();

		unset( $_SERVER['argv'] );

		$this->assertInstanceOf( CLI_Runner::class, $runner );
		$this->assertNull( $destroyed );
	}

	public function test_destroy_runner_with_ajax() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_REQUEST['action'] = 'plugin_check_run_checks';
		$_REQUEST['plugin'] = 'plugin-check';
		$_REQUEST['checks'] = array( 'runtime_check' );

		add_filter(
			'wp_plugin_check_checks',
			function( $checks ) {
				return array(
					'runtime_check' => new WordPress\Plugin_Check\Test_Data\Runtime_Check(),
				);
			}
		);

		Plugin_Request_Utility::initialize_runner();
		$runner = Plugin_Request_Utility::get_runner();

		Plugin_Request_Utility::destroy_runner();
		$destroyed = Plugin_Request_Utility::get_runner();

		$this->assertInstanceOf( AJAX_Runner::class, $runner );
		$this->assertNull( $destroyed );
	}

	public function test_destroy_runner_with_no_runner() {
		Plugin_Request_Utility::destroy_runner();
		$runner = Plugin_Request_Utility::get_runner();

		$this->assertNull( $runner );
	}
}
