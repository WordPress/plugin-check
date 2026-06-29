<?php
/**
 * Tests for the Inlined_React_Runtime_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Performance\Inlined_React_Runtime_Check;

class Inlined_React_Runtime_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$check         = new Inlined_React_Runtime_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-inlined-react-runtime-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertEmpty( $check_result->get_errors() );
		$this->assertSame( 2, $check_result->get_warning_count() );

		$this->assertArrayHasKey( 'index.js', $warnings );
		$this->assertArrayHasKey( 'legacy.js', $warnings );

		$this->assertSame( 'inlined_jsx_runtime', $this->get_first_code( $warnings['index.js'] ) );
		$this->assertSame( 'react_removed_api', $this->get_first_code( $warnings['legacy.js'] ) );
	}

	public function test_run_without_errors() {
		$check         = new Inlined_React_Runtime_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-inlined-react-runtime-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$this->assertEmpty( $check_result->get_errors() );
		$this->assertEmpty( $check_result->get_warnings() );
		$this->assertSame( 0, $check_result->get_error_count() );
		$this->assertSame( 0, $check_result->get_warning_count() );
	}

	/**
	 * Returns the message code of the first warning reported for a file.
	 *
	 * @param array $file_warnings Warnings for a single file, keyed by line and column.
	 * @return string|null The message code, or null if none was found.
	 */
	private function get_first_code( array $file_warnings ) {
		foreach ( $file_warnings as $columns ) {
			foreach ( $columns as $messages ) {
				if ( isset( $messages[0]['code'] ) ) {
					return $messages[0]['code'];
				}
			}
		}

		return null;
	}
}
