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
 * WordPressVIPMinimum_Sniffs_JS_HTMLExecutingFunctions.
 *
 * Flags functions which are executing HTML passed to it.
 */
class HTMLExecutingFunctionsSniff extends Sniff {

	/**
	 * List of HTML executing functions.
	 *
	 * Name of function => content or target.
	 * Value indicates whether the function's arg is the content to be inserted, or the target where the inserted
	 * content is to be inserted before/after/replaced. For the latter, the content is in the preceding method's arg.
	 *
	 * @var array
	 */
	public $HTMLExecutingFunctions = [
		'after'        => 'content', // jQuery.
		'append'       => 'content', // jQuery.
		'appendTo'     => 'target',  // jQuery.
		'before'       => 'content', // jQuery.
		'html'         => 'content', // jQuery.
		'insertAfter'  => 'target',  // jQuery.
		'insertBefore' => 'target',  // jQuery.
		'prepend'      => 'content', // jQuery.
		'prependTo'    => 'target',  // jQuery.
		'replaceAll'   => 'target',  // jQuery.
		'replaceWith'  => 'content', // jQuery.
		'write'        => 'content',
		'writeln'      => 'content',
	];

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

		if ( ! isset( $this->HTMLExecutingFunctions[ $this->tokens[ $stackPtr ]['content'] ] ) ) {
			// Looking for specific functions only.
			return;
		}

		if ( $this->HTMLExecutingFunctions[ $this->tokens[ $stackPtr ]['content'] ] === 'content' ) {
			$nextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true );

			if ( $this->tokens[ $nextToken ]['code'] !== T_OPEN_PARENTHESIS ) {
				// Not a function.
				return;
			}

			$parenthesis_closer = $this->tokens[ $nextToken ]['parenthesis_closer'];

			while ( $nextToken < $parenthesis_closer ) {
				$nextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $nextToken + 1, null, true, null, true );
				if ( $this->tokens[ $nextToken ]['code'] === T_STRING ) { // Contains a variable, function call or something else dynamic.
					$message = 'Any HTML passed to `%s` gets executed. Make sure it\'s properly escaped.';
					$data    = [ $this->tokens[ $stackPtr ]['content'] ];
					$this->phpcsFile->addWarning( $message, $stackPtr, $this->tokens[ $stackPtr ]['content'], $data );

					return;
				}
			}
		} elseif ( $this->HTMLExecutingFunctions[ $this->tokens[ $stackPtr ]['content'] ] === 'target' ) {
			$prevToken = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true );

			if ( $this->tokens[ $prevToken ]['code'] !== T_OBJECT_OPERATOR ) {
				return;
			}

			$prevPrevToken = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $prevToken - 1, null, true, null, true );

			if ( $this->tokens[ $prevPrevToken ]['code'] !== T_CLOSE_PARENTHESIS ) {
				// Not a function call, but may be a variable containing an element reference, so just
				// flag all remaining instances of these target HTML executing functions.
				$message = 'Any HTML used with `%s` gets executed. Make sure it\'s properly escaped.';
				$data    = [ $this->tokens[ $stackPtr ]['content'] ];
				$this->phpcsFile->addWarning( $message, $stackPtr, $this->tokens[ $stackPtr ]['content'], $data );

				return;
			}

			// Check if it's a function call (typically $() ) that contains a dynamic part.
			$parenthesis_opener = $this->tokens[ $prevPrevToken ]['parenthesis_opener'];

			while ( $prevPrevToken > $parenthesis_opener ) {
				$prevPrevToken = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $prevPrevToken - 1, null, true, null, true );
				if ( $this->tokens[ $prevPrevToken ]['code'] === T_STRING ) { // Contains a variable, function call or something else dynamic.
					$message = 'Any HTML used with `%s` gets executed. Make sure it\'s properly escaped.';
					$data    = [ $this->tokens[ $stackPtr ]['content'] ];
					$this->phpcsFile->addWarning( $message, $stackPtr, $this->tokens[ $stackPtr ]['content'], $data );

					return;
				}
			}
		}
	}
}
