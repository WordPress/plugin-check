<?php
/**
 * Tests for the `wp_plugin_check_phpcs_args` filter wired into
 * `Abstract_PHP_CodeSniffer_Check::run()`.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Checker\Checks\General\I18n_Usage_Check;

class Abstract_PHP_CodeSniffer_Check_Tests extends WP_UnitTestCase {

	public function test_phpcs_args_filter_receives_args_check_and_result() {
		$captured = array();

		add_filter(
			'wp_plugin_check_phpcs_args',
			static function ( $args, $check, $result ) use ( &$captured ) {
				$captured[] = array(
					'args'   => $args,
					'check'  => $check,
					'result' => $result,
				);

				return $args;
			},
			10,
			3
		);

		$check         = new I18n_Usage_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-i18n-usage-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$this->assertCount( 1, $captured, 'Filter should fire exactly once per run().' );
		$this->assertIsArray( $captured[0]['args'] );
		$this->assertArrayHasKey( 'standard', $captured[0]['args'] );
		$this->assertInstanceOf( Abstract_PHP_CodeSniffer_Check::class, $captured[0]['check'] );
		$this->assertSame( $check, $captured[0]['check'] );
		$this->assertSame( $check_result, $captured[0]['result'] );
	}

	public function test_phpcs_args_filter_mutation_affects_check_output() {
		// The fixture's declared Text Domain is `test-plugin-check-errors`. By default
		// `I18n_Usage_Check` sets the runtime-set text_domain to the plugin slug, so the
		// fixture's intentional TextDomainMismatch lines fire. Overriding the filter to
		// accept any text domain should make those mismatches disappear, proving that
		// the filter's return value actually reaches PHPCS.
		add_filter(
			'wp_plugin_check_phpcs_args',
			static function ( $args ) {
				if ( isset( $args['runtime-set']['text_domain'] ) ) {
					// A comma-separated list with the fixture's own domain plus the
					// other domains the fixture references makes everything legal.
					$args['runtime-set']['text_domain'] = 'test-plugin-check-errors, foo, bar, baz';
				}

				return $args;
			}
		);

		$check         = new I18n_Usage_Check();
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-i18n-usage-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check->run( $check_result );

		$this->assertFalse(
			$this->errors_contain_code( $check_result->get_errors(), 'WordPress.WP.I18n.TextDomainMismatch' ),
			'TextDomainMismatch errors should be gone once the filter widens the accepted text domains.'
		);
	}

	/**
	 * Helper: walks the nested errors structure produced by `Check_Result::get_errors()`
	 * and returns true if any entry has the given PHPCS code.
	 *
	 * The structure is `[ file => [ line => [ column => [ index => [ 'code' => ... ] ] ] ] ]`.
	 */
	private function errors_contain_code( array $errors, string $code ): bool {
		foreach ( $errors as $file_errors ) {
			foreach ( $file_errors as $line_errors ) {
				foreach ( $line_errors as $column_errors ) {
					foreach ( $column_errors as $error ) {
						if ( ( $error['code'] ?? '' ) === $code ) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}
}
