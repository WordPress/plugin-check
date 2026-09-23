<?php
/**
 * Tests for the React_Usage_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\General\React_Usage_Check;

class React_Usage_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check_result = $this->run_check( 'test-plugin-react-usage-with-errors' );
		$errors       = $check_result->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertSame( 8, $check_result->get_error_count() );

		// Each package is reported under its own code.
		$this->assertSame( array( 'inlined_react_jsx_runtime' ), $this->get_codes( $errors, 'jsx-runtime.js' ) );
		$this->assertSame( array( 'inlined_react' ), $this->get_codes( $errors, 'react.js' ) );
		$this->assertSame( array( 'inlined_react_dom' ), $this->get_codes( $errors, 'react-dom.js' ) );

		// The JSX runtime is recognized from the jsx export alone, and the react
		// and react-dom copies the same file externalizes stay unreported.
		$this->assertSame( array( 'inlined_react_jsx_runtime' ), $this->get_codes( $errors, 'jsx-runtime-tree-shaken.js' ) );

		// Externalizing the renderer does not externalize the library: the
		// window.ReactDOM reference must not suppress the inlined react copy.
		$this->assertSame( array( 'inlined_react' ), $this->get_codes( $errors, 'react-external-dom.js' ) );

		// React 17 production builds call Symbol.for through a local variable.
		$this->assertSame( array( 'inlined_react' ), $this->get_codes( $errors, 'react-17-prod.js' ) );

		// A development build is reported under the same code but with a higher severity.
		$this->assertSame( array( 'inlined_react_jsx_runtime' ), $this->get_codes( $errors, 'jsx-runtime-dev.js' ) );
		$this->assertSame( 6, $this->get_first_message( $errors, 'jsx-runtime.js' )['severity'] );
		$this->assertSame( 7, $this->get_first_message( $errors, 'jsx-runtime-dev.js' )['severity'] );
		$this->assertStringContainsString( 'development build', $this->get_first_message( $errors, 'jsx-runtime-dev.js' )['message'] );

		// A declared react-jsx-runtime dependency in the sibling asset file must
		// not suppress the inlined pre-19 runtime found in the JavaScript.
		$this->assertSame( array( 'inlined_react_jsx_runtime' ), $this->get_codes( $errors, 'asset-declared.js' ) );
	}

	public function test_run_with_warnings() {
		$check_result = $this->run_check( 'test-plugin-react-usage-with-errors' );
		$warnings     = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertSame( 6, $check_result->get_warning_count() );

		// Every removed API used in a file is reported, not only the first one.
		$this->assertSame(
			array( 'ReactDOM.render', 'ReactDOM.findDOMNode' ),
			$this->get_reported_apis( $warnings, 'legacy.js' )
		);
		$this->assertSame(
			array( 'ReactDOM.hydrate', 'ReactDOM.unmountComponentAtNode' ),
			$this->get_reported_apis( $warnings, 'hydrate.js' )
		);

		// A quote inside a regular expression literal must not be read as the
		// start of a string, which would hide the call following it.
		$this->assertSame(
			array( 'ReactDOM.findDOMNode' ),
			$this->get_reported_apis( $warnings, 'regex-literal.js' )
		);

		// A file that inlines a package is reported for that alone, even though
		// the inlined renderer defines the removed APIs itself.
		$this->assertArrayNotHasKey( 'react-dom.js', $warnings );

		// Positions follow the line endings of the file. Counting the ones native
		// to this machine instead would put the call on line 1 of a file written
		// with carriage returns alone.
		$this->assertSame( array( 3 ), array_keys( $warnings['cr-line-endings.js'] ) );
	}

	public function test_run_without_errors() {
		$check_result = $this->run_check( 'test-plugin-react-usage-without-errors' );

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
		$check         = new React_Usage_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . $plugin . '/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		return $check_result;
	}

	/**
	 * Returns the message codes reported for a file.
	 *
	 * @param array  $reported All reported messages, keyed by file.
	 * @param string $file     File to collect the codes for.
	 * @return array List of message codes.
	 */
	private function get_codes( array $reported, $file ) {
		$codes = array();

		foreach ( $this->get_messages( $reported, $file ) as $message ) {
			$codes[] = $message['code'];
		}

		return $codes;
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
			$this->assertSame( 'react_removed_api', $message['code'] );

			if ( preg_match( '/"([^"]+)"/', $message['message'], $matches ) ) {
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
