<?php
/**
 * WordPressVIPMinimum_Sniffs_Files_IncludingFileSniff.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\JS;

use PHP_CodeSniffer\Util\Tokens;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * WordPressVIPMinimum_Sniffs_JS_StrippingTagsSniff.
 *
 * Looks for incorrect way of stripping tags.
 */
class StrippingTagsSniff extends Sniff {

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var string[]
	 */
	public $supportedTokenizers = [ 'JS' ];

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
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {

		if ( $this->tokens[ $stackPtr ]['content'] !== 'html' ) {
			// Looking for html() only.
			return;
		}

		$nextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true );

		if ( $this->tokens[ $nextToken ]['code'] !== T_OPEN_PARENTHESIS ) {
			// Not a function.
			return;
		}

		$afterFunctionCall = $this->phpcsFile->findNext( Tokens::$emptyTokens, $this->tokens[ $nextToken ]['parenthesis_closer'] + 1, null, true, null, true );

		if ( $this->tokens[ $afterFunctionCall ]['code'] !== T_OBJECT_OPERATOR ) {
			return;
		}

		$nextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $afterFunctionCall + 1, null, true, null, true );

		if ( $this->tokens[ $nextToken ]['code'] === T_STRING && $this->tokens[ $nextToken ]['content'] === 'text' ) {
			$message = 'Vulnerable tag stripping approach detected.';
			$this->phpcsFile->addError( $message, $stackPtr, 'VulnerableTagStripping' );
		}
	}
}
