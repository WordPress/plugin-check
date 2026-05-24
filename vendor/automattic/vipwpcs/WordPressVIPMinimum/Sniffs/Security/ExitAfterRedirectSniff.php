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
 * Require `exit;` being called after wp_redirect and wp_safe_redirect.
 */
class ExitAfterRedirectSniff extends Sniff {

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

		if ( $this->tokens[ $stackPtr ]['content'] !== 'wp_redirect' && $this->tokens[ $stackPtr ]['content'] !== 'wp_safe_redirect' ) {
			return;
		}

		$openBracket = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );

		if ( $this->tokens[ $openBracket ]['code'] !== T_OPEN_PARENTHESIS ) {
			return;
		}

		$next_token = $this->phpcsFile->findNext( array_merge( Tokens::$emptyTokens, [ T_SEMICOLON, T_CLOSE_PARENTHESIS ] ), $this->tokens[ $openBracket ]['parenthesis_closer'] + 1, null, true );

		$message = '`%s()` should almost always be followed by a call to `exit;`.';
		$data    = [ $this->tokens[ $stackPtr ]['content'] ];

		if ( $this->tokens[ $next_token ]['code'] === T_OPEN_CURLY_BRACKET ) {
			$is_exit_in_scope = false;
			for ( $i = $this->tokens[ $next_token ]['scope_opener']; $i <= $this->tokens[ $next_token ]['scope_closer']; $i++ ) {
				if ( $this->tokens[ $i ]['code'] === T_EXIT ) {
					$is_exit_in_scope = true;
				}
			}
			if ( $is_exit_in_scope === false ) {
				$this->phpcsFile->addError( $message, $stackPtr, 'NoExitInConditional', $data );
			}
		} elseif ( $this->tokens[ $next_token ]['code'] !== T_EXIT ) {
			$this->phpcsFile->addError( $message, $stackPtr, 'NoExit', $data );
		}
	}
}
