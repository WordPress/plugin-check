<?php
/**
 * UnclosedObStartSniff
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Util\Tokens;
use WordPressCS\WordPress\Sniff;

/**
 * Detects calls to ob_start() that are not paired with a corresponding buffer-closing
 * function within the same logical scope.
 *
 * @since 1.4.0
 */
final class UnclosedObStartSniff extends Sniff {

	/**
	 * Buffer-closing functions that pair with ob_start().
	 *
	 * @since 1.4.0
	 *
	 * @var array<string, true>
	 */
	private $closing_functions = array(
		'ob_get_clean' => true,
		'ob_end_clean' => true,
		'ob_get_flush' => true,
		'ob_end_flush' => true,
	);

	/**
	 * Scope-keyed collection of ob_start() and closing calls for the current file.
	 *
	 * Populated during the token walk and evaluated once the file has been
	 * fully processed.
	 *
	 * @since 1.4.0
	 *
	 * @var array<int, array{starts: array<int, array{ptr: int, line: int, conditions: array}>, closes: array<int, array{ptr: int, line: int, conditions: array}>}>
	 */
	private $scopes = array();

	/**
	 * Ordered list of scope start pointers, used to process scopes in source order.
	 *
	 * @since 1.4.0
	 *
	 * @var array<int, int>
	 */
	private $scope_order = array();

	/**
	 * Whether the current file has been finalized (scopes evaluated).
	 *
	 * @since 1.4.0
	 *
	 * @var bool
	 */
	private $finalized = false;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.4.0
	 *
	 * @return array<int>
	 */
	public function register() {
		$tokens = array( T_STRING );

		// T_NAME_FULLY_QUALIFIED was introduced in PHP 8.0 for namespaced names like \ob_start().
		if ( defined( 'T_NAME_FULLY_QUALIFIED' ) ) {
			$tokens[] = constant( 'T_NAME_FULLY_QUALIFIED' );
		}

		return $tokens;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.4.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		$name = $this->get_global_function_name( $stackPtr );
		if ( null === $name ) {
			$this->maybe_finalize( $stackPtr );
			return;
		}

		$lower_name = strtolower( $name );

		if ( 'ob_start' === $lower_name ) {
			$this->record_call( $stackPtr, true );
		} elseif ( isset( $this->closing_functions[ $lower_name ] ) ) {
			$this->record_call( $stackPtr, false );
		}

		$this->maybe_finalize( $stackPtr );
	}

	/**
	 * Finalizes the file once the last registered token has been processed.
	 *
	 * PHPCS only invokes process_token() for tokens returned by register(), so the
	 * final file token (often trailing whitespace) is never visited. This finalizes as
	 * soon as no further registered tokens remain ahead, ensuring all collected calls
	 * are available before pairing.
	 *
	 * @since 1.4.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 */
	private function maybe_finalize( $stackPtr ) {
		if ( $this->finalized ) {
			return;
		}

		$next = $this->phpcsFile->findNext( $this->register(), ( $stackPtr + 1 ) );
		if ( false !== $next ) {
			return;
		}

		$this->finalize_file();
	}

	/**
	 * Resolves the global function name being called at the given pointer, or null if
	 * the token is not a global call to a tracked function.
	 *
	 * Handles both the legacy T_STRING + T_NS_SEPARATOR sequence (PHP < 8.0) and the
	 * single T_NAME_FULLY_QUALIFIED token (PHP >= 8.0) so that \ob_start() in namespaced
	 * code is detected consistently.
	 *
	 * @since 1.4.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return string|null The lowercased function name, or null when not a global call.
	 */
	private function get_global_function_name( $stackPtr ) {
		$token_code = $this->tokens[ $stackPtr ]['code'];

		$name = null;

		if ( T_STRING === $token_code ) {
			$previous = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, ( $stackPtr - 1 ), null, true );
			if ( false === $previous ) {
				return null;
			}

			$previous_code = $this->tokens[ $previous ]['code'];

			$disallowed_previous = array( T_FUNCTION, T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON );

			// T_NULLSAFE_OBJECT_OPERATOR (?->) was introduced in PHP 8.0.
			if ( defined( 'T_NULLSAFE_OBJECT_OPERATOR' ) ) {
				$disallowed_previous[] = constant( 'T_NULLSAFE_OBJECT_OPERATOR' );
			}

			// Skip method calls, constructors and function declarations.
			if ( in_array( $previous_code, $disallowed_previous, true ) ) {
				return null;
			}

			// Handle the legacy \Namespace\func() two-token form.
			if ( T_NS_SEPARATOR === $previous_code ) {
				$before_ns = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, ( $previous - 1 ), null, true );
				if ( false !== $before_ns ) {
					$before_code = $this->tokens[ $before_ns ]['code'];
					if ( in_array( $before_code, array( T_STRING, T_NAMESPACE ), true ) ) {
						return null; // Namespaced call, not a global one.
					}
				}
			}

			$name = $this->tokens[ $stackPtr ]['content'];
		} elseif ( defined( 'T_NAME_FULLY_QUALIFIED' ) && constant( 'T_NAME_FULLY_QUALIFIED' ) === $token_code ) {
			// PHP >= 8.0: \ob_start() tokenizes as a single T_NAME_FULLY_QUALIFIED token.
			$name = ltrim( $this->tokens[ $stackPtr ]['content'], '\\' );
		}

		if ( null === $name ) {
			return null;
		}

		// Must be followed by an opening parenthesis to be a function call.
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $stackPtr + 1 ), null, true );
		if ( false === $next || T_OPEN_PARENTHESIS !== $this->tokens[ $next ]['code'] ) {
			return null;
		}

		return $name;
	}

	/**
	 * Records an ob_start() or closing call against its enclosing scope.
	 *
	 * The file scope is represented by a pointer of -1. Function, method and closure
	 * scopes use the pointer of their opening condition token.
	 *
	 * @since 1.4.0
	 *
	 * @param int  $stackPtr The position of the call.
	 * @param bool $is_start Whether this is an ob_start() (true) or a closing call (false).
	 */
	private function record_call( $stackPtr, $is_start ) {
		$scope_ptr = $this->get_scope_pointer( $stackPtr );

		if ( ! isset( $this->scopes[ $scope_ptr ] ) ) {
			$this->scopes[ $scope_ptr ] = array(
				'starts' => array(),
				'closes' => array(),
			);
			$this->scope_order[]        = $scope_ptr;
		}

		$line       = $this->tokens[ $stackPtr ]['line'];
		$conditions = isset( $this->tokens[ $stackPtr ]['conditions'] ) ? $this->tokens[ $stackPtr ]['conditions'] : array();

		if ( $is_start ) {
			$this->scopes[ $scope_ptr ]['starts'][] = array(
				'ptr'        => $stackPtr,
				'line'       => $line,
				'conditions' => $conditions,
			);
		} else {
			$this->scopes[ $scope_ptr ]['closes'][] = array(
				'ptr'        => $stackPtr,
				'line'       => $line,
				'conditions' => $conditions,
			);
		}
	}

	/**
	 * Gets the scope pointer that owns the token at the given position.
	 *
	 * Returns the pointer of the nearest enclosing function, method or closure, or -1
	 * for the file (top-level) scope.
	 *
	 * @since 1.4.0
	 *
	 * @param int $stackPtr The position of the token.
	 * @return int The scope pointer, or -1 for the file scope.
	 */
	private function get_scope_pointer( $stackPtr ) {
		if ( empty( $this->tokens[ $stackPtr ]['conditions'] ) ) {
			return -1;
		}

		$conditions = array_reverse( $this->tokens[ $stackPtr ]['conditions'], true );
		foreach ( $conditions as $condition_ptr => $condition_code ) {
			if ( in_array( $condition_code, array( T_FUNCTION, T_CLOSURE ), true ) ) {
				return $condition_ptr;
			}
		}

		return -1;
	}

	/**
	 * Evaluates collected scopes and reports unpaired ob_start() calls.
	 *
	 * Called once when the end of the file is reached so that the full scope contents
	 * are available for pairing.
	 *
	 * @since 1.4.0
	 */
	private function finalize_file() {
		$this->finalized = true;

		foreach ( $this->scope_order as $scope_ptr ) {
			$scope = $this->scopes[ $scope_ptr ];
			if ( empty( $scope['starts'] ) ) {
				continue;
			}

			$unpaired = $this->pair_starts_and_closes( $scope['starts'], $scope['closes'] );

			foreach ( $unpaired as $start ) {
				if ( $this->is_hook_paired( $start['ptr'] ) ) {
					continue;
				}

				$this->phpcsFile->addWarning(
					'ob_start() was found without a corresponding closing call (ob_get_clean(), ob_end_clean(), ob_get_flush() or ob_end_flush()) in the same scope. Output buffering is a valid technique, but a buffer must not be left open. WordPress is a shared environment where core, themes and other plugins may also open or close buffers, and a misaligned buffer stack causes unpredictable behaviour (headers already sent, lost output, broken redirects, etc.). Please ensure every ob_start() is paired with a closing function within the same function scope, and that nothing (including hooks or early returns) can bypass that closing logic. If you need to modify the full response output, use the new template enhancement output buffer available since WordPress 6.9.',
					$start['ptr'],
					'UnclosedObStart'
				);
			}
		}

		// Reset state so the sniff can be reused across multiple files.
		$this->scopes      = array();
		$this->scope_order = array();
		$this->finalized   = false;
	}

	/**
	 * Pairs ob_start() calls with closing calls within the same scope.
	 *
	 * A closing call pairs with the most recent unpaired ob_start() that appears before
	 * it in source order and that sits at the same nesting level (identical set of
	 * enclosing conditions). This ensures a buffer that is only closed conditionally,
	 * for example inside an if block that may not execute, is treated as unpaired and
	 * flagged.
	 *
	 * ob_start() calls that remain after all closes have been consumed are returned as
	 * unpaired.
	 *
	 * @since 1.4.0
	 *
	 * @param array<int, array{ptr: int, line: int, conditions: array}> $starts The ob_start() calls.
	 * @param array<int, array{ptr: int, line: int, conditions: array}> $closes The closing calls.
	 * @return array<int, array{ptr: int, line: int, conditions: array}> The unpaired ob_start() calls.
	 */
	private function pair_starts_and_closes( array $starts, array $closes ) {
		$unpaired = $starts;

		foreach ( $closes as $close ) {
			for ( $i = ( count( $unpaired ) - 1 ); $i >= 0; $i-- ) {
				$start = $unpaired[ $i ];

				if ( $close['ptr'] <= $start['ptr'] ) {
					continue;
				}

				// Only pair when both calls share the exact same nesting context, so a
				// close inside a conditional block does not silently pair with a start
				// at the enclosing level.
				if ( $this->same_nesting( $start['conditions'], $close['conditions'] ) ) {
					array_splice( $unpaired, $i, 1 );
					break;
				}
			}
		}

		return $unpaired;
	}

	/**
	 * Determines whether two condition sets represent the same nesting level.
	 *
	 * Compares the full set of enclosing condition pointers and their token codes, so
	 * that two calls in different conditional branches (same depth, different blocks)
	 * are not treated as nested identically.
	 *
	 * @since 1.4.0
	 *
	 * @param array $a The first set of conditions (token pointer => token code).
	 * @param array $b The second set of conditions (token pointer => token code).
	 * @return bool True when both calls sit at the same nesting level.
	 */
	private function same_nesting( array $a, array $b ) {
		return $a === $b;
	}

	/**
	 * Best-effort heuristic for hook-paired buffers that should not be flagged.
	 *
	 * Per issue #1314, an ob_start() that is intentionally closed via a hook callback
	 * registered immediately next to it, in a way that is statically obvious, should
	 * not be considered an issue. This checks whether the statement immediately
	 * following the ob_start() is a call to add_action() or add_filter(), which is the
	 * statically obvious signal that the buffer is managed by a hooked callback.
	 *
	 * This is intentionally conservative: it only suppresses the warning when the hook
	 * registration directly follows the ob_start() call, to avoid masking genuinely
	 * unclosed buffers. Resolving the registered callback and verifying it closes the
	 * buffer is out of scope for static analysis.
	 *
	 * @since 1.4.0
	 *
	 * @param int $start_ptr The position of the ob_start() call.
	 * @return bool True when the buffer appears to be closed via an adjacent hook callback.
	 */
	private function is_hook_paired( $start_ptr ) {
		$next_statement = $this->phpcsFile->findEndOfStatement( $start_ptr );
		if ( false === $next_statement ) {
			return false;
		}

		$hook_ptr = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $next_statement + 1 ), null, true );
		if ( false === $hook_ptr ) {
			return false;
		}

		$name = $this->get_global_function_name( $hook_ptr );
		if ( null === $name ) {
			return false;
		}

		return in_array( strtolower( $name ), array( 'add_action', 'add_filter' ), true );
	}
}
