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
		foreach ( array( 'plugin-check-external-dependency.js', 'plugin-check-external-transitive-dependency.js', 'plugin-check-scheme-test.js', 'plugin-check-relative-test.js' ) as $file ) {
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
		// can't be measured locally and must be skipped rather than causing a fatal error.
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

		// The fixture enqueues a 23-byte script with a 29-byte inline block (52 bytes the plugin
		// controls) and pulls in a 1000-byte dependency that itself depends on a 500-byte one
		// (1500 bytes of dependencies). The warning must report those two figures separately and
		// add up to the combined total, so a regression in the plugin/dependency split would
		// change the reported numbers and fail here rather than slipping through on the count alone.
		$expected_plugin_size     = 52;
		$expected_dependency_size = 1500;
		$expected_total_size      = $expected_plugin_size + $expected_dependency_size;

		$split_warning = null;
		foreach ( $this->flatten_warning_messages( $results->get_warnings() ) as $message ) {
			if ( false !== strpos( $message, 'add a further' ) ) {
				$split_warning = $message;
				break;
			}
		}

		$this->assertNotNull( $split_warning, 'A warning should break down the plugin scripts and their dependencies.' );
		$this->assertStringContainsString( 'total ' . size_format( $expected_plugin_size ) . ',', $split_warning );
		$this->assertStringContainsString( 'add a further ' . size_format( $expected_dependency_size ) . ',', $split_warning );
		$this->assertStringContainsString( 'for a combined ' . size_format( $expected_total_size ) . ',', $split_warning );
	}

	public function test_get_local_path_resolves_dependency_with_mismatched_scheme() {
		// A dependency can be served over https while the site's URL roots are registered over http.
		// Its size still has to resolve to the local file rather than being silently counted as zero.
		$uploads = wp_upload_dir();
		$file    = trailingslashit( $uploads['basedir'] ) . 'plugin-check-scheme-test.js';
		file_put_contents( $file, str_repeat( 'a', 128 ) );

		$https_src = set_url_scheme( trailingslashit( $uploads['baseurl'] ) . 'plugin-check-scheme-test.js', 'https' );

		$check  = new Enqueued_Scripts_Size_Check();
		$method = new ReflectionMethod( $check, 'get_local_path_from_roots' );
		$method->setAccessible( true );
		$resolved = $method->invoke( $check, $https_src );

		$this->assertSame( wp_normalize_path( $file ), $resolved );
	}

	public function test_get_path_resolves_root_relative_and_protocol_relative_dependency() {
		// Core registers its scripts root-relative (e.g. /wp-includes/js/jquery/jquery.min.js), and
		// some are enqueued protocol-relative (//host/path). Both have to resolve to the local file,
		// not just fully-qualified srcs, or a plugin's core dependencies get counted as zero bytes.
		$uploads = wp_upload_dir();
		$file    = trailingslashit( $uploads['basedir'] ) . 'plugin-check-relative-test.js';
		file_put_contents( $file, str_repeat( 'a', 64 ) );

		$absolute          = trailingslashit( $uploads['baseurl'] ) . 'plugin-check-relative-test.js';
		$root_relative     = wp_parse_url( $absolute, PHP_URL_PATH );
		$protocol_relative = preg_replace( '#^https?:#', '', set_url_scheme( $absolute, 'http' ) );

		$check  = new Enqueued_Scripts_Size_Check();
		$method = new ReflectionMethod( $check, 'get_path_from_src' );
		$method->setAccessible( true );

		// The plugin url/path are irrelevant here: a non-plugin src falls through to root resolution.
		$this->assertSame(
			wp_normalize_path( $file ),
			$method->invoke( $check, $root_relative, 'http://plugin.invalid/', '/plugin/invalid/' ),
			'A root-relative dependency src should resolve to its local file.'
		);
		$this->assertSame(
			wp_normalize_path( $file ),
			$method->invoke( $check, $protocol_relative, 'http://plugin.invalid/', '/plugin/invalid/' ),
			'A protocol-relative dependency src should resolve to its local file.'
		);
	}

	/**
	 * Flattens the nested warnings array into a plain list of warning messages.
	 *
	 * Warnings are keyed by file, then line, then column, with a list of message data at each
	 * position, so pull every message out regardless of where it landed.
	 *
	 * @param array $warnings Warnings as returned by Check_Result::get_warnings().
	 * @return string[] The warning messages.
	 */
	private function flatten_warning_messages( $warnings ) {
		$messages = array();

		foreach ( $warnings as $lines ) {
			foreach ( $lines as $columns ) {
				foreach ( $columns as $items ) {
					foreach ( $items as $item ) {
						if ( isset( $item['message'] ) ) {
							$messages[] = $item['message'];
						}
					}
				}
			}
		}

		return $messages;
	}
}
