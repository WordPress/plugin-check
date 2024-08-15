<?php
/**
 * Tests for the Non_Blocking_Scripts_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Checks\Performance\Non_Blocking_Scripts_Check;
use WordPress\Plugin_Check\Checker\Preparation;
use WordPress\Plugin_Check\Test_Utils\TestCase\Runtime_Check_UnitTestCase;

class Non_Blocking_Scripts_Check_Tests extends Runtime_Check_UnitTestCase {

	public function test_get_shared_preparations() {
		$check        = new Non_Blocking_Scripts_Check();
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

		$check   = new Non_Blocking_Scripts_Check();
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

	public function test_run_with_warnings() {
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-non-blocking-scripts-check/load.php';

		$check   = new Non_Blocking_Scripts_Check();
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertNotEmpty( $warnings );

		$header_script = 'tests/phpunit/testdata/plugins/test-plugin-non-blocking-scripts-check/header.js';
		$footer_script = 'tests/phpunit/testdata/plugins/test-plugin-non-blocking-scripts-check/footer.js';
		$async_script  = 'tests/phpunit/testdata/plugins/test-plugin-non-blocking-scripts-check/async.js';
		$defer_script  = 'tests/phpunit/testdata/plugins/test-plugin-non-blocking-scripts-check/defer.js';

		$this->assertArrayNotHasKey( $async_script, $warnings, 'An async script should not cause any warnings' );
		$this->assertArrayNotHasKey( $defer_script, $warnings, 'A deferred script should not cause any warnings' );
		$this->assertArrayHasKey( $header_script, $warnings, 'A header script should cause a warning' );
		$this->assertArrayHasKey( $footer_script, $warnings, 'A footer script should cause a warning' );

		$this->assertSame( 'NonBlockingScripts.BlockingHeadScript', $warnings[ $header_script ][0][0][0]['code'] );
		$this->assertSame( 'NonBlockingScripts.NoStrategy', $warnings[ $footer_script ][0][0][0]['code'] );
	}
}
