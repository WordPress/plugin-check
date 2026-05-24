<?php
/**
 * WordPressVIPMinimum_Sniffs_JS_DangerouslySetInnerHTMLSniff.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\JS;

use PHP_CodeSniffer\Util\Tokens;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * WordPressVIPMinimum_Sniffs_JS_DangerouslySetInnerHTMLSniff.
 *
 * Looks for instances of React's dangerouslySetInnerHMTL.
 */
class DangerouslySetInnerHTMLSniff extends Sniff {

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

		if ( $this->tokens[ $stackPtr ]['content'] !== 'dangerouslySetInnerHTML' ) {
			// Looking for dangerouslySetInnerHTML only.
			return;
		}

		$nextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true );

		if ( $this->tokens[ $nextToken ]['code'] !== T_EQUAL ) {
			// Not an assignment.
			return;
		}

		$nextNextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $nextToken + 1, null, true, null, true );

		if ( $this->tokens[ $nextNextToken ]['code'] !== T_OBJECT ) {
			// Not react syntax.
			return;
		}

		$message = "Any HTML passed to `%s` gets executed. Please make sure it's properly escaped.";
		$data    = [ $this->tokens[ $stackPtr ]['content'] ];
		$this->phpcsFile->addError( $message, $stackPtr, 'Found', $data );
	}
}
