<?php
/**
 * WordPressVIPMinimum_Sniffs_JS_WindowSniff.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\JS;

use PHP_CodeSniffer\Util\Tokens;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * WordPressVIPMinimum_Sniffs_JS_WindowSniff.
 *
 * Looks for instances of window properties that should be flagged.
 */
class WindowSniff extends Sniff {

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
	 * List of window properties that need to be flagged.
	 *
	 * @var array
	 */
	private $windowProperties = [
		'location' => [
			'href'     => true,
			'protocol' => true,
			'host'     => true,
			'hostname' => true,
			'pathname' => true,
			'search'   => true,
			'hash'     => true,
			'username' => true,
			'port'     => true,
			'password' => true,
		],
		'name'     => true,
		'status'   => true,
	];

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {

		if ( $this->tokens[ $stackPtr ]['content'] !== 'window' ) {
			// Doesn't begin with 'window', bail.
			return;
		}

		$nextTokenPtr = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true );
		$nextToken    = $this->tokens[ $nextTokenPtr ]['code'];
		if ( $nextToken !== T_OBJECT_OPERATOR && $nextToken !== T_OPEN_SQUARE_BRACKET ) {
			// No . or [' next, bail.
			return;
		}

		$nextNextTokenPtr = $this->phpcsFile->findNext( Tokens::$emptyTokens, $nextTokenPtr + 1, null, true, null, true );
		if ( $nextNextTokenPtr === false ) {
			// Something went wrong, bail.
			return;
		}

		$nextNextToken = str_replace( [ '"', "'" ], '', $this->tokens[ $nextNextTokenPtr ]['content'] );
		if ( ! isset( $this->windowProperties[ $nextNextToken ] ) ) {
			// Not in $windowProperties, bail.
			return;
		}

		$nextNextNextTokenPtr = $this->phpcsFile->findNext( array_merge( [ T_CLOSE_SQUARE_BRACKET ], Tokens::$emptyTokens ), $nextNextTokenPtr + 1, null, true, null, true );
		$nextNextNextToken    = $this->tokens[ $nextNextNextTokenPtr ]['code'];

		$nextNextNextNextToken = false;
		if ( $nextNextNextToken === T_OBJECT_OPERATOR || $nextNextNextToken === T_OPEN_SQUARE_BRACKET ) {
			$nextNextNextNextTokenPtr = $this->phpcsFile->findNext( Tokens::$emptyTokens, $nextNextNextTokenPtr + 1, null, true, null, true );
			if ( $nextNextNextNextTokenPtr === false ) {
				// Something went wrong, bail.
				return;
			}

			$nextNextNextNextToken = str_replace( [ '"', "'" ], '', $this->tokens[ $nextNextNextNextTokenPtr ]['content'] );
			if ( ! isset( $this->windowProperties[ $nextNextToken ][ $nextNextNextNextToken ] ) ) {
				// Not in $windowProperties, bail.
				return;
			}
		}

		$windowProperty  = 'window.';
		$windowProperty .= $nextNextNextNextToken ? $nextNextToken . '.' . $nextNextNextNextToken : $nextNextToken;
		$data            = [ $windowProperty ];

		$prevTokenPtr = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true );

		if ( $this->tokens[ $prevTokenPtr ]['code'] === T_EQUAL ) {
			// Variable assignment.
			$message = 'Data from JS global "%s" may contain user-supplied values and should be checked.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'VarAssignment', $data );

			return;
		}

		$message = 'Data from JS global "%s" may contain user-supplied values and should be sanitized before output to prevent XSS.';
		$this->phpcsFile->addError( $message, $stackPtr, $nextNextToken, $data );
	}
}
