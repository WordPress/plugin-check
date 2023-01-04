<?php
/**
 * Tests for the Check_Result class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;

class Check_Result_Tests extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();

		$check_context  = new Check_Context( 'test-plugin/test-plugin.php' );

		$this->check_result = new Check_Result( $check_context );
	}

	public function test_plugin() {
		$this->assertInstanceOf( Check_Context::class, $this->check_result->plugin() );
	}

	public function test_add_message() {
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

		// Tests the filename used as the main key for the message associated with that file.
		$this->assertArrayHasKey( 'test-plugin.php', $warnings );

		// Tests the line number is used as the first key for the filename array.
		$this->assertArrayHasKey( 12, $warnings['test-plugin.php'] );

		// Tests the column is used as the first key for the line number array.
		$this->assertArrayHasKey( 40, $warnings['test-plugin.php'][12] );

		// Tests the column array contains the message details.
		$expected = array(
			'message' => 'Warning message',
			'code'    => 'test_warning',
			'file'    => 'test-plugin/test-plugin.php',
		);

		$this->assertEquals( $expected, $warnings['test-plugin.php'][12][40][0] );
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

		// Tests no errors were added or error count incrememeted.
		$this->assertEmpty( $this->check_result->get_errors() );
		$this->assertEquals( 0, $this->check_result->get_error_count() );

		// Tests the warning exists in the array.
		$expected = array(
			'message' => 'Warning message',
			'code'    => 'test_warning',
			'file'    => 'test-plugin/test-plugin.php',
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

		// Tests no warnings were added or warnings count incrememeted.
		$this->assertEmpty( $this->check_result->get_warnings() );
		$this->assertEquals( 0, $this->check_result->get_warning_count() );

		// Tests the error exists in the array.
		$expected = array(
			'message' => 'Error message',
			'code'    => 'test_error',
			'file'    => 'test-plugin/test-plugin.php',
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
			'message' => 'Error message',
			'code'    => 'test_error',
			'file'    => 'test-plugin/test-plugin.php',
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
			'message' => 'Warning message',
			'code'    => 'test_warning',
			'file'    => 'test-plugin/test-plugin.php',
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
}
