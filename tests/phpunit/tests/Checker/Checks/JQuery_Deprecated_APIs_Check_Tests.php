<?php
/**
 * Tests for the JQuery_Deprecated_APIs_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\General\JQuery_Deprecated_APIs_Check;

class JQuery_Deprecated_APIs_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_warnings() {
		$check_result = $this->run_check( 'test-plugin-jquery-deprecated-apis-with-errors' );
		$warnings     = $check_result->get_warnings();

		$this->assertEmpty( $check_result->get_errors() );
		$this->assertSame( 19, $check_result->get_warning_count() );

		// Every removed API used in a file is reported once, in the order of the list.
		$this->assertSame(
			array(
				'jQuery.isArray()',
				'jQuery.parseJSON()',
				'jQuery.trim()',
				'jQuery.now()',
				'jQuery.unique()',
				'jQuery.isFunction()',
				'jQuery.nodeName()',
				'jQuery.isNumeric()',
				'jQuery.isWindow()',
				'jQuery.type()',
				'jQuery.camelCase()',
				'jQuery.cssProps',
				'jQuery.fx.interval',
				'jQuery.event.global',
				'jQuery.Deferred.getStackHook',
			),
			$this->get_reported_apis( $warnings, 'all-removed.js' )
		);

		// The aliased $ used inside a jQuery wrapper function is recognized.
		$this->assertSame( array( 'jQuery.trim()' ), $this->get_reported_apis( $warnings, 'alias.js' ) );

		// So is the global read from the window object.
		$this->assertSame( array( 'jQuery.parseJSON()' ), $this->get_reported_apis( $warnings, 'window-global.js' ) );

		// A bundler wraps the reference in a sequence expression.
		$this->assertSame(
			array( 'jQuery.trim()', 'jQuery.now()' ),
			$this->get_reported_apis( $warnings, 'bundled-call.js' )
		);

		// The message names the replacement, and points to where to read more.
		$first = $this->get_first_message( $warnings, 'all-removed.js' );
		$this->assertSame( 'jquery_removed_api', $first['code'] );
		$this->assertSame( 'jQuery.isArray() is removed in jQuery 4.0. Use Array.isArray() instead.', $first['message'] );
		$this->assertSame( JQuery_Deprecated_APIs_Check::DOCS_URL, $first['docs'] );
		$this->assertSame( 5, $first['severity'] );

		// The position is that of the first use, on line 2 after the tab and `var list = `.
		$this->assertArrayHasKey( 2, $warnings['all-removed.js'] );
		$this->assertArrayHasKey( 13, $warnings['all-removed.js'][2] );
	}

	public function test_run_without_warnings() {
		// Native methods that share a name, mentions in comments and strings,
		// deprecated APIs that jQuery 4 still ships, minified files, and copies of
		// jQuery itself must all stay silent.
		$check_result = $this->run_check( 'test-plugin-jquery-deprecated-apis-without-errors' );

		$this->assertEmpty( $check_result->get_errors() );
		$this->assertEmpty( $check_result->get_warnings() );
		$this->assertSame( 0, $check_result->get_error_count() );
		$this->assertSame( 0, $check_result->get_warning_count() );
	}

	/**
	 * Runs the check against one of the test plugins.
	 *
	 * @param string $plugin Directory name of the test plugin.
	 * @return Check_Result The result of the check.
	 */
	private function run_check( $plugin ) {
		$check         = new JQuery_Deprecated_APIs_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . $plugin . '/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		return $check_result;
	}

	/**
	 * Returns the removed API names reported for a file, in source order.
	 *
	 * @param array  $warnings All warnings, keyed by file.
	 * @param string $file     File to collect the API names for.
	 * @return array List of API names.
	 */
	private function get_reported_apis( array $warnings, $file ) {
		$apis = array();

		foreach ( $this->get_messages( $warnings, $file ) as $message ) {
			$this->assertSame( 'jquery_removed_api', $message['code'] );

			if ( preg_match( '/^(\S+) is removed/', $message['message'], $matches ) ) {
				$apis[] = $matches[1];
			}
		}

		return $apis;
	}

	/**
	 * Returns the first message reported for a file.
	 *
	 * @param array  $reported All reported messages, keyed by file.
	 * @param string $file     File to return the message for.
	 * @return array The message data.
	 */
	private function get_first_message( array $reported, $file ) {
		$messages = $this->get_messages( $reported, $file );

		return $messages[0];
	}

	/**
	 * Flattens the line and column nesting of the messages for a single file.
	 *
	 * @param array  $reported All reported messages, keyed by file.
	 * @param string $file     File to flatten the messages for.
	 * @return array List of message data arrays.
	 */
	private function get_messages( array $reported, $file ) {
		$this->assertArrayHasKey( $file, $reported );

		$flattened = array();

		foreach ( $reported[ $file ] as $columns ) {
			foreach ( $columns as $messages ) {
				foreach ( $messages as $message ) {
					$flattened[] = $message;
				}
			}
		}

		return $flattened;
	}
}
