<?php
/**
 * Tests for the Editor_Dependencies_Check class.
 *
 * @package plugin-check
 */

namespace phpunit\tests\Checker\Checks;

use WordPress\Plugin_Check\Checker\Checks\Performance\Editor_Dependencies_Check;
use WordPress\Plugin_Check\Test_Utils\TestCase\Runtime_Check_UnitTestCase;

class Editor_Dependencies_Check_Tests extends Runtime_Check_UnitTestCase {

	/**
	 * Tests detection of editor dependencies.
	 */
	public function test_run_with_errors() {
		// Load the test plugin.
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-editor-dependencies-check-with-error/load.php';

		$check   = new Editor_Dependencies_Check();
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertNotEmpty( $warnings );

		$this->assertSame( 0, $results->get_error_count() );
		$this->assertSame( 2, $results->get_warning_count() );

		$script  = 'tests/phpunit/testdata/plugins/test-plugin-editor-dependencies-check-with-error/script.js';
		$script2 = 'tests/phpunit/testdata/plugins/test-plugin-editor-dependencies-check-with-error/script2.js';

		$this->assertArrayHasKey( $script, $warnings );
		$this->assertArrayHasKey( $script2, $warnings );

		$this->assertSame(
			'EditorDependencies.EditorPackageDependency',
			$warnings[ $script ][0][0][0]['code']
		);

		$this->assertSame(
			'EditorDependencies.EditorPackageDependency',
			$warnings[ $script2 ][0][0][0]['code']
		);
	}

	/**
	 * Tests when no editor dependencies are present.
	 */
	public function test_run_without_errors() {
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-editor-dependencies-check-without-error/load.php';

		$check   = new Editor_Dependencies_Check();
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( $check, $context );

		$this->assertEmpty( $results->get_errors() );
		$this->assertEmpty( $results->get_warnings() );

		$this->assertSame( 0, $results->get_error_count() );
		$this->assertSame( 0, $results->get_warning_count() );
	}
}
