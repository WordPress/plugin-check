<?php
/**
 * Tests for the Check_Result class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;

class Check_Result_Tests extends WP_UnitTestCase {
	/**
	 * Check_Result instance.
	 *
	 * @var Check_Result
	 */
	protected $check_result;

	public function set_up() {
		parent::set_up();

		$check_context = new Check_Context( 'test-plugin/test-plugin.php' );

		$this->check_result = new Check_Result( $check_context );
	}

	public function test_plugin() {
		$this->assertInstanceOf( Check_Context::class, $this->check_result->plugin() );

		// Check the Check_Context has the correct basename.
		$this->assertSame( 'test-plugin/test-plugin.php', $this->check_result->plugin()->basename() );
	}

	public function test_add_message_with_warning() {
		$this->check_result->add_message(
			false,
			'Warning message',
			array(
				'code'   => 'test_warning',
				'file'   => 'test-plugin/test-plugin.php',
				'line'   => 12,
				'column' => 40,
			)
		);

		$warnings = $this->check_result->get_warnings();

		// Tests that warnings contains an error.
		$this->assertNotEmpty( $warnings );

		// Tests warnings count incremented correctly.
		$this->assertEquals( 1, $this->check_result->get_warning_count() );

		// Tests no errors were added or error count incremented.
		$this->assertEmpty( $this->check_result->get_errors() );
		$this->assertEquals( 0, $this->check_result->get_error_count() );

		// Tests the warning exists in the array.
		$expected = array(
			'message'  => 'Warning message',
			'code'     => 'test_warning',
			'link'     => '',
			'docs'     => '',
			'severity' => 5,
		);

		$this->assertEquals( $expected, $warnings['test-plugin.php'][12][40][0] );
	}

	public function test_add_message_with_error() {
		$this->check_result->add_message(
			true,
			'Error message',
			array(
				'code'   => 'test_error',
				'file'   => 'test-plugin/test-plugin.php',
				'line'   => 22,
				'column' => 30,
			)
		);

		$errors = $this->check_result->get_errors();

		// Tests that errors contains an error.
		$this->assertNotEmpty( $errors );

		// Tests errors count incremented correctly.
		$this->assertEquals( 1, $this->check_result->get_error_count() );

		// Tests no warnings were added or warnings count incremented.
		$this->assertEmpty( $this->check_result->get_warnings() );
		$this->assertEquals( 0, $this->check_result->get_warning_count() );

		// Tests the error exists in the array.
		$expected = array(
			'message'  => 'Error message',
			'code'     => 'test_error',
			'link'     => '',
			'docs'     => '',
			'severity' => 5,
		);

		$this->assertEquals( $expected, $errors['test-plugin.php'][22][30][0] );
	}

	public function test_get_errors() {
		$this->assertEmpty( $this->check_result->get_errors() );
	}

	public function test_get_errors_with_errors() {
		$this->check_result->add_message(
			true,
			'Error message',
			array(
				'code'   => 'test_error',
				'file'   => 'test-plugin/test-plugin.php',
				'line'   => 22,
				'column' => 30,
			)
		);

		$errors = $this->check_result->get_errors();

		// Tests errors are not empty.
		$this->assertNotEmpty( $errors );

		// Tests the error exists in the array.
		$expected = array(
			'message'  => 'Error message',
			'code'     => 'test_error',
			'link'     => '',
			'docs'     => '',
			'severity' => 5,
		);

		$this->assertEquals( $expected, $errors['test-plugin.php'][22][30][0] );
	}

	public function test_get_warnings() {
		$this->assertEmpty( $this->check_result->get_warnings() );
	}

	public function test_get_warnings_with_warnings() {
		$this->check_result->add_message(
			false,
			'Warning message',
			array(
				'code'   => 'test_warning',
				'file'   => 'test-plugin/test-plugin.php',
				'line'   => 22,
				'column' => 30,
			)
		);

		$warnings = $this->check_result->get_warnings();

		// Tests warnings are not empty.
		$this->assertNotEmpty( $warnings );

		// Tests the warning exists in the array.
		$expected = array(
			'message'  => 'Warning message',
			'code'     => 'test_warning',
			'link'     => '',
			'docs'     => '',
			'severity' => 5,
		);

		$this->assertEquals( $expected, $warnings['test-plugin.php'][22][30][0] );
	}

	public function test_get_warning_count() {
		$this->assertEquals( 0, $this->check_result->get_warning_count() );
	}

	public function test_get_warning_count_with_message() {
		$this->check_result->add_message( false, 'Warning message' );

		$this->assertEquals( 1, $this->check_result->get_warning_count() );
	}

	public function test_get_error_count() {
		$this->assertEquals( 0, $this->check_result->get_error_count() );
	}

	public function test_get_error_count_with_message() {
		$this->check_result->add_message( true, 'Error message' );

		$this->assertEquals( 1, $this->check_result->get_error_count() );
	}

	public function test_check_result_filter_receives_data_result_and_is_error_flag() {
		$captured = array();

		add_filter(
			'wp_plugin_check_check_result',
			static function ( $data, $result, $is_error ) use ( &$captured ) {
				$captured[] = array(
					'data'     => $data,
					'result'   => $result,
					'is_error' => $is_error,
				);

				return $data;
			},
			10,
			3
		);

		$this->check_result->add_message(
			true,
			'Error message',
			array(
				'code'   => 'test_error',
				'file'   => 'test-plugin/test-plugin.php',
				'line'   => 22,
				'column' => 30,
			)
		);

		$this->assertCount( 1, $captured );
		$this->assertIsArray( $captured[0]['data'] );
		$this->assertSame( 'Error message', $captured[0]['data']['message'] );
		$this->assertSame( 'test_error', $captured[0]['data']['code'] );
		// File path is normalised before the filter fires.
		$this->assertSame( 'test-plugin.php', $captured[0]['data']['file'] );
		$this->assertSame( 22, $captured[0]['data']['line'] );
		$this->assertSame( 30, $captured[0]['data']['column'] );
		$this->assertSame( $this->check_result, $captured[0]['result'] );
		$this->assertTrue( $captured[0]['is_error'] );
	}

	public function test_check_result_filter_suppresses_entry_when_returning_null() {
		add_filter(
			'wp_plugin_check_check_result',
			static function ( $data ) {
				return ( 'noisy_warning' === ( $data['code'] ?? '' ) ) ? null : $data;
			}
		);

		$this->check_result->add_message(
			false,
			'Noise.',
			array(
				'code' => 'noisy_warning',
				'file' => 'test-plugin/test-plugin.php',
			)
		);
		$this->check_result->add_message(
			false,
			'Real warning.',
			array(
				'code' => 'real_warning',
				'file' => 'test-plugin/test-plugin.php',
			)
		);

		$this->assertEquals( 1, $this->check_result->get_warning_count() );
		$this->assertEquals( 0, $this->check_result->get_error_count() );

		$warnings = $this->check_result->get_warnings();
		$entries  = array();
		foreach ( $warnings as $file_entries ) {
			foreach ( $file_entries as $line_entries ) {
				foreach ( $line_entries as $column_entries ) {
					foreach ( $column_entries as $entry ) {
						$entries[] = $entry['code'] ?? '';
					}
				}
			}
		}
		$this->assertSame( array( 'real_warning' ), $entries );
	}

	public function test_check_result_filter_can_mutate_entry() {
		add_filter(
			'wp_plugin_check_check_result',
			static function ( $data ) {
				if ( 'mutable_warning' === ( $data['code'] ?? '' ) ) {
					$data['message']  = 'Edited by filter.';
					$data['severity'] = 9;
				}

				return $data;
			}
		);

		$this->check_result->add_message(
			false,
			'Original.',
			array(
				'code'   => 'mutable_warning',
				'file'   => 'test-plugin/test-plugin.php',
				'line'   => 1,
				'column' => 1,
			)
		);

		$warnings = $this->check_result->get_warnings();
		$entry    = $warnings['test-plugin.php'][1][1][0];

		$this->assertSame( 'Edited by filter.', $entry['message'] );
		$this->assertSame( 9, $entry['severity'] );
	}
}
