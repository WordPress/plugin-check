<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 */

namespace WordPressVIPMinimum\Sniffs\Security;

use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * Looks for instances of unescaped output for Twig templating engine.
 */
class TwigSniff extends Sniff {

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var string[]
	 */
	public $supportedTokenizers = [ 'JS', 'PHP' ];

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [
			T_CONSTANT_ENCAPSED_STRING,
			T_INLINE_HTML,
			T_HEREDOC,
		];
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {

		if ( preg_match( '/autoescape\s+false/', $this->tokens[ $stackPtr ]['content'] ) === 1 ) {
			// Twig autoescape disabled.
			$message = 'Found Twig autoescape disabling notation.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'AutoescapeFalse' );
		}

		if ( preg_match( '/\|\s*raw/', $this->tokens[ $stackPtr ]['content'] ) === 1 ) {
			// Twig default unescape filter.
			$message = 'Found Twig default unescape filter: "|raw".';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'RawFound' );
		}
	}
}
