<?php
/**
 * Tests for the Plugin_Check_Command class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\CLI\Plugin_Check_Command;
use WordPress\Plugin_Check\Plugin_Context;

class Plugin_Check_Command_Tests extends WP_UnitTestCase {

	public function test_get_success_message_without_ai_stats() {
		$this->assertSame(
			'Checks complete. No errors found.',
			$this->get_success_message( array() )
		);
	}

	public function test_get_success_message_without_false_positives() {
		$this->assertSame(
			'Checks complete. No errors found.',
			$this->get_success_message(
				array(
					'false_positives' => 0,
					'tokens_spent'    => 500,
				)
			)
		);
	}

	public function test_get_success_message_with_one_false_positive() {
		$this->assertSame(
			'Checks complete. No errors found. AI detected 1 possible false positive.',
			$this->get_success_message( array( 'false_positives' => 1 ) )
		);
	}

	public function test_get_success_message_with_false_positives_and_tokens() {
		$this->assertSame(
			'Checks complete. No errors found. AI detected 3 possible false positives. Tokens spent: 1,234.',
			$this->get_success_message(
				array(
					'false_positives' => 3,
					'tokens_spent'    => 1234,
				)
			)
		);
	}

	public function test_get_success_message_with_one_token() {
		$this->assertSame(
			'Checks complete. No errors found. AI detected 2 possible false positives. Tokens spent: 1.',
			$this->get_success_message(
				array(
					'false_positives' => 2,
					'tokens_spent'    => 1,
				)
			)
		);
	}

	private function get_success_message( array $ai_stats ) {
		$command = new Plugin_Check_Command( new Plugin_Context( WP_PLUGIN_CHECK_MAIN_FILE ) );

		$reflection = new ReflectionClass( $command );
		$method     = $reflection->getMethod( 'get_success_message' );
		$method->setAccessible( true );

		return $method->invoke( $command, $ai_stats );
	}
}
