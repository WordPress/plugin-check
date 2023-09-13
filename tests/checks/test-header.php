<?php

/**
 * @group Checks
 * @group Header
 */
class Test_Header extends PluginCheck_TestCase {
	public function test_textdomain() {
		$usage = '
		/*
		 * Plugin Name: Test Plugin
		 * Text Domain: example-plugin
		 */
		';

		$matching_slug = $this->run_against_string( $usage, [ 'slug' => 'example-plugin' ] );
		$mismatch_slug = $this->run_against_string( $usage, [ 'slug' => 'mismatched-slug' ] );

		$this->assertHasErrorType( $mismatch_slug, [ 'type' => 'warning', 'code' => 'textdomain_mismatch', 'needle' => 'mismatched-slug' ] );
		$this->assertNotHasErrorType( $matching_slug, [ 'type' => 'warning', 'code' => 'textdomain_mismatch', 'needle' => 'example-plugin' ] );
	}
}
