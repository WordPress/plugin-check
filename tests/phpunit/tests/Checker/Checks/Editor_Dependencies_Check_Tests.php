<?php

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Performance\Editor_Dependencies_Check;

class Editor_Dependencies_Check_Tests extends WP_UnitTestCase {

	public function test_dependencies() {
		foreach ( self::data_dependencies() as $name => $scenario ) {
			$dependencies = $scenario[0];
			$expected     = $scenario[1];
			$transitive   = $scenario[2] ?? false;
			$check        = new Test_Editor_Dependencies_Check();
			$result       = new Check_Result( new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE ) );
			$scripts      = new WP_Scripts();
			$source       = $result->plugin()->url() . 'assets/example.js?ver=1#test';
			if ( $transitive ) {
				$scripts->add( 'bridge', false, $dependencies );
				$dependencies = array( 'bridge' );
			}
			$scripts->add( 'plugin-script', $source, $dependencies );
			$scripts->done = array( 'plugin-script' );
			$check->inspect_scripts( $result, $scripts );
			$check->inspect_scripts( $result, $scripts );

			$this->assertSame( 0, $result->get_error_count() );
			$this->assertSame( $expected, $result->get_warning_count(), $name );
			if ( $expected ) {
				$this->assertStringContainsString( 'EditorDependencies.AdminEntryPoint', wp_json_encode( $result->get_warnings() ) );
				$check->run( $result ); // A new run must reset duplicate suppression.
				$check->inspect_scripts( $result, $scripts );
				$this->assertSame( 2, $result->get_warning_count() );
			}
		}
	}

	public static function data_dependencies() {
		return array(
			'post editor'           => array( array( 'wp-edit-post' ), 1 ),
			'site editor'           => array( array( 'wp-edit-site' ), 1 ),
			'multiple entry points' => array( array( 'wp-edit-site', 'wp-edit-post' ), 1 ),
			'transitive dependency' => array( array( 'wp-edit-post' ), 1, true ),
			'frontend block editor' => array( array( 'wp-block-editor' ), 0 ),
			'frontend rich text'    => array( array( 'wp-editor' ), 0 ),
			'ordinary dependency'   => array( array( 'jquery' ), 0 ),
			'missing dependency'    => array( array( 'unknown-handle' ), 0 ),
			'no dependencies'       => array( array(), 0 ),
		);
	}

	public function test_cycles_and_unprinted_scripts_are_ignored() {
		$check   = new Test_Editor_Dependencies_Check();
		$result  = new Check_Result( new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE ) );
		$scripts = new WP_Scripts();
		$scripts->add( 'a', false, array( 'b' ) );
		$scripts->add( 'b', false, array( 'a' ) );
		$scripts->add( 'cycle', $result->plugin()->url() . 'cycle.js', array( 'a' ) );
		$scripts->add( 'unprinted', $result->plugin()->url() . 'unprinted.js', array( 'wp-edit-post' ) );
		$scripts->done = array( 'cycle' );
		$check->inspect_scripts( $result, $scripts );
		$this->assertSame( 0, $result->get_warning_count() );
	}

	public function test_other_origins_siblings_and_aliases_are_ignored() {
		$check   = new Test_Editor_Dependencies_Check();
		$result  = new Check_Result( new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE ) );
		$scripts = new WP_Scripts();
		$base    = rtrim( $result->plugin()->url(), '/' );
		$path    = wp_parse_url( $base, PHP_URL_PATH );
		$sources = array(
			'https://unrelated.invalid' . $path . '/external.js',
			$base . '-other/sibling.js',
			$base . '/../other/traversal.js',
			$base . '/%2e%2e/other/encoded.js',
			false,
		);
		foreach ( $sources as $index => $source ) {
			$handle = 'foreign-' . $index;
			$scripts->add( $handle, $source, array( 'wp-edit-post' ) );
			$scripts->done[] = $handle;
		}
		$check->inspect_scripts( $result, $scripts );
		$this->assertSame( 0, $result->get_warning_count() );
	}

	public function test_root_relative_sources_belong_to_the_plugin() {
		$check   = new Test_Editor_Dependencies_Check();
		$result  = new Check_Result( new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE ) );
		$scripts = new WP_Scripts();
		$path    = wp_parse_url( $result->plugin()->url(), PHP_URL_PATH );
		$scripts->add( 'relative', $path . 'relative.js', array( 'wp-edit-site' ) );
		$scripts->done = array( 'relative' );
		$check->inspect_scripts( $result, $scripts );
		$this->assertSame( 1, $result->get_warning_count() );
	}

	public function test_check_is_experimental() {
		$check = new Editor_Dependencies_Check();
		$this->assertSame( $check::STABILITY_EXPERIMENTAL, $check->get_stability() );
	}

	public function test_url_scheme_and_effective_port_are_part_of_script_ownership() {
		$check   = new Test_Editor_Dependencies_Check();
		$result  = new Check_Result( new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE ) );
		$base    = $result->plugin()->url();
		$scheme  = wp_parse_url( $base, PHP_URL_SCHEME );
		$host    = wp_parse_url( $base, PHP_URL_HOST );
		$port    = wp_parse_url( $base, PHP_URL_PORT );
		$path    = wp_parse_url( $base, PHP_URL_PATH );
		$port    = null === $port ? ( 'https' === $scheme ? 443 : 80 ) : $port;
		$other   = 'https' === $scheme ? 'http' : 'https';
		$sources = array(
			$scheme . '://' . $host . ':' . $port . $path . 'explicit-port.js',
			'//' . $host . ':' . $port . $path . 'protocol-relative.js',
			$other . '://' . $host . ':' . $port . $path . 'other-scheme.js',
			$scheme . '://' . $host . ':' . ( $port + 1 ) . $path . 'other-port.js',
		);
		foreach ( $sources as $index => $source ) {
			$scripts = new WP_Scripts();
			$handle  = 'origin-' . $index;
			$scripts->add( $handle, $source, array( 'wp-edit-post' ) );
			$scripts->done = array( $handle );
			$check->inspect_scripts( $result, $scripts );
		}
		$this->assertSame( 2, $result->get_warning_count() );
	}

	public function test_nested_output_buffers_are_cleaned_after_an_exception() {
		$check            = new Test_Editor_Dependencies_Check();
		$result           = new Check_Result( new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE ) );
		$original_scripts = $GLOBALS['wp_scripts'] ?? null;
		$original_level   = ob_get_level();
		$callback         = static function () {
			ob_start();
			throw new RuntimeException( 'Fixture exception during script collection.' );
		};
		add_action( 'wp_enqueue_scripts', $callback );
		try {
			try {
				$check->inspect_url( $result );
				$this->fail( 'Expected the script collection callback to throw.' );
			} catch ( RuntimeException $exception ) {
				$this->assertSame( 'Fixture exception during script collection.', $exception->getMessage() );
			}
			$this->assertSame( $original_level, ob_get_level() );
		} finally {
			remove_action( 'wp_enqueue_scripts', $callback );
			while ( ob_get_level() > $original_level ) {
				if ( false === ob_end_clean() ) {
					break;
				}
			}
			if ( null === $original_scripts ) {
				unset( $GLOBALS['wp_scripts'] );
			} else {
				$GLOBALS['wp_scripts'] = $original_scripts;
			}
		}
	}
}

class Test_Editor_Dependencies_Check extends Editor_Dependencies_Check {

	public function inspect_scripts( Check_Result $result, WP_Scripts $scripts ) {
		$this->check_scripts( $result, $scripts );
	}

	public function inspect_url( Check_Result $result ) {
		$this->check_url( $result );
	}

	protected function get_urls() {
		return array();
	}
}
