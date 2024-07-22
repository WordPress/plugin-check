<?php
/**
 * Tests for the Checks class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Test_Data\Empty_Check;
use WordPress\Plugin_Check\Test_Data\Error_Check;
use WordPress\Plugin_Check\Test_Data\Runtime_Check;
use WordPress\Plugin_Check\Test_Utils\Traits\With_Mock_Filesystem;

class CLI_Runner_Tests extends WP_UnitTestCase {

	use With_Mock_Filesystem;

	public function set_up() {
		// Setup the mock filesystem so the Runtime_Environment_Setup works correctly within the CLI_Runner.
		$this->set_up_mock_filesystem();
	}

	public function tear_down() {
		// Force reset the database prefix after runner prepare method called.
		global $wpdb, $table_prefix;
		$wpdb->set_prefix( $table_prefix );
		parent::tear_down();
	}

	public function test_is_plugin_check_returns_true() {
		$runner = new CLI_Runner();

		$this->assertTrue( $runner->is_plugin_check() );
	}

	public function test_prepare_with_runtime_check() {
		global $wp_actions;

		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'plugin-check',
			'--checks=runtime-check',
		);

		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				return array(
					'runtime-check' => new Runtime_Check(),
				);
			}
		);

		$muplugins_loaded = $wp_actions['muplugins_loaded'];
		unset( $wp_actions['muplugins_loaded'] );

		$runner  = new CLI_Runner();
		$cleanup = $runner->prepare();

		$wp_actions['muplugins_loaded'] = $muplugins_loaded;

		$this->assertIsCallable( $cleanup );

		// Assert the Universal_Runtime_Preparation was run.
		$this->assertTrue( has_filter( 'option_active_plugins' ) );
		$this->assertTrue( has_filter( 'default_option_active_plugins' ) );
		$this->assertTrue( has_filter( 'stylesheet' ) );
		$this->assertTrue( has_filter( 'template' ) );
		$this->assertTrue( has_filter( 'pre_option_template' ) );
		$this->assertTrue( has_filter( 'pre_option_stylesheet' ) );
		$this->assertTrue( has_filter( 'pre_option_current_theme' ) );
		$this->assertTrue( has_filter( 'pre_option_template_root' ) );
		$this->assertTrue( has_filter( 'pre_option_stylesheet_root' ) );
	}

	public function test_prepare_with_static_check() {
		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'plugin-check',
			'--checks=empty-check',
		);

		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				return array(
					'empty-check' => new Empty_Check(),
				);
			}
		);

		$runner  = new CLI_Runner();
		$cleanup = $runner->prepare();

		$this->assertIsCallable( $cleanup );

		// Assert the Universal_Runtime_Preparation was not run.
		$this->assertFalse( has_filter( 'option_active_plugins' ) );
		$this->assertFalse( has_filter( 'default_option_active_plugins' ) );
		$this->assertFalse( has_filter( 'stylesheet' ) );
		$this->assertFalse( has_filter( 'template' ) );
		$this->assertFalse( has_filter( 'pre_option_template' ) );
		$this->assertFalse( has_filter( 'pre_option_stylesheet' ) );
		$this->assertFalse( has_filter( 'pre_option_current_theme' ) );
		$this->assertFalse( has_filter( 'pre_option_template_root' ) );
		$this->assertFalse( has_filter( 'pre_option_stylesheet_root' ) );
	}

	public function test_run() {
		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'plugin-check',
			'--checks=empty-check',
		);

		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				return array(
					'empty-check' => new WordPress\Plugin_Check\Test_Data\Empty_Check(),
				);
			}
		);

		$runner = new CLI_Runner();
		$runner->prepare();
		$results = $runner->run();

		$this->assertInstanceOf( Check_Result::class, $results );
		$this->assertEmpty( $results->get_warnings() );
		$this->assertEmpty( $results->get_errors() );
	}

	public function test_run_with_errors() {
		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'plugin-check',
			'--checks=error-check',
		);

		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				return array(
					'error-check' => new Error_Check(),
				);
			}
		);

		$runner = new CLI_Runner();
		$runner->prepare();
		$results = $runner->run();

		$this->assertInstanceOf( Check_Result::class, $results );
		$this->assertEmpty( $results->get_warnings() );
		$this->assertNotEmpty( $results->get_errors() );
	}

	public function test_runner_initialized_early_throws_plugin_basename_exception() {
		global $wp_actions;

		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Invalid plugin: The plugin set does not match the original request parameter.' );

		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'plugin-check',
			'--checks=error-check',
		);

		$muplugins_loaded = $wp_actions['muplugins_loaded'];
		unset( $wp_actions['muplugins_loaded'] );

		$runner = new CLI_Runner();

		$wp_actions['muplugins_loaded'] = $muplugins_loaded;

		$runner->set_plugin( 'invalid-plugin' );

		$runner->prepare();
		$runner->run();
	}

	public function test_runner_initialized_early_throws_checks_exception() {
		global $wp_actions;

		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Invalid checks: The checks to run do not match the original request.' );

		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'plugin-check',
			'--checks=error-check',
		);

		$muplugins_loaded = $wp_actions['muplugins_loaded'];
		unset( $wp_actions['muplugins_loaded'] );

		$runner = new CLI_Runner();

		$wp_actions['muplugins_loaded'] = $muplugins_loaded;

		$runner->set_check_slugs( array( 'runtime_check' ) );

		$runner->prepare();
		$runner->run();
	}
}
