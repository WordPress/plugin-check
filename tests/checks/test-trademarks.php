<?php

/**
 * @group Checks
 * @group Trademarks
 */
class Test_Trademark_Checks extends PluginCheck_TestCase {
	public function test_plugin_headers() {
		$results = $this->run_against_string('<?php
			// Plugin Name: Example Plugin
		' );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'trademarked_term', 'needle' => 'plugin' ] );
	}

	public function test_readme() {
		$results = $this->run_against_virtual_files( [
			'readme.txt' => '=== Example Plugin ==='
		] );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'trademarked_term', 'needle' => 'plugin' ] );
	}

	public function test_slug() {
		$results = $this->run_against_virtual_files(
			[ 'readme.txt' => '=== Example Good Name ===' ],
			[ 'slug' => 'example-bad-plugin-slug' ]
		);

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'trademarked_term', 'needle' => 'plugin' ] );
	}

	public function test_plugin_headers_for_use_exception() {
		$results = $this->run_against_string('<?php
			// Plugin Name: WooCommerce Example String
		' );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'trademarked_term', 'needle' => 'woocommerce' ] );

		$results = $this->run_against_string('<?php
			// Plugin Name: Example String for WooCommere
		' );

		$this->assertNotHasErrorType( $results, [ 'type' => 'error', 'code' => 'trademarked_term' ] );

		$results = $this->run_against_string('<?php
			// Plugin Name: WooCommerce ExampleString for WooCommere
		' );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'trademarked_term', 'needle' => 'woocommerce' ] );

		// NOTE: This test set is dependent upon the order of the trademarks; WooCommerce must be before WordPress
		$results = $this->run_against_string('<?php
			// Plugin Name: WordPress ExampleString for WooCommere
		' );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'trademarked_term', 'needle' => 'wordpress' ] );
	}

	public function test_portmanteaus() {
		$results = $this->run_against_string('<?php
			// Plugin Name: WooXample
		');

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'trademarked_term', 'needle' => 'woo' ] );
	}
}
