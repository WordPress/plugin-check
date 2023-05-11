<?php

/**
 * @group Checks
 * @group PluginUpdaters
 */
class Test_Plugin_Updaters extends PluginCheck_TestCase {
	public function test_update_uri() {
		$usage = '
		/*
		 * Plugin Name: Test Plugin
		 * Update URI: https://example.org/
		 */
		';

		$results = $this->run_against_string( $usage );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'plugin_updater_detected', 'needle' => 'Update URI header' ] );
	}

	public function test_blocked_files() {
		$results = $this->run_against_virtual_files( [
			'plugin-update-checker.php' => "// Do something that we don't want.",
		] );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'plugin_updater_detected', 'needle' => 'Plugin Updater detected' ] );
	}

	/**
	 * @dataProvider data_plugin_updater_classes
	 */
	public function test_plugin_updater_classes( $triggering_php ) {
		$results = $this->run_against_string( $triggering_php );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'plugin_updater_detected', 'needle' => 'Plugin Updater detected' ] );
	}

	public function data_plugin_updater_classes() {
		return [
			[ 'class WP_GitHub_Updater {}' ],
			[ 'class WPGitHubUpdater {}' ],
			[ "namespace Example;\nclass WPGitHubUpdater {}" ],
			[ " _e( 'Plugin Update Checker', 'plugin-update-checker' ); " ],
			[ 'class My_Plugin_Updater {}' ],
			[ "add_filter( 'updater.myplugin.php', 'example' );" ],
			[ "add_filter( 'set_site_transient_update_plugins', 'example' );" ],
		];
	}

	/**
	 * @dataProvider data_maybe_altering_updates
	 */
	public function test_maybe_altering_updates( $triggering_php ) {
		$results = $this->run_against_string( $triggering_php );

		$this->assertHasErrorType( $results, [ 'type' => 'warning', 'code' => 'update_modification_detected', 'needle' => 'altering WordPress update routines' ] );
	}

	public function data_maybe_altering_updates() {
		return [
			[ "add_filter( 'pre_set_site_transient_update_plugins', 'example' );" ],
			[ "add_filter( 'pre_set_site_transient_update_themes', 'example' );" ],
			[ "add_filter( 'auto_update_plugin', '__return_true' );" ],
			[ "add_filter( '_site_transient_update_plugins', 'example' ) " ],
			[ 'add_filter( "pre_site_option__site_transient_update_plugins", "example" );' ],
		];
	}

}
