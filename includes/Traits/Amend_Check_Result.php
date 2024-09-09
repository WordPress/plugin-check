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
 * @since 1.0.0
 */
trait Amend_Check_Result {

	use File_Editor_URL;

	/**
	 * Amends the given result with a message for the specified file, including error information.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result   The check result to amend, including the plugin context to check.
	 * @param bool         $error    Whether it is an error or notice.
	 * @param string       $message  Error message.
	 * @param string       $code     Error code.
	 * @param string       $file     Absolute path to the file where the issue was found.
	 * @param int          $line     The line on which the message occurred. Default is 0 (unknown line).
	 * @param int          $column   The column on which the message occurred. Default is 0 (unknown column).
	 * @param string       $docs     URL for further information about the message.
	 * @param int          $severity Severity level. Default is 5.
	 */
	protected function add_result_message_for_file( Check_Result $result, $error, $message, $code, $file, $line = 0, $column = 0, string $docs = '', $severity = 5 ) {

		$result->add_message(
			(bool) $error,
			$message,
			array(
				'code'     => $code,
				'file'     => str_replace( $result->plugin()->path(), '', $file ),
				'line'     => $line,
				'column'   => $column,
				'link'     => $this->get_file_editor_url( $result, $file, $line ),
				'docs'     => $docs,
				'severity' => $severity,
			)
		);
	}

	/**
	 * Amends the given result with an error message for the specified file.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result   The check result to amend, including the plugin context to check.
	 * @param string       $message  Error message.
	 * @param string       $code     Error code.
	 * @param string       $file     Absolute path to the file where the error was found.
	 * @param int          $line     The line on which the error occurred. Default is 0 (unknown line).
	 * @param int          $column   The column on which the error occurred. Default is 0 (unknown column).
	 * @param string       $docs     URL for further information about the message.
	 * @param int          $severity Severity level. Default is 5.
	 */
	protected function add_result_error_for_file( Check_Result $result, $message, $code, $file, $line = 0, $column = 0, string $docs = '', $severity = 5 ) {
		$this->add_result_message_for_file( $result, true, $message, $code, $file, $line, $column, $docs, $severity );
	}

	/**
	 * Amends the given result with a warning message for the specified file.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result   The check result to amend, including the plugin context to check.
	 * @param string       $message  Error message.
	 * @param string       $code     Error code.
	 * @param string       $file     Absolute path to the file where the warning was found.
	 * @param int          $line     The line on which the warning occurred. Default is 0 (unknown line).
	 * @param int          $column   The column on which the warning occurred. Default is 0 (unknown column).
	 * @param string       $docs     URL for further information about the message.
	 * @param int          $severity Severity level. Default is 5.
	 */
	protected function add_result_warning_for_file( Check_Result $result, $message, $code, $file, $line = 0, $column = 0, string $docs = '', $severity = 5 ) {
		$this->add_result_message_for_file( $result, false, $message, $code, $file, $line, $column, $docs, $severity );
	}
}
