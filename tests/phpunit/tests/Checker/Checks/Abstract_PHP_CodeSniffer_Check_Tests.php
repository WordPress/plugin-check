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

	// End-to-end verification that filter mutations reach PHPCS is covered by
	// the Behat scenario `plugin-check-phpcs-args-filter.feature` — it runs a
	// real `plugin check` with a sniff added to the `exclude` argument via the
	// filter and asserts the sniff's message disappears from STDOUT.
}
