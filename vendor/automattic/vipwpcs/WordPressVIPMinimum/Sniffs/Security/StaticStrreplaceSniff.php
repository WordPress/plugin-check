<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 */

namespace WordPressVIPMinimum\Sniffs\Security;

use PHP_CodeSniffer\Util\Tokens;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * Restricts usage of str_replace with all 3 params being static.
 */
class StaticStrreplaceSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [ T_STRING ];
	}

	/**
	 * Process this test when one of its tokens is encountered
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {

		if ( $this->tokens[ $stackPtr ]['content'] !== 'str_replace' ) {
			return;
		}

		$openBracket = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );

		if ( $this->tokens[ $openBracket ]['code'] !== T_OPEN_PARENTHESIS ) {
			return;
		}

		$next_start_ptr = $openBracket + 1;
		for ( $i = 0; $i < 3; $i++ ) {
			$param_ptr = $this->phpcsFile->findNext( array_merge( Tokens::$emptyTokens, [ T_COMMA ] ), $next_start_ptr, null, true );

			if ( $this->tokens[ $param_ptr ]['code'] === T_ARRAY ) {
				$openBracket = $this->phpcsFile->findNext( Tokens::$emptyTokens, $param_ptr + 1, null, true );
				if ( $this->tokens[ $openBracket ]['code'] !== T_OPEN_PARENTHESIS ) {
					return;
				}

				// Find the closing bracket.
				$closeBracket = $this->tokens[ $openBracket ]['parenthesis_closer'];

				$array_item_ptr = $this->phpcsFile->findNext( array_merge( Tokens::$emptyTokens, [ T_COMMA ] ), $openBracket + 1, $closeBracket, true );
				while ( $array_item_ptr !== false ) {

					if ( $this->tokens[ $array_item_ptr ]['code'] !== T_CONSTANT_ENCAPSED_STRING ) {
						return;
					}
					$array_item_ptr = $this->phpcsFile->findNext( array_merge( Tokens::$emptyTokens, [ T_COMMA ] ), $array_item_ptr + 1, $closeBracket, true );
				}

				$next_start_ptr = $closeBracket + 1;
				continue;

			}

			if ( $this->tokens[ $param_ptr ]['code'] !== T_CONSTANT_ENCAPSED_STRING ) {
				return;
			}

			$next_start_ptr = $param_ptr + 1;

		}

		$message = 'This code pattern is often used to run a very dangerous shell programs on your server. The code in these files needs to be reviewed, and possibly cleaned.';
		$this->phpcsFile->addError( $message, $stackPtr, 'StaticStrreplace' );
	}
}
