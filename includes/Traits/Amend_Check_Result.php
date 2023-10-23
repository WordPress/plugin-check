<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Amend_Check_Result
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Trait for amending check results.
 *
 * @since n.e.x.t
 */
trait Amend_Check_Result {

	/**
	 * Amends the given result with a message for the specified file, including error information.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result  The check result to amend, including the plugin context to check.
	 * @param bool         $error   Whether it is an error or notice.
	 * @param string       $message Error message.
	 * @param string       $code    Error code.
	 * @param string       $file    Absolute path to the file where the issue was found.
	 * @param int          $line    The line on which the message occurred. Default is 0 (unknown line).
	 * @param int          $column  The column on which the message occurred. Default is 0 (unknown column).
	 */
	protected function add_result_message_for_file( Check_Result $result, $error, $message, $code, $file, $line = 0, $column = 0 ) {
		$result->add_message(
			(bool) $error,
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
	 * Amends the given result with an error message for the specified file.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result  The check result to amend, including the plugin context to check.
	 * @param string       $message Error message.
	 * @param string       $code    Error code.
	 * @param string       $file    Absolute path to the file where the error was found.
	 * @param int          $line    The line on which the error occurred. Default is 0 (unknown line).
	 * @param int          $column  The column on which the error occurred. Default is 0 (unknown column).
	 */
	protected function add_result_error_for_file( Check_Result $result, $message, $code, $file, $line = 0, $column = 0 ) {
		$this->add_result_message_for_file( $result, true, $message, $code, $file, $line, $column );
	}

	/**
	 * Amends the given result with a warning message for the specified file.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result  The check result to amend, including the plugin context to check.
	 * @param string       $message Error message.
	 * @param string       $code    Error code.
	 * @param string       $file    Absolute path to the file where the warning was found.
	 * @param int          $line    The line on which the warning occurred. Default is 0 (unknown line).
	 * @param int          $column  The column on which the warning occurred. Default is 0 (unknown column).
	 */
	protected function add_result_warning_for_file( Check_Result $result, $message, $code, $file, $line = 0, $column = 0 ) {
		$this->add_result_message_for_file( $result, false, $message, $code, $file, $line, $column );
	}
}
