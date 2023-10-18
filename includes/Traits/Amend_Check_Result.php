<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Amend_Check_Result
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Trait for check result.
 *
 * @since n.e.x.t
 */
trait Amend_Check_Result {

	/**
	 * Amends the given result with an error for the given file, code, and message.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result  The check result to amend, including the plugin context to check.
	 * @param string       $message Error message.
	 * @param string       $code    Error code.
	 * @param string       $file    Absolute path to the file found.
	 * @param int          $line    The line on which the message occurred. Default 0 (unknown line).
	 * @param int          $column  The column on which the message occurred. Default 0 (unknown column).
	 */
	protected function add_result_error_for_file( Check_Result $result, $message, $code, $file, $line = 0, $column = 0 ) {
		$result->add_message(
			true,
			$message,
			array(
				'code'   => $code,
				'file'   => str_replace( $result->plugin()->path(), '', $file ),
				'line'   => $line,
				'column' => $column,
			)
		);
	}

	/**
	 * Amends the given result with a warning for the given file, code, and message.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result  The check result to amend, including the plugin context to check.
	 * @param string       $message Error message.
	 * @param string       $code    Error code.
	 * @param string       $file    Absolute path to the file found.
	 * @param int          $line    The line on which the message occurred. Default 0 (unknown line).
	 * @param int          $column  The column on which the message occurred. Default 0 (unknown column).
	 */
	protected function add_result_warning_for_file( Check_Result $result, $message, $code, $file, $line = 0, $column = 0 ) {
		$result->add_message(
			false,
			$message,
			array(
				'code'   => $code,
				'file'   => str_replace( $result->plugin()->path(), '', $file ),
				'line'   => $line,
				'column' => $column,
			)
		);
	}
}
