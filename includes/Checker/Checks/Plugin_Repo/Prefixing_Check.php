<?php
/**
 * Class Prefixing_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Prefix_Utils;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for prefixing.
 *
 * @since 1.7.0
 */
class Prefixing_Check extends Abstract_PHP_CodeSniffer_Check {

	use Amend_Check_Result;
	use Prefix_Utils;
	use Stable_Check;

	/**
	 * Cache of file contents for line-level checks.
	 *
	 * @since n.e.x.t
	 * @var array<string, array<int, string>>
	 */
	private $file_line_cache = array();

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.7.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args( Check_Result $result ) {
		$args = array(
			'extensions' => 'php',
			'standard'   => 'WordPress',
			'sniffs'     => 'WordPress.NamingConventions.PrefixAllGlobals',
		);

		$prefixes = $this->get_potential_prefixes( $result );

		if ( ! empty( $prefixes ) ) {
			$args['runtime-set'] = array(
				'prefixes' => implode( ',', $prefixes ),
			);
		}

		return $args;
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.7.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Checks plugin for unique prefixing for everything the plugin defines in the public namespace.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.7.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/#code', 'plugin-check' );
	}

	/**
	 * Amends the given result with a message for the specified file, including error information.
	 *
	 * @since 1.8.0
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
		// Suppress false positives where a variable sits inside a `foreach` `as` clause
		// or a `for` loop initializer at the top level of a file. PHPCS' WPCS sniff
		// treats these as global variable definitions because the enclosing function
		// early-return does not apply to file top-level scope, but they are loop-local.
		if ( 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound' === $code
			&& $this->is_variable_in_loop_control( $file, $line )
		) {
			return;
		}

		// Update error type and severity.
		$error    = false;
		$severity = 6;

		parent::add_result_message_for_file( $result, $error, $message, $code, $file, $line, $column, $docs, $severity );
	}

	/**
	 * Determines whether the variable on the given file/line sits inside a
	 * `foreach` `as` clause or a `for` loop initializer.
	 *
	 * Used to filter out false-positive prefix warnings for loop-local variables
	 * at the top level of a plugin file, where the WPCS sniff's normal
	 * function-scope early-return does not apply.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $file Absolute path to the file.
	 * @param int    $line 1-based line number to inspect.
	 * @return bool True when the variable lives inside a loop control structure.
	 */
	private function is_variable_in_loop_control( $file, $line ) {
		if ( $line <= 0 || ! is_string( $file ) || '' === $file || ! file_exists( $file ) ) {
			return false;
		}

		if ( ! isset( $this->file_line_cache[ $file ] ) ) {
			$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $contents ) {
				return false;
			}
			$this->file_line_cache[ $file ] = preg_split( '/\r\n|\r|\n/', $contents );
		}

		$lines = $this->file_line_cache[ $file ];
		if ( ! isset( $lines[ $line - 1 ] ) ) {
			return false;
		}

		$source = $lines[ $line - 1 ];

		if ( $this->is_in_single_line_loop_header( $source ) ) {
			return true;
		}

		return $this->is_in_multiline_loop_header( $lines, $line );
	}

	/**
	 * Checks whether a single source line contains a `foreach ... as $var` or
	 * `for ( ... $var = ...` loop header.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $source Source line content.
	 * @return bool True when the line is a single-line loop header.
	 */
	private function is_in_single_line_loop_header( $source ) {
		// foreach ( $items as $key => $value ).
		if ( preg_match( '/\bforeach\s*\([^)]*\bas\s+\$/', $source ) ) {
			return true;
		}

		// for ( $i = 0, $j = 10; ... ) — match any `$var =` inside parens.
		if ( preg_match( '/\bfor\s*\([^)]*?\$\w+\s*=/', $source ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks whether the reported line is part of a multi-line `foreach` or
	 * `for` header whose opener sits on a previous line.
	 *
	 * Looks back up to 10 lines for `foreach (` or `for (` with an unclosed
	 * opening parenthesis, then walks forward to the reported line checking
	 * for the `as` keyword (foreach) or `$var =` initializer (for).
	 *
	 * @since n.e.x.t
	 *
	 * @param array<int, string> $lines File contents split by line.
	 * @param int                $line  1-based line number being inspected.
	 * @return bool True when the line is inside a multi-line loop header.
	 */
	private function is_in_multiline_loop_header( $lines, $line ) {
		$min_back = max( 0, $line - 11 );

		for ( $i = $line - 2; $i >= $min_back; $i-- ) {
			if ( ! isset( $lines[ $i ] ) ) {
				continue;
			}
			if ( ! preg_match( '/\b(foreach|for)\s*\(\s*$/', $lines[ $i ] ) ) {
				continue;
			}
			if ( $this->loop_header_contains_variable( $lines, $i + 1, $line ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Walks forward from the loop opener to the reported line and checks
	 * for an `as` keyword (foreach) or `$var =` initializer (for) before
	 * any statement-terminating `;` or paren-closing `)`.
	 *
	 * @since n.e.x.t
	 *
	 * @param array<int, string> $lines File contents split by line.
	 * @param int                $start 1-based line index just after the opener.
	 * @param int                $end   1-based line index of the reported line.
	 * @return bool True when a loop header variable is found.
	 */
	private function loop_header_contains_variable( $lines, $start, $end ) {
		for ( $j = $start; $j <= $end; $j++ ) {
			if ( ! isset( $lines[ $j - 1 ] ) ) {
				continue;
			}
			$scan = $lines[ $j - 1 ];
			if ( preg_match( '/\bas\b/', $scan ) ) {
				return true;
			}
			if ( preg_match( '/\$\w+\s*=/', $scan ) ) {
				return true;
			}
			if ( strpos( $scan, ';' ) !== false ) {
				return false;
			}
			if ( strpos( $scan, ')' ) !== false ) {
				return false;
			}
		}
		return false;
	}
}
