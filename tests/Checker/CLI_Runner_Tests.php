<?php
/**
 * Tests for the Checks class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Checker\Check_Result;

class CLI_Runner_Tests extends WP_UnitTestCase {

	public function test_is_plugin_check_returns_true() {
		$_SERVER['argv'] = array(
			'wp',
			'plugin',
			'check',
			'hello-dolly',
		);

		$runner = new CLI_Runner();

		$this->assertTrue( $runner->is_plugin_check() );
	}

	public function test_is_plugin_check_returns_false() {
		$_SERVER['argv'] = array();

		$runner = new CLI_Runner();

		$this->assertFalse( $runner->is_plugin_check() );
	}

	public function test_prepare_with_runtime_check() {
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

		$runner  = new CLI_Runner();
		$cleanup = $runner->prepare();

		$this->assertIsCallable( $cleanup );

		// Assert the Universal_Runtume_Preparation was run.
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
			function( $checks ) {
				return array(
					'empty-check' => new WordPress\Plugin_Check\Test_Data\Empty_Check(),
				);
			}
		);

		$runner  = new CLI_Runner();
		$cleanup = $runner->prepare();

		$this->assertIsCallable( $cleanup );

		// Assert the Universal_Runtume_Preparation was not run.
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
			function( $checks ) {
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
			function( $checks ) {
				return array(
					'error-check' => new WordPress\Plugin_Check\Test_Data\Error_Check(),
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
}
