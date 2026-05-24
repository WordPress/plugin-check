<?php
/**
 * WordPress-VIP-Minimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 */

namespace WordPressVIPMinimum\Sniffs\Constants;

use PHP_CodeSniffer\Util\Tokens;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * Restricts usage of some constants.
 */
class RestrictedConstantsSniff extends Sniff {

	/**
	 * List of restricted constant names.
	 *
	 * @var array
	 */
	public $restrictedConstantNames = [
		'A8C_PROXIED_REQUEST',
	];

	/**
	 * List of restricted constant declarations.
	 *
	 * @var array
	 */
	public $restrictedConstantDeclaration = [
		'JETPACK_DEV_DEBUG',
		'WP_CRON_CONTROL_SECRET',
	];

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [
			T_CONSTANT_ENCAPSED_STRING,
			T_STRING,
		];
	}

	/**
	 * Process this test when one of its tokens is encountered
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {

		if ( $this->tokens[ $stackPtr ]['code'] === T_STRING ) {
			$constantName = $this->tokens[ $stackPtr ]['content'];
		} else {
			$constantName = trim( $this->tokens[ $stackPtr ]['content'], "\"'" );
		}

		if ( in_array( $constantName, $this->restrictedConstantNames, true ) === false && in_array( $constantName, $this->restrictedConstantDeclaration, true ) === false ) {
			// Not the constant we are looking for.
			return;
		}

		if ( $this->tokens[ $stackPtr ]['code'] === T_STRING && in_array( $constantName, $this->restrictedConstantNames, true ) === true ) {
			$message = 'Code is touching the `%s` constant. Make sure it\'s used appropriately.';
			$data    = [ $constantName ];
			$this->phpcsFile->addWarning( $message, $stackPtr, 'UsingRestrictedConstant', $data );
			return;
		}

		// Find the previous non-empty token.
		$openBracket = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true );

		if ( $this->tokens[ $openBracket ]['code'] !== T_OPEN_PARENTHESIS ) {
			// Not a function call.
			return;
		}

		if ( isset( $this->tokens[ $openBracket ]['parenthesis_closer'] ) === false ) {
			// Not a function call.
			return;
		}

		// Find the previous non-empty token.
		$search   = Tokens::$emptyTokens;
		$search[] = T_BITWISE_AND;
		$previous = $this->phpcsFile->findPrevious( $search, $openBracket - 1, null, true );
		if ( $this->tokens[ $previous ]['code'] === T_FUNCTION ) {
			// It's a function definition, not a function call.
			return;
		}

		if ( in_array( $this->tokens[ $previous ]['code'], Tokens::$functionNameTokens, true ) === true ) {
			$data = [ $constantName ];
			if ( $this->tokens[ $previous ]['content'] === 'define' ) {
				$message = 'The definition of `%s` constant is prohibited. Please use a different name.';
				$this->phpcsFile->addError( $message, $previous, 'DefiningRestrictedConstant', $data );
			} elseif ( in_array( $constantName, $this->restrictedConstantNames, true ) === true ) {
				$message = 'Code is touching the `%s` constant. Make sure it\'s used appropriately.';
				$this->phpcsFile->addWarning( $message, $previous, 'UsingRestrictedConstant', $data );
			}
		}
	}
}
