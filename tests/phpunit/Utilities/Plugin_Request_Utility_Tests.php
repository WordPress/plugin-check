<?php
/**
 * Tests for the Plugin_Request_Utility class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Test_Data\Runtime_Check;
use WordPress\Plugin_Check\Test_Utils\Traits\With_Mock_Filesystem;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

class Plugin_Request_Utility_Tests extends WP_UnitTestCase {

	use With_Mock_Filesystem;

	public function tear_down() {
		// Force reset the database prefix after runner prepare method called.
		global $wpdb, $table_prefix;
		$wpdb->set_prefix( $table_prefix );
		parent::tear_down();
	}

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

		do_action( 'muplugins_loaded' );

		$runner = Plugin_Request_Utility::get_runner();

		unset( $_SERVER['argv'] );

		$this->assertInstanceOf( CLI_Runner::class, $runner );
	}

	public function test_initialize_runner_with_ajax() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_REQUEST['action'] = 'plugin_check_run_checks';
		$_REQUEST['plugin'] = 'plugin-check';

		Plugin_Request_Utility::initialize_runner();

		do_action( 'muplugins_loaded' );

		$runner = Plugin_Request_Utility::get_runner();

		$this->assertInstanceOf( AJAX_Runner::class, $runner );
	}

	public function test_destroy_runner_with_cli() {
		global $wpdb, $table_prefix, $wp_actions;

		$this->set_up_mock_filesystem();

		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'plugin-check',
			'--checks=runtime_check',
		);

		add_filter(
			'wp_plugin_check_checks',
			function( $checks ) {
				return array(
					'runtime_check' => new Runtime_Check(),
				);
			}
		);

		$muplugins_loaded = $wp_actions['muplugins_loaded'];
		unset( $wp_actions['muplugins_loaded'] );

		Plugin_Request_Utility::initialize_runner();

		do_action( 'muplugins_loaded' );

		// Determine if one of the Universal_Runtume_Preparation was run.
		$prepared = has_filter( 'option_active_plugins' );

		Plugin_Request_Utility::destroy_runner();

		// Determine if the cleanup function was run.
		$cleanup = ! has_filter( 'option_active_plugins' );
		$runner  = Plugin_Request_Utility::get_runner();

		unset( $_SERVER['argv'] );
		$wp_actions['muplugins_loaded'] = $muplugins_loaded;
		$wpdb->set_prefix( $table_prefix );

		$this->assertTrue( $prepared );
		$this->assertTrue( $cleanup );
		$this->assertNull( $runner );
	}

	public function test_destroy_runner_with_ajax() {
		global $wpdb, $table_prefix, $wp_actions;

		$this->set_up_mock_filesystem();

		add_filter( 'wp_doing_ajax', '__return_true' );
		$_REQUEST['action'] = 'plugin_check_run_checks';
		$_REQUEST['plugin'] = 'plugin-check';
		$_REQUEST['checks'] = array( 'runtime_check' );

		add_filter(
			'wp_plugin_check_checks',
			function( $checks ) {
				return array(
					'runtime_check' => new Runtime_Check(),
				);
			}
		);

		$muplugins_loaded = $wp_actions['muplugins_loaded'];
		unset( $wp_actions['muplugins_loaded'] );

		Plugin_Request_Utility::initialize_runner();

		do_action( 'muplugins_loaded' );

		// Determine if one of the Universal_Runtume_Preparation was run.
		$prepared = has_filter( 'option_active_plugins' );

		Plugin_Request_Utility::destroy_runner();

		// Determine if the cleanup function was run.
		$cleanup = ! has_filter( 'option_active_plugins' );
		$runner  = Plugin_Request_Utility::get_runner();

		$wpdb->set_prefix( $table_prefix );
		$wp_actions['muplugins_loaded'] = $muplugins_loaded;

		$this->assertTrue( $prepared );
		$this->assertTrue( $cleanup );
		$this->assertNull( $runner );
	}

	public function test_destroy_runner_with_no_runner() {
		Plugin_Request_Utility::destroy_runner();
		$runner = Plugin_Request_Utility::get_runner();

		$this->assertNull( $runner );
	}

	public function test_default_ignore_directories() {
		$expected_directories = array(
			'.git',
			'vendor',
			'node_modules',
		);

		$actual_directories = Plugin_Request_Utility::get_directories_to_ignore();

		$this->assertEquals( $expected_directories, $actual_directories );
	}

	public function test_filter_ignore_directories() {
		// Define custom directories to ignore for testing.
		$custom_ignore_directories = array(
			'custom_directory_1',
			'custom_directory_2',
		);

		// Create a mock filter that will return our custom directories to ignore.
		$filter_name = 'wp_plugin_check_ignore_directories';
		add_filter(
			$filter_name,
			static function () use ( $custom_ignore_directories ) {
				return $custom_ignore_directories;
			}
		);

		$result = Plugin_Request_Utility::get_directories_to_ignore();

		$this->assertEquals( $custom_ignore_directories, $result );

		// Remove the filter to avoid interfering with other tests.
		remove_filter(
			$filter_name,
			static function () use ( $custom_ignore_directories ) {
				return $custom_ignore_directories;
			}
		);
	}
}
