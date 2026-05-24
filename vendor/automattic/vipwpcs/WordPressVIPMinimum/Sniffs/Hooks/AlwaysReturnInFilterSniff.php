<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Hooks;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\FunctionDeclarations;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * This sniff validates that filters always return a value
 */
class AlwaysReturnInFilterSniff extends Sniff {

	/**
	 * Filter name pointer.
	 *
	 * @var int
	 */
	private $filterNamePtr;

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

		if ( $functionName !== 'add_filter' ) {
			return;
		}

		$this->filterNamePtr = $this->phpcsFile->findNext(
			array_merge( Tokens::$emptyTokens, [ T_OPEN_PARENTHESIS ] ),
			$stackPtr + 1,
			null,
			true,
			null,
			true
		);

		if ( ! $this->filterNamePtr ) {
			// Something is wrong.
			return;
		}

		$callbackPtr = $this->phpcsFile->findNext(
			array_merge( Tokens::$emptyTokens, [ T_COMMA ] ),
			$this->filterNamePtr + 1,
			null,
			true,
			null,
			true
		);

		if ( ! $callbackPtr ) {
			// Something is wrong.
			return;
		}

		if ( $this->tokens[ $callbackPtr ]['code'] === 'PHPCS_T_CLOSURE' ) {
			$this->processFunctionBody( $callbackPtr );
		} elseif ( $this->tokens[ $callbackPtr ]['code'] === T_ARRAY
			|| $this->tokens[ $callbackPtr ]['code'] === T_OPEN_SHORT_ARRAY
		) {
			$this->processArray( $callbackPtr );
		} elseif ( in_array( $this->tokens[ $callbackPtr ]['code'], Tokens::$stringTokens, true ) === true ) {
			$this->processString( $callbackPtr );
		}
	}

	/**
	 * Process array.
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return void
	 */
	private function processArray( $stackPtr ) {

		$open_close = Arrays::getOpenClose( $this->phpcsFile, $stackPtr );
		if ( $open_close === false ) {
			return;
		}

		$previous = $this->phpcsFile->findPrevious(
			Tokens::$emptyTokens,
			$open_close['closer'] - 1,
			null,
			true
		);

		if ( in_array( T_CLASS, $this->tokens[ $stackPtr ]['conditions'], true ) === true ) {
			$classPtr = array_search( T_CLASS, $this->tokens[ $stackPtr ]['conditions'], true );
			if ( $classPtr ) {
				$classToken = $this->tokens[ $classPtr ];
				$this->processString( $previous, $classToken['scope_opener'], $classToken['scope_closer'] );
				return;
			}
		}

		$this->processString( $previous );
	}

	/**
	 * Process string.
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 * @param int $start    The start of the token.
	 * @param int $end      The end of the token.
	 *
	 * @return void
	 */
	private function processString( $stackPtr, $start = 0, $end = null ) {

		$callbackFunctionName = substr( $this->tokens[ $stackPtr ]['content'], 1, -1 );

		$callbackFunctionPtr = $this->phpcsFile->findNext(
			T_STRING,
			$start,
			$end,
			false,
			$callbackFunctionName
		);

		if ( ! $callbackFunctionPtr ) {
			// We were not able to find the function callback in the file.
			return;
		}

		$this->processFunction( $callbackFunctionPtr, $start, $end );
	}

	/**
	 * Process function.
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 * @param int $start    The start of the token.
	 * @param int $end      The end of the token.
	 *
	 * @return void
	 */
	private function processFunction( $stackPtr, $start = 0, $end = null ) {

		$functionName = $this->tokens[ $stackPtr ]['content'];

		$offset = $start;
		while ( $this->phpcsFile->findNext( [ T_FUNCTION ], $offset, $end ) !== false ) {
			$functionStackPtr = $this->phpcsFile->findNext( [ T_FUNCTION ], $offset, $end );
			$functionNamePtr  = $this->phpcsFile->findNext( Tokens::$emptyTokens, $functionStackPtr + 1, null, true, null, true );
			if ( $this->tokens[ $functionNamePtr ]['code'] === T_STRING && $this->tokens[ $functionNamePtr ]['content'] === $functionName ) {
				$this->processFunctionBody( $functionStackPtr );
				return;
			}
			$offset = $functionStackPtr + 1;
		}
	}

	/**
	 * Process function's body
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return void
	 */
	private function processFunctionBody( $stackPtr ) {

		$filterName = $this->tokens[ $this->filterNamePtr ]['content'];

		$methodProps = FunctionDeclarations::getProperties( $this->phpcsFile, $stackPtr );
		if ( $methodProps['is_abstract'] === true ) {
			$message = 'The callback for the `%s` filter hook-in points to an abstract method. Please ensure that child class implementations of this method always return a value.';
			$data    = [ $filterName ];
			$this->phpcsFile->addWarning( $message, $stackPtr, 'AbstractMethod', $data );
			return;
		}

		if ( isset( $this->tokens[ $stackPtr ]['scope_opener'], $this->tokens[ $stackPtr ]['scope_closer'] ) === false ) {
			// Live coding, parse or tokenizer error.
			return;
		}

		$argPtr = $this->phpcsFile->findNext(
			array_merge( Tokens::$emptyTokens, [ T_STRING, T_OPEN_PARENTHESIS ] ),
			$stackPtr + 1,
			null,
			true,
			null,
			true
		);

		// If arg is being passed by reference, we can skip.
		if ( $this->tokens[ $argPtr ]['code'] === T_BITWISE_AND ) {
			return;
		}

		$functionBodyScopeStart = $this->tokens[ $stackPtr ]['scope_opener'];
		$functionBodyScopeEnd   = $this->tokens[ $stackPtr ]['scope_closer'];

		$returnTokenPtr = $this->phpcsFile->findNext(
			[ T_RETURN ],
			$functionBodyScopeStart + 1,
			$functionBodyScopeEnd
		);

		$outsideConditionalReturn = 0;

		while ( $returnTokenPtr ) {
			if ( $this->isInsideIfConditonal( $returnTokenPtr ) === false ) {
				++$outsideConditionalReturn;
			}
			if ( $this->isReturningVoid( $returnTokenPtr ) ) {
				$message = 'Please, make sure that a callback to `%s` filter is returning void intentionally.';
				$data    = [ $filterName ];
				$this->phpcsFile->addError( $message, $functionBodyScopeStart, 'VoidReturn', $data );
			}
			$returnTokenPtr = $this->phpcsFile->findNext(
				[ T_RETURN ],
				$returnTokenPtr + 1,
				$functionBodyScopeEnd
			);
		}

		if ( $outsideConditionalReturn === 0 ) {
			$message = 'Please, make sure that a callback to `%s` filter is always returning some value.';
			$data    = [ $filterName ];
			$this->phpcsFile->addError( $message, $functionBodyScopeStart, 'MissingReturnStatement', $data );
		}
	}

	/**
	 * Is the current token inside a conditional?
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return bool
	 */
	private function isInsideIfConditonal( $stackPtr ) {

		// This check helps us in situations a class or a function is wrapped
		// inside a conditional as a whole. Eg.: inside `class_exists`.
		if ( end( $this->tokens[ $stackPtr ]['conditions'] ) === T_FUNCTION ) {
			return false;
		}

		// Similar case may be a conditional closure.
		if ( end( $this->tokens[ $stackPtr ]['conditions'] ) === 'PHPCS_T_CLOSURE' ) {
			return false;
		}

		// Loop over the array of conditions and look for an IF.
		reset( $this->tokens[ $stackPtr ]['conditions'] );

		if ( array_key_exists( 'conditions', $this->tokens[ $stackPtr ] ) === true
			&& is_array( $this->tokens[ $stackPtr ]['conditions'] ) === true
			&& empty( $this->tokens[ $stackPtr ]['conditions'] ) === false
		) {
			foreach ( $this->tokens[ $stackPtr ]['conditions'] as $tokenPtr => $tokenCode ) {
				if ( $this->tokens[ $stackPtr ]['conditions'][ $tokenPtr ] === T_IF ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Is the token returning void
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return bool
	 **/
	private function isReturningVoid( $stackPtr ) {

		$nextToReturnTokenPtr = $this->phpcsFile->findNext(
			[ Tokens::$emptyTokens ],
			$stackPtr + 1,
			null,
			true
		);

		return $this->tokens[ $nextToReturnTokenPtr ]['code'] === T_SEMICOLON;
	}
}
