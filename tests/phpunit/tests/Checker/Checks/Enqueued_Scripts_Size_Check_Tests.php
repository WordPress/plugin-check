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

	public function tear_down() {
		// Remove the dependency files the size-check fixture writes to the uploads directory.
		$basedir = trailingslashit( wp_upload_dir()['basedir'] );
		foreach ( array( 'plugin-check-external-dependency.js', 'plugin-check-external-transitive-dependency.js' ) as $file ) {
			if ( file_exists( $basedir . $file ) ) {
				unlink( $basedir . $file );
			}
		}

		parent::tear_down();
	}

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

	public function test_run_counts_external_dependencies() {
		// The fixture's script depends on a 1000 byte script served from the uploads directory,
		// which in turn depends on a 500 byte transitive one, plus a remote (CDN) script that
		// can't be measured locally and must be skipped rather than fataling.
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-enqueued-script-size-check/load.php';

		// The threshold sits above the plugin's own script plus its direct dependency (~1053 bytes)
		// but below the total once the transitive dependency is also counted (~1553 bytes), so the
		// warning only appears if the dependency graph is walked recursively.
		$check   = new Enqueued_Scripts_Size_Check( 1200 );
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$this->assertEmpty( $results->get_errors() );
		$this->assertNotEmpty( $results->get_warnings() );

		$this->assertEquals( 0, $results->get_error_count() );
		$this->assertEquals( 4, $results->get_warning_count() );
	}
}
