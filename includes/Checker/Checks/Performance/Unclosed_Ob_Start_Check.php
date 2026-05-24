<?php
/**
 * Class Unclosed_Ob_Start_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Performance;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for unclosed ob_start() calls.
 *
 * @since 1.4.0
 */
class Unclosed_Ob_Start_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.4.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PERFORMANCE );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 1.4.0
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );

		foreach ( $php_files as $file ) {
			$this->check_file_for_unclosed_ob_start( $result, $file );
		}
	}

	/**
	 * Checks a single PHP file for unclosed ob_start calls.
	 *
	 * @since 1.4.0
	 *
	 * @param Check_Result $result The check result.
	 * @param string       $file   Absolute path to the file.
	 */
	private function check_file_for_unclosed_ob_start( Check_Result $result, string $file ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$source = file_get_contents( $file );
		if ( false === $source || '' === $source ) {
			return;
		}

		$tokens = token_get_all( $source );

		$brace_depth             = 0;
		$awaiting_function_brace = false;

		$scope_stack = array(
			array(
				'type'        => 'file',
				'start_depth' => 0,
				'ob_starts'   => array(),
				'ob_closes'   => array(),
			),
		);

		foreach ( $tokens as $index => $token ) {
			if ( is_array( $token ) ) {
				$this->process_array_token( $token, $index, $tokens, $brace_depth, $scope_stack, $awaiting_function_brace );
				continue;
			}

			if ( '{' === $token ) {
				if ( $awaiting_function_brace ) {
					$scope_stack[]           = array(
						'type'        => 'function',
						'start_depth' => $brace_depth,
						'ob_starts'   => array(),
						'ob_closes'   => array(),
					);
					$awaiting_function_brace = false;
				}
				++$brace_depth;
			} elseif ( '}' === $token ) {
				--$brace_depth;
				$current_scope = end( $scope_stack );
				if ( 'function' === $current_scope['type'] && $current_scope['start_depth'] === $brace_depth ) {
					$this->process_scope( $result, $file, $current_scope );
					array_pop( $scope_stack );
				}
			}
		}

		// Process any remaining scopes in case of unbalanced braces or top-level file scope.
		while ( count( $scope_stack ) > 0 ) {
			$current_scope = array_pop( $scope_stack );
			$this->process_scope( $result, $file, $current_scope );
		}
	}

	/**
	 * Processes an array token to update function detection state and track ob_start/close calls.
	 *
	 * @since 1.4.0
	 *
	 * @param array $token                   The array token.
	 * @param int   $index                   Token index.
	 * @param array $tokens                  All tokens.
	 * @param int   $brace_depth             Current brace depth.
	 * @param array $scope_stack             The scope stack.
	 * @param bool  $awaiting_function_brace Whether awaiting a function's opening brace.
	 */
	private function process_array_token( array $token, int $index, array &$tokens, int $brace_depth, array &$scope_stack, bool &$awaiting_function_brace ) {
		$token_type = $token[0];

		if ( T_FUNCTION === $token_type ) {
			$awaiting_function_brace = true;
			return;
		}

		if ( T_STRING !== $token_type ) {
			return;
		}

		$next_index = $this->get_next_significant_token_index( $tokens, $index );
		if ( null === $next_index || '(' !== $tokens[ $next_index ] ) {
			return;
		}

		if ( ! $this->is_global_function_call( $tokens, $index ) ) {
			return;
		}

		$name = strtolower( $token[1] );
		$line = (int) $token[2];

		if ( 'ob_start' === $name ) {
			$scope_stack[ count( $scope_stack ) - 1 ]['ob_starts'][] = array(
				'line'  => $line,
				'depth' => $brace_depth,
			);
		} elseif ( in_array( $name, array( 'ob_get_clean', 'ob_end_clean', 'ob_get_flush', 'ob_end_flush' ), true ) ) {
			$scope_stack[ count( $scope_stack ) - 1 ]['ob_closes'][] = array(
				'line'  => $line,
				'depth' => $brace_depth,
			);
		}
	}

	/**
	 * Processes a completed scope to find unpaired ob_start calls.
	 *
	 * @since 1.4.0
	 *
	 * @param Check_Result $result The check result.
	 * @param string       $file   Absolute path to the file.
	 * @param array        $scope  The scope data.
	 */
	private function process_scope( Check_Result $result, string $file, array $scope ) {
		if ( empty( $scope['ob_starts'] ) ) {
			return;
		}

		$unpaired_ob_starts = $scope['ob_starts'];

		foreach ( $scope['ob_closes'] as $close ) {
			// Find the most recent ob_start that is on or before this close's depth.
			for ( $i = count( $unpaired_ob_starts ) - 1; $i >= 0; $i-- ) {
				if ( $close['depth'] <= $unpaired_ob_starts[ $i ]['depth'] ) {
					array_splice( $unpaired_ob_starts, $i, 1 );
					break; // Found the match for this close, move to the next close.
				}
			}
		}

		foreach ( $unpaired_ob_starts as $ob_start ) {
			$this->add_result_warning_for_file(
				$result,
				__( '<code>ob_start()</code> was found without a corresponding closing call (<code>ob_get_clean()</code>, <code>ob_end_clean()</code>, <code>ob_get_flush()</code> or <code>ob_end_flush()</code>) in the same scope. Output buffering is a valid technique, but a buffer must not be left open. WordPress is a shared environment where core, themes and other plugins may also open or close buffers, and a misaligned buffer stack causes unpredictable behaviour (headers already sent, lost output, broken redirects, etc.). Please ensure every <code>ob_start()</code> is paired with a closing function within the same function scope, and that nothing (including hooks or early returns) can bypass that closing logic. If you need to modify the full response output, use the new template enhancement output buffer available since WordPress 6.9.', 'plugin-check' ),
				'unclosed_ob_start',
				$file,
				$ob_start['line'],
				0,
				'https://make.wordpress.org/core/2021/02/10/introducing-template-road-map-and-enhancements-in-wordpress-5-7/'
			);
		}
	}

	/**
	 * Checks whether a tokenized T_STRING is a global function call.
	 *
	 * @since 1.4.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return bool
	 */
	private function is_global_function_call( array $tokens, int $index ): bool {
		$previous_index = $this->get_previous_significant_token_index( $tokens, $index );
		if ( null === $previous_index ) {
			return true;
		}

		$previous_token = $tokens[ $previous_index ];

		if ( is_array( $previous_token ) ) {
			if ( in_array( $previous_token[0], array( T_FUNCTION, T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON ), true ) ) {
				return false;
			}

			if ( T_NS_SEPARATOR === $previous_token[0] ) {
				$before_namespace_index = $this->get_previous_significant_token_index( $tokens, $previous_index );
				if ( null === $before_namespace_index ) {
					return true;
				}

				$before_namespace_token = $tokens[ $before_namespace_index ];
				if ( is_array( $before_namespace_token ) && in_array( $before_namespace_token[0], array( T_STRING, T_NAMESPACE ), true ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Finds the next significant token index.
	 *
	 * @since 1.4.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return int|null
	 */
	private function get_next_significant_token_index( array $tokens, int $index ): ?int {
		$count = count( $tokens );
		for ( $i = $index + 1; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( is_array( $token ) && T_WHITESPACE === $token[0] ) {
				continue;
			}

			return $i;
		}

		return null;
	}

	/**
	 * Finds the previous significant token index.
	 *
	 * @since 1.4.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return int|null
	 */
	private function get_previous_significant_token_index( array $tokens, int $index ): ?int {
		for ( $i = $index - 1; $i >= 0; $i-- ) {
			$token = $tokens[ $i ];

			if ( is_array( $token ) && in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}

			return $i;
		}

		return null;
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.4.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects calls to ob_start() that are not paired with a corresponding buffer-closing function within the same logical scope.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.4.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/', 'plugin-check' );
	}
}
