<?php
/**
 * WordPress-VIP-Minimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 */

namespace WordPressVIPMinimum\Sniffs\Constants;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\PassedParameters;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * Sniff for properly using constant name when checking whether a constant is defined.
 */
class ConstantStringSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [
			T_STRING,
		];
	}

	/**
	 * Process this test when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {

		if ( in_array( $this->tokens[ $stackPtr ]['content'], [ 'define', 'defined' ], true ) === false ) {
			return;
		}

		// Find the next non-empty token.
		$nextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true );

		if ( $this->tokens[ $nextToken ]['code'] !== T_OPEN_PARENTHESIS ) {
			// Not a function call.
			return;
		}

		if ( isset( $this->tokens[ $nextToken ]['parenthesis_closer'] ) === false ) {
			// Not a function call.
			return;
		}

		$param = PassedParameters::getParameter( $this->phpcsFile, $stackPtr, 1, 'constant_name' );
		if ( $param === false ) {
			// Target parameter not found.
			return;
		}

		$search             = Tokens::$emptyTokens;
		$search[ T_STRING ] = T_STRING;

		$has_only_tstring = $this->phpcsFile->findNext( $search, $param['start'], $param['end'] + 1, true );
		if ( $has_only_tstring !== false ) {
			// Came across something other than a T_STRING token. Ignore.
			return;
		}

		$tstring_token = $this->phpcsFile->findNext( T_STRING, $param['start'], $param['end'] + 1 );

		$message = 'Constant name, as a string, should be used along with `%s()`.';
		$data    = [ $this->tokens[ $stackPtr ]['content'] ];
		$this->phpcsFile->addError( $message, $tstring_token, 'NotCheckingConstantName', $data );
	}
}
