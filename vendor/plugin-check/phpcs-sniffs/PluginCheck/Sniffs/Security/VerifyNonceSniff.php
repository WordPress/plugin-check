<?php
/**
 * PluginCheckCS\PluginCheck\Sniffs\Security\VerifyNonceSniff
 *
 * Detects buggy and insecure usage patterns of wp_verify_nonce().
 *
 * @package plugin-check
 * @since 1.7.0
 */

namespace PluginCheckCS\PluginCheck\Sniffs\Security;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Check for buggy/insecure use of wp_verify_nonce()
 *
 * This sniff detects common mistakes when using wp_verify_nonce() that could
 * lead to CSRF vulnerabilities due to improper conditional logic.
 *
 * @since 1.7.0
 */
class VerifyNonceSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public function register() {
		return array_merge( array( T_IF, T_ELSEIF ), Tokens::$functionNameTokens );
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.7.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		// Check for wp_verify_nonce function calls.
		if ( 'wp_verify_nonce' === $tokens[ $stackPtr ]['content'] ) {
			$this->check_unconditional_call( $phpcsFile, $stackPtr, $tokens );
			return;
		}

		// Check for if/elseif conditions.
		if ( ! isset( $tokens[ $stackPtr ]['parenthesis_opener'] ) || ! isset( $tokens[ $stackPtr ]['parenthesis_closer'] ) ) {
			return;
		}

		$opener = $tokens[ $stackPtr ]['parenthesis_opener'];
		$closer = $tokens[ $stackPtr ]['parenthesis_closer'];

		// Find wp_verify_nonce in this condition.
		$noncePtr = $this->find_function_call( $phpcsFile, $opener, $closer, 'wp_verify_nonce' );
		if ( false === $noncePtr ) {
			return;
		}

		// Check if it's negated.
		$isNegated = $this->is_negated( $phpcsFile, $noncePtr, $tokens );

		// Check for the isset combined with negated wp_verify_nonce pattern.
		if ( $isNegated && $this->has_isset_before_and( $phpcsFile, $noncePtr, $opener, $tokens ) ) {
			$this->report_isset_and_negated_nonce( $phpcsFile, $noncePtr );
			return;
		}

		// Check for the negated isset combined with negated wp_verify_nonce pattern.
		if ( $isNegated && $this->has_negated_isset_before_and( $phpcsFile, $noncePtr, $opener, $tokens ) ) {
			$this->report_negated_isset_and_negated_nonce( $phpcsFile, $noncePtr, $stackPtr, $tokens );
			return;
		}

		// Check for $something || wp_verify_nonce() with else that exits.
		if ( ! $isNegated && $this->has_or_before_nonce( $phpcsFile, $noncePtr, $opener, $tokens ) ) {
			$this->check_or_condition_with_else( $phpcsFile, $noncePtr, $stackPtr, $tokens );
		}
	}

	/**
	 * Check for unconditional wp_verify_nonce() call (not in conditional, return, or assignment).
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $stackPtr  The position of the current token.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return void
	 */
	private function check_unconditional_call( File $phpcsFile, $stackPtr, $tokens ) {
		// Check if it's in a conditional expression.
		if ( $this->is_in_conditional( $phpcsFile, $stackPtr, $tokens ) ) {
			return;
		}

		// Check if it's a return statement.
		if ( $this->is_return_statement( $phpcsFile, $stackPtr, $tokens ) ) {
			return;
		}

		// Check if it's an assignment.
		if ( $this->is_assignment( $phpcsFile, $stackPtr, $tokens ) ) {
			return;
		}

		// Check if it's a ternary expression.
		if ( $this->is_in_ternary( $phpcsFile, $stackPtr, $tokens ) ) {
			return;
		}

		$phpcsFile->addError(
			'Unconditional call to wp_verify_nonce(). The return value must be checked. Consider using check_admin_referer() instead, which exits on failure.',
			$stackPtr,
			'UnsafeVerifyNonceStatement'
		);
	}

	/**
	 * Check if the token is in a conditional expression.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $stackPtr  The position of the current token.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function is_in_conditional( File $phpcsFile, $stackPtr, $tokens ) {
		// Check if we're inside an if/elseif/while/for condition.
		$conditions = array( T_IF, T_ELSEIF, T_WHILE, T_FOR, T_FOREACH );

		// Look backward for all open parentheses, not just the first one.
		$current = $stackPtr - 1;
		while ( false !== $current ) {
			$openParen = $phpcsFile->findPrevious( T_OPEN_PARENTHESIS, $current );
			if ( false === $openParen ) {
				break;
			}

			if ( isset( $tokens[ $openParen ]['parenthesis_owner'] ) ) {
				$owner = $tokens[ $openParen ]['parenthesis_owner'];
				if ( in_array( $tokens[ $owner ]['code'], $conditions, true ) ) {
					// Check if we're between the parentheses.
					if ( isset( $tokens[ $openParen ]['parenthesis_closer'] ) && $stackPtr < $tokens[ $openParen ]['parenthesis_closer'] ) {
						return true;
					}
				}
			}

			$current = $openParen - 1;
		}

		return false;
	}

	/**
	 * Check if it's a return statement.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $stackPtr  The position of the current token.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function is_return_statement( File $phpcsFile, $stackPtr, $tokens ) {
		$prev = $phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true );
		return false !== $prev && T_RETURN === $tokens[ $prev ]['code'];
	}

	/**
	 * Check if it's an assignment.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $stackPtr  The position of the current token.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function is_assignment( File $phpcsFile, $stackPtr, $tokens ) {
		// Look backward to see if there's an equals sign before the function call.
		$prev = $phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true );
		if ( false !== $prev && T_EQUAL === $tokens[ $prev ]['code'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if it's in a ternary expression.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $stackPtr  The position of the current token.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function is_in_ternary( File $phpcsFile, $stackPtr, $tokens ) {
		// Look for a ternary operator after the function call.
		$closeParen = $phpcsFile->findNext( T_CLOSE_PARENTHESIS, $stackPtr );
		if ( false === $closeParen ) {
			return false;
		}

		$semicolon = $phpcsFile->findNext( T_SEMICOLON, $closeParen );
		if ( false === $semicolon ) {
			return false;
		}

		for ( $i = $closeParen; $i < $semicolon; $i++ ) {
			if ( T_INLINE_THEN === $tokens[ $i ]['code'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Find a function call within a range.
	 *
	 * @since 1.7.0
	 *
	 * @param File   $phpcsFile    The file being scanned.
	 * @param int    $start        Start position.
	 * @param int    $end          End position.
	 * @param string $function_name Function name to find.
	 *
	 * @return int|false
	 */
	private function find_function_call( File $phpcsFile, $start, $end, $function_name ) {
		$tokens = $phpcsFile->getTokens();

		for ( $i = $start + 1; $i < $end; $i++ ) {
			if ( isset( $tokens[ $i ]['content'] ) && $function_name === $tokens[ $i ]['content'] ) {
				return $i;
			}
		}

		return false;
	}

	/**
	 * Check if wp_verify_nonce() is negated.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $stackPtr  The position of the current token.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function is_negated( File $phpcsFile, $stackPtr, $tokens ) {
		$prev = $phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true );
		return false !== $prev && T_BOOLEAN_NOT === $tokens[ $prev ]['code'];
	}

	/**
	 * Check if there's isset() before an AND operator before the nonce check.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $noncePtr  The position of wp_verify_nonce.
	 * @param int   $opener    The opening parenthesis of the condition.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function has_isset_before_and( File $phpcsFile, $noncePtr, $opener, $tokens ) {
		// Find AND operator before nonce.
		$andPtr = $this->find_operator_before( $phpcsFile, $noncePtr, $opener, $tokens, array( T_BOOLEAN_AND, T_LOGICAL_AND ) );
		if ( false === $andPtr ) {
			return false;
		}

		// Check if there's isset() before the AND.
		$issetPtr = $this->find_function_call( $phpcsFile, $opener, $andPtr, 'isset' );
		return false !== $issetPtr;
	}

	/**
	 * Check if there's !isset() before an AND operator before the nonce check.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $noncePtr  The position of wp_verify_nonce.
	 * @param int   $opener    The opening parenthesis of the condition.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function has_negated_isset_before_and( File $phpcsFile, $noncePtr, $opener, $tokens ) {
		// Find AND operator before nonce.
		$andPtr = $this->find_operator_before( $phpcsFile, $noncePtr, $opener, $tokens, array( T_BOOLEAN_AND, T_LOGICAL_AND ) );
		if ( false === $andPtr ) {
			return false;
		}

		// Check if there's isset() before the AND.
		$issetPtr = $this->find_function_call( $phpcsFile, $opener, $andPtr, 'isset' );
		if ( false === $issetPtr ) {
			return false;
		}

		// Check if isset is negated.
		return $this->is_negated( $phpcsFile, $issetPtr, $tokens );
	}

	/**
	 * Check if there's an OR operator before the nonce check.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $noncePtr  The position of wp_verify_nonce.
	 * @param int   $opener    The opening parenthesis of the condition.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function has_or_before_nonce( File $phpcsFile, $noncePtr, $opener, $tokens ) {
		$orPtr = $this->find_operator_before( $phpcsFile, $noncePtr, $opener, $tokens, array( T_BOOLEAN_OR, T_LOGICAL_OR ) );
		if ( false === $orPtr ) {
			return false;
		}

		// Make sure there's no wp_verify_nonce before the OR.
		$firstNoncePtr = $this->find_function_call( $phpcsFile, $opener, $orPtr, 'wp_verify_nonce' );
		return false === $firstNoncePtr;
	}

	/**
	 * Find an operator before the current position.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile     The file being scanned.
	 * @param int   $stackPtr      The position of the current token.
	 * @param int   $start         Start position to search from.
	 * @param array $tokens        The stack of tokens.
	 * @param array $operatorTypes Array of operator token types to search for.
	 *
	 * @return int|false
	 */
	private function find_operator_before( File $phpcsFile, $stackPtr, $start, $tokens, $operatorTypes ) {
		for ( $i = $stackPtr - 1; $i > $start; $i-- ) {
			if ( in_array( $tokens[ $i ]['code'], $operatorTypes, true ) ) {
				return $i;
			}
		}

		return false;
	}

	/**
	 * Report isset() && !wp_verify_nonce() pattern.
	 *
	 * @since 1.7.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token.
	 *
	 * @return void
	 */
	private function report_isset_and_negated_nonce( File $phpcsFile, $stackPtr ) {
		$phpcsFile->addError(
			'Unsafe use of wp_verify_nonce() with isset() and AND operator. If isset() is false, the nonce is never checked. Use OR operator instead: if ( ! isset(...) || ! wp_verify_nonce(...) )',
			$stackPtr,
			'UnsafeVerifyNonceIssetAnd'
		);
	}

	/**
	 * Report !isset() && !wp_verify_nonce() pattern.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $stackPtr  The position of the current token.
	 * @param int   $condPtr   The position of the if/elseif.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return void
	 */
	private function report_negated_isset_and_negated_nonce( File $phpcsFile, $stackPtr, $condPtr, $tokens ) {
		// Check if the condition scope contains error terminator.
		if ( ! $this->scope_contains_error_terminator( $phpcsFile, $condPtr, $tokens ) ) {
			return;
		}

		$phpcsFile->addError(
			'Unsafe use of wp_verify_nonce() with !isset() and AND operator. If isset() is true (nonce exists), the nonce is never checked. Use OR operator instead: if ( ! isset(...) || ! wp_verify_nonce(...) )',
			$stackPtr,
			'UnsafeVerifyNonceNegatedAnd'
		);
	}

	/**
	 * Check OR condition with else clause.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $stackPtr  The position of wp_verify_nonce.
	 * @param int   $condPtr   The position of the if/elseif.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return void
	 */
	private function check_or_condition_with_else( File $phpcsFile, $stackPtr, $condPtr, $tokens ) {
		// Find the else clause.
		// For if with braces, look after scope_closer.
		// For if without braces, look for the next else token.
		$elsePtr = false;
		if ( isset( $tokens[ $condPtr ]['scope_closer'] ) ) {
			$elsePtr = $phpcsFile->findNext( T_ELSE, $tokens[ $condPtr ]['scope_closer'], null, false );
		} else {
			// If without braces, find the semicolon after the if statement.
			$ifSemicolon = $phpcsFile->findNext( T_SEMICOLON, $condPtr, null, false );
			if ( false !== $ifSemicolon ) {
				$elsePtr = $phpcsFile->findNext( T_ELSE, $ifSemicolon, null, false );
			}
		}

		if ( false === $elsePtr ) {
			return;
		}

		// Check if else scope contains error terminator.
		// Handle else with braces.
		if ( isset( $tokens[ $elsePtr ]['scope_opener'] ) && isset( $tokens[ $elsePtr ]['scope_closer'] ) ) {
			if ( ! $this->scope_contains_error_terminator_in_range( $phpcsFile, $tokens[ $elsePtr ]['scope_opener'], $tokens[ $elsePtr ]['scope_closer'], $tokens ) ) {
				return;
			}
		} else {
			// Handle else without braces (single statement).
			$semicolon = $phpcsFile->findNext( T_SEMICOLON, $elsePtr, null, false );
			if ( false === $semicolon ) {
				return;
			}
			if ( ! $this->scope_contains_error_terminator_in_range( $phpcsFile, $elsePtr, $semicolon, $tokens ) ) {
				return;
			}
		}

		$phpcsFile->addWarning(
			'Possibly unsafe use of wp_verify_nonce() with OR operator. If the condition before || is true, the nonce is never checked. Move nonce verification before the || or use separate conditions.',
			$stackPtr,
			'UnsafeVerifyNonceElse'
		);
	}

	/**
	 * Check if scope contains an error terminator (exit, die, return, etc.).
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $condition The condition pointer.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function scope_contains_error_terminator( File $phpcsFile, $condition, $tokens ) {
		if ( ! isset( $tokens[ $condition ]['scope_opener'] ) || ! isset( $tokens[ $condition ]['scope_closer'] ) ) {
			// Check for single-line if without braces.
			$semicolon = $phpcsFile->findNext( T_SEMICOLON, $condition, null, false );
			if ( false !== $semicolon ) {
				return $this->scope_contains_error_terminator_in_range( $phpcsFile, $condition, $semicolon, $tokens );
			}
			return false;
		}

		return $this->scope_contains_error_terminator_in_range(
			$phpcsFile,
			$tokens[ $condition ]['scope_opener'],
			$tokens[ $condition ]['scope_closer'],
			$tokens
		);
	}

	/**
	 * Check if a range contains an error terminator.
	 *
	 * @since 1.7.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $start     Start position.
	 * @param int   $end       End position.
	 * @param array $tokens    The stack of tokens.
	 *
	 * @return bool
	 */
	private function scope_contains_error_terminator_in_range( File $phpcsFile, $start, $end, $tokens ) {
		$terminators = array(
			'exit',
			'die',
			'wp_send_json_error',
			'wp_nonce_ays',
			'wp_die',
		);

		for ( $i = $start; $i < $end; $i++ ) {
			if ( T_RETURN === $tokens[ $i ]['code'] ) {
				return true;
			}

			if ( isset( $tokens[ $i ]['content'] ) && in_array( $tokens[ $i ]['content'], $terminators, true ) ) {
				return true;
			}
		}

		return false;
	}
}
