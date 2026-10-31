<?php

use WordPress\Plugin_Check\Checker\Checks\Performance\Editor_Dependencies_Check;
use WordPress\Plugin_Check\Test_Utils\TestCase\Runtime_Check_UnitTestCase;

class Editor_Dependencies_Runtime_Check_Tests extends Runtime_Check_UnitTestCase {

	public function test_frontend_scripts_are_collected_and_reported_once() {
		require UNIT_TESTS_PLUGIN_DIR . 'test-plugin-editor-entry-points/load.php';
		$context = $this->get_context( WP_PLUGIN_CHECK_MAIN_FILE );
		$results = $this->run_check( new Editor_Dependencies_Check(), $context );
		$this->assertSame( 0, $results->get_error_count() );
		$this->assertSame( 1, $results->get_warning_count() );
		$this->assertStringContainsString( 'EditorDependencies.AdminEntryPoint', wp_json_encode( $results->get_warnings() ) );
	}
}
