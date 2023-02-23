<?php
/**
 * Tests for the Enqueued_Scripts_Size_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Preparation;
use WordPress\Plugin_Check\Checker\Checks\Enqueued_Scripts_Size_Check;
use WordPress\Plugin_Check\Tests\TestCase\Runtime_Check_UnitTestCase;

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
		$_GET['test_prepare']      = true;
		$_POST['test_prepare']     = true;
		$_SERVER['test_prepare']   = true;

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
		require TESTS_PLUGIN_DIR . '/tests/testdata/plugins/test-plugin-enqueued-script-size-check/load.php';

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
		require TESTS_PLUGIN_DIR . '/tests/testdata/plugins/test-plugin-enqueued-script-size-check/load.php';

		// Test with low threshold to force warnings.
		$check   = new Enqueued_Scripts_Size_Check( 1 );
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertNotEmpty( $warnings );

		$this->assertEquals( 0, $results->get_error_count() );
		$this->assertEquals( 3, $results->get_warning_count() );
	}

	public function test_run_with_errors_for_inline_script() {
		// Load the test plugin.
		require TESTS_PLUGIN_DIR . '/tests/testdata/plugins/test-plugin-enqueued-script-size-check/load.php';

		// Test with threshold under the enqueued test-script.js byte size.
		$check   = new Enqueued_Scripts_Size_Check( 20 );
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertNotEmpty( $warnings );

		$this->assertEquals( 0, $results->get_error_count() );
		$this->assertEquals( 3, $results->get_warning_count() );
	}
}
