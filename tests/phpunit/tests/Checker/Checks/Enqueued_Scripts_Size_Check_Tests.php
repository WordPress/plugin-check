<?php
/**
 * Tests for the Enqueued_Scripts_Size_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Checks\Performance\Enqueued_Scripts_Size_Check;
use WordPress\Plugin_Check\Checker\Preparation;
use WordPress\Plugin_Check\Test_Utils\TestCase\Runtime_Check_UnitTestCase;

class Enqueued_Scripts_Size_Check_Tests extends Runtime_Check_UnitTestCase {

	public function test_get_shared_preparations() {
		$check        = new Enqueued_Scripts_Size_Check();
		$preparations = $check->get_shared_preparations();

		$this->assertIsArray( $preparations );

		foreach ( $preparations as $class => $args ) {
			$instance = new $class( ...$args );
			$this->assertInstanceOf( Preparation::class, $instance );
		}
	}

	public function test_prepare() {
		// Create variables in global state.
		$_GET['test_prepare']    = true;
		$_POST['test_prepare']   = true;
		$_SERVER['test_prepare'] = true;

		$current_screen            = $GLOBALS['current_screen'];
		$GLOBALS['current_screen'] = 'test_prepare';

		$check   = new Enqueued_Scripts_Size_Check();
		$cleanup = $check->prepare();

		// Modify the variables in the global state.
		$_GET['test_prepare']      = false;
		$_POST['test_prepare']     = false;
		$_SERVER['test_prepare']   = false;
		$GLOBALS['current_screen'] = 'altered';

		$cleanup();

		$test_get     = $_GET['test_prepare'];
		$test_post    = $_POST['test_prepare'];
		$test_server  = $_SERVER['test_prepare'];
		$test_globals = $GLOBALS['current_screen'];

		// Restore the global state.
		unset( $_GET['test_prepare'] );
		unset( $_POST['test_prepare'] );
		unset( $_SERVER['test_prepare'] );
		$GLOBALS['current_screen'] = $current_screen;

		$this->assertTrue( $test_get );
		$this->assertTrue( $test_post );
		$this->assertTrue( $test_server );
		$this->assertSame( 'test_prepare', $test_globals );
	}

	public function test_run_without_errors() {
		// Load the test plugin.
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-script-size-check/load.php';

		$check   = new Enqueued_Scripts_Size_Check();
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );

		$this->assertEquals( 0, $results->get_error_count() );
		$this->assertEquals( 0, $results->get_warning_count() );
	}

	public function test_run_with_errors() {
		// Load the test plugin.
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-script-size-check/load.php';

		// Test with low threshold to force warnings.
		$check   = new Enqueued_Scripts_Size_Check( 1 );
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertNotEmpty( $warnings );

		$this->assertEquals( 0, $results->get_error_count() );
		$this->assertEquals( 4, $results->get_warning_count() );
	}

	public function test_run_with_errors_for_inline_script() {
		// Load the test plugin.
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-script-size-check/load.php';

		// Test with threshold under the enqueued test-script.js byte size.
		$check   = new Enqueued_Scripts_Size_Check( 20 );
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertNotEmpty( $warnings );

		$this->assertEquals( 0, $results->get_error_count() );
		$this->assertEquals( 4, $results->get_warning_count() );
	}

	public function test_run_reports_dep_size() {
		// Load the test plugin that enqueues a script with external dependencies.
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-script-size-check-with-deps/load.php';

		$check   = new Enqueued_Scripts_Size_Check( 1 );
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$warnings = $this->flatten_warnings( $results->get_warnings() );

		$plugin_codes = array_values(
			array_filter(
				array_column( $warnings, 'code' ),
				static function ( $code ) {
					return 'EnqueuedScriptsSize.ScriptSizeGreaterThanThreshold' === $code;
				}
			)
		);
		$dep_codes    = array_values(
			array_filter(
				array_column( $warnings, 'code' ),
				static function ( $code ) {
					return 'EnqueuedScriptsSize.ExternalDependencySize' === $code;
				}
			)
		);

		// 1 plugin asset x 4 URLs.
		$this->assertCount( 4, $plugin_codes );
		// 1 combined dep warning per URL (4 URLs).
		$this->assertCount( 4, $dep_codes );
	}

	public function test_run_handles_external_dep_safely() {
		// Fixture enqueues a CDN dep that the resolver cannot measure.
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-script-size-check-with-deps/load.php';

		// High threshold so plugin bucket alone does not warn, but the
		// combination does.
		$check   = new Enqueued_Scripts_Size_Check( 50 );
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors = $results->get_errors();
		$this->assertEmpty( $errors );

		$warnings  = $this->flatten_warnings( $results->get_warnings() );
		$dep_codes = array_values(
			array_filter(
				array_column( $warnings, 'code' ),
				static function ( $code ) {
					return 'EnqueuedScriptsSize.ExternalDependencySize' === $code;
				}
			)
		);

		$this->assertNotEmpty( $dep_codes, 'External dep warning should still emit when CDN src cannot be measured.' );
	}

	public function test_run_no_dep_warning_when_no_deps() {
		// Plain fixture, no deps registered.
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-script-size-check/load.php';

		$check   = new Enqueued_Scripts_Size_Check( 1 );
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$warnings  = $this->flatten_warnings( $results->get_warnings() );
		$dep_codes = array_values(
			array_filter(
				array_column( $warnings, 'code' ),
				static function ( $code ) {
					return 'EnqueuedScriptsSize.ExternalDependencySize' === $code;
				}
			)
		);

		$this->assertCount( 0, $dep_codes, 'Dep warning must not fire when plugin enqueues no external deps.' );
	}

	public function test_run_no_false_positive_on_unrelated_external_scripts() {
		// Fixture enqueues a small plugin script plus a large unrelated external
		// script that is NOT in the plugin's dep graph.
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-script-size-check-with-unrelated-external-script/load.php';

		// Threshold above the plugin script size, but below it once the
		// unrelated external would be (incorrectly) counted.
		$check   = new Enqueued_Scripts_Size_Check( 1000 );
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors = $results->get_errors();
		$this->assertEmpty( $errors );

		$warnings = $this->flatten_warnings( $results->get_warnings() );

		$plugin_codes = array_values(
			array_filter(
				array_column( $warnings, 'code' ),
				static function ( $code ) {
					return 'EnqueuedScriptsSize.ScriptSizeGreaterThanThreshold' === $code;
				}
			)
		);
		$dep_codes    = array_values(
			array_filter(
				array_column( $warnings, 'code' ),
				static function ( $code ) {
					return 'EnqueuedScriptsSize.ExternalDependencySize' === $code;
				}
			)
		);

		$this->assertCount( 0, $plugin_codes, 'Plugin bucket alone must not trigger the per-file warning.' );
		$this->assertCount(
			0,
			$dep_codes,
			'Unrelated external scripts not in the plugin dep graph must not trigger the dep warning.'
		);
	}

	private function flatten_warnings( $warnings ) {
		$flat = array();
		foreach ( $warnings as $lines ) {
			foreach ( $lines as $columns ) {
				foreach ( $columns as $entries ) {
					foreach ( $entries as $entry ) {
						$flat[] = $entry;
					}
				}
			}
		}
		return $flat;
	}
}
