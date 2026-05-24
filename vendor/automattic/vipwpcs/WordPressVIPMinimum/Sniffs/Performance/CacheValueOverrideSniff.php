<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Performance;

use PHP_CodeSniffer\Util\Tokens;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * This sniff check whether a cached value is being overridden.
 */
class CacheValueOverrideSniff extends Sniff {

	/**
	 * Returns the token types that this sniff is interested in.
	 *
	 * @return array(int)
	 */
	public function register() {
		return [ T_STRING ];
	}


	/**
	 * Processes the tokens that this sniff is interested in.
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {

		$functionName = $this->tokens[ $stackPtr ]['content'];

		if ( $functionName !== 'wp_cache_get' ) {
			// Not a function we are looking for.
			return;
		}

		if ( $this->isFunctionCall( $stackPtr ) === false ) {
			// Not a function call.
			return;
		}

		$variablePos = $this->isVariableAssignment( $stackPtr );

		if ( $variablePos === false ) {
			// Not a variable assignment.
			return;
		}

		$variableToken = $this->tokens[ $variablePos ];
		$variableName  = $variableToken['content'];

		// Find the next non-empty token.
		$openBracket = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );

		// Find the closing bracket.
		$closeBracket = $this->tokens[ $openBracket ]['parenthesis_closer'];

		$nextVariableOccurrence = $this->phpcsFile->findNext( T_VARIABLE, $closeBracket + 1, null, false, $variableName );

		$rightAfterNextVariableOccurence = $this->phpcsFile->findNext( Tokens::$emptyTokens, $nextVariableOccurrence + 1, null, true, null, true );

		if ( $this->tokens[ $rightAfterNextVariableOccurence ]['code'] !== T_EQUAL ) {
			// Not a value override.
			return;
		}

		$valueAfterEqualSign = $this->phpcsFile->findNext( Tokens::$emptyTokens, $rightAfterNextVariableOccurence + 1, null, true, null, true );

		if ( $this->tokens[ $valueAfterEqualSign ]['code'] === T_FALSE ) {
			$message = 'Obtained cached value in `%s` is being overridden. Disabling caching?';
			$data    = [ $variableName ];
			$this->phpcsFile->addError( $message, $nextVariableOccurrence, 'CacheValueOverride', $data );
		}
	}

	/**
	 * Check whether the examined code is a function call.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 *
	 * @return bool
	 */
	private function isFunctionCall( $stackPtr ) {

		// Find the next non-empty token.
		$openBracket = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );

		if ( $this->tokens[ $openBracket ]['code'] !== T_OPEN_PARENTHESIS ) {
			// Not a function call.
			return false;
		}

		// Find the previous non-empty token.
		$search   = Tokens::$emptyTokens;
		$search[] = T_BITWISE_AND;
		$previous = $this->phpcsFile->findPrevious( $search, $stackPtr - 1, null, true );

		// It's a function definition, not a function call, so return false.
		return ! ( $this->tokens[ $previous ]['code'] === T_FUNCTION );
	}

	/**
	 * Check whether the examined code is a variable assignment.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 *
	 * @return int|false
	 */
	private function isVariableAssignment( $stackPtr ) {

		// Find the previous non-empty token.
		$search   = Tokens::$emptyTokens;
		$search[] = T_BITWISE_AND;
		$previous = $this->phpcsFile->findPrevious( $search, $stackPtr - 1, null, true );

		if ( $this->tokens[ $previous ]['code'] !== T_EQUAL ) {
			// It's not a variable assignment.
			return false;
		}

		$previous = $this->phpcsFile->findPrevious( $search, $previous - 1, null, true );

		if ( $this->tokens[ $previous ]['code'] !== T_VARIABLE ) {
			// It's not a variable assignment.
			return false;
		}

		return $previous;
	}
}
