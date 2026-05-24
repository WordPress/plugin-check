<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Hooks;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Arrays;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * This sniff validates a proper usage of pre_get_posts action callback.
 *
 * It looks for cases when the WP_Query object is being modified without checking for WP_Query::is_main_query().
 */
class PreGetPostsSniff extends Sniff {

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

		if ( $functionName !== 'add_action' ) {
			// We are interested in add_action calls only.
			return;
		}

		$actionNamePtr = $this->phpcsFile->findNext(
			array_merge( Tokens::$emptyTokens, [ T_OPEN_PARENTHESIS ] ),
			$stackPtr + 1,
			null,
			true,
			null,
			true
		);

		if ( ! $actionNamePtr ) {
			// Something is wrong.
			return;
		}

		if ( substr( $this->tokens[ $actionNamePtr ]['content'], 1, -1 ) !== 'pre_get_posts' ) {
			// This is not setting a callback for pre_get_posts action.
			return;
		}

		$callbackPtr = $this->phpcsFile->findNext(
			array_merge( Tokens::$emptyTokens, [ T_COMMA ] ),
			$actionNamePtr + 1,
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
			$this->processClosure( $callbackPtr );
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

		$this->processString( $previous );
	}

	/**
	 * Process string.
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return void
	 */
	private function processString( $stackPtr ) {

		$callbackFunctionName = substr( $this->tokens[ $stackPtr ]['content'], 1, -1 );

		$callbackFunctionPtr = $this->phpcsFile->findNext(
			T_STRING,
			0,
			null,
			false,
			$callbackFunctionName
		);

		if ( ! $callbackFunctionPtr ) {
			// We were not able to find the function callback in the file.
			return;
		}

		$this->processFunction( $callbackFunctionPtr );
	}

	/**
	 * Process function.
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return void
	 */
	private function processFunction( $stackPtr ) {

		$wpQueryObjectNamePtr = $this->phpcsFile->findNext(
			[ T_VARIABLE ],
			$stackPtr + 1,
			null,
			false,
			null,
			true
		);

		if ( ! $wpQueryObjectNamePtr ) {
			// Something is wrong.
			return;
		}

		$wpQueryObjectVariableName = $this->tokens[ $wpQueryObjectNamePtr ]['content'];

		$functionDefinitionPtr = $this->phpcsFile->findPrevious( [ T_FUNCTION ], $wpQueryObjectNamePtr - 1 );

		if ( ! $functionDefinitionPtr ) {
			// Something is wrong.
			return;
		}

		$this->processFunctionBody( $functionDefinitionPtr, $wpQueryObjectVariableName );
	}

	/**
	 * Process closure.
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return void
	 */
	private function processClosure( $stackPtr ) {

		$wpQueryObjectNamePtr = $this->phpcsFile->findNext(
			[ T_VARIABLE ],
			$stackPtr + 1,
			null,
			false,
			null,
			true
		);

		if ( ! $wpQueryObjectNamePtr ) {
			// Something is wrong.
			return;
		}

		$this->processFunctionBody( $stackPtr, $this->tokens[ $wpQueryObjectNamePtr ]['content'] );
	}

	/**
	 * Process function's body
	 *
	 * @param int    $stackPtr     The position in the stack where the token was found.
	 * @param string $variableName Variable name.
	 *
	 * @return void
	 */
	private function processFunctionBody( $stackPtr, $variableName ) {

		$functionBodyScopeStart = $this->tokens[ $stackPtr ]['scope_opener'];
		$functionBodyScopeEnd   = $this->tokens[ $stackPtr ]['scope_closer'];

		$wpQueryVarUsed = $this->phpcsFile->findNext(
			[ T_VARIABLE ],
			$functionBodyScopeStart + 1,
			$functionBodyScopeEnd,
			false,
			$variableName
		);
		while ( $wpQueryVarUsed ) {
			if ( $this->isPartOfIfConditional( $wpQueryVarUsed ) ) {
				if ( $this->isEarlyMainQueryCheck( $wpQueryVarUsed ) ) {
					return;
				}
			} elseif ( $this->isInsideIfConditonal( $wpQueryVarUsed ) ) {
				if ( ! $this->isParentConditionalCheckingMainQuery( $wpQueryVarUsed ) ) {
					$this->addPreGetPostsWarning( $wpQueryVarUsed );
				}
			} elseif ( $this->isWPQueryMethodCall( $wpQueryVarUsed, 'set' ) ) {
				$this->addPreGetPostsWarning( $wpQueryVarUsed );
			}
			$wpQueryVarUsed = $this->phpcsFile->findNext(
				[ T_VARIABLE ],
				$wpQueryVarUsed + 1,
				$functionBodyScopeEnd,
				false,
				$variableName
			);
		}
	}

	/**
	 * Consolidated violation.
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return void
	 */
	private function addPreGetPostsWarning( $stackPtr ) {
		$message = 'Main WP_Query is being modified without `$query->is_main_query()` check. Needs manual inspection.';
		$this->phpcsFile->addWarning( $message, $stackPtr, 'PreGetPosts' );
	}

	/**
	 * Is parent conditional checking is_main_query?
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return bool
	 */
	private function isParentConditionalCheckingMainQuery( $stackPtr ) {

		if ( array_key_exists( 'conditions', $this->tokens[ $stackPtr ] ) === false
			|| is_array( $this->tokens[ $stackPtr ]['conditions'] ) === false
			|| empty( $this->tokens[ $stackPtr ]['conditions'] ) === true
		) {
			return false;
		}

		$conditionStackPtrs    = array_keys( $this->tokens[ $stackPtr ]['conditions'] );
		$lastConditionStackPtr = array_pop( $conditionStackPtrs );

		while ( $this->tokens[ $stackPtr ]['conditions'][ $lastConditionStackPtr ] === T_IF ) {

			$next = $this->phpcsFile->findNext(
				[ T_VARIABLE ],
				$lastConditionStackPtr + 1,
				null,
				false,
				$this->tokens[ $stackPtr ]['content'],
				true
			);
			while ( $next ) {
				if ( $this->isWPQueryMethodCall( $next, 'is_main_query' ) === true ) {
					return true;
				}
				$next = $this->phpcsFile->findNext(
					[ T_VARIABLE ],
					$next + 1,
					null,
					false,
					$this->tokens[ $stackPtr ]['content'],
					true
				);
			}

			$lastConditionStackPtr = array_pop( $conditionStackPtrs );
		}

		return false;
	}


	/**
	 * Is the current code an early main query check?
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return bool
	 */
	private function isEarlyMainQueryCheck( $stackPtr ) {

		if ( ! $this->isWPQueryMethodCall( $stackPtr, 'is_main_query' ) ) {
			return false;
		}

		if ( array_key_exists( 'nested_parenthesis', $this->tokens[ $stackPtr ] ) === false
			|| empty( $this->tokens[ $stackPtr ]['nested_parenthesis'] ) === true
		) {
			return false;
		}

		$parentheses = $this->tokens[ $stackPtr ]['nested_parenthesis'];
		do {
			$nestedParenthesisEnd = array_shift( $parentheses );
			if ( $nestedParenthesisEnd === null ) {
				// Nothing left in the array. No parenthesis found with a non-closure owner.
				return false;
			}

			if ( isset( $this->tokens[ $nestedParenthesisEnd ]['parenthesis_owner'] )
				&& $this->tokens[ $this->tokens[ $nestedParenthesisEnd ]['parenthesis_owner'] ]['code'] !== T_CLOSURE
			) {
				break;
			}
		} while ( true );

		$owner = $this->tokens[ $nestedParenthesisEnd ]['parenthesis_owner'];
		if ( isset( $this->tokens[ $owner ]['scope_opener'], $this->tokens[ $owner ]['scope_closer'] ) === false ) {
			// This may be an inline control structure (no braces).
			$next = $this->phpcsFile->findNext(
				Tokens::$emptyTokens,
				( $nestedParenthesisEnd + 1 ),
				null,
				true
			);

			if ( $next !== false && $this->tokens[ $next ]['code'] === T_RETURN ) {
				return true;
			}

			return false;
		}

		$next = $this->phpcsFile->findNext(
			[ T_RETURN ],
			$this->tokens[ $this->tokens[ $nestedParenthesisEnd ]['parenthesis_owner'] ]['scope_opener'],
			$this->tokens[ $this->tokens[ $nestedParenthesisEnd ]['parenthesis_owner'] ]['scope_closer'],
			false,
			'return',
			true
		);

		if ( $next ) {
			return true;
		}

		return false;
	}

	/**
	 * Is the current code a WP_Query call?
	 *
	 * @param int         $stackPtr The position in the stack where the token was found.
	 * @param string|null $method   Method.
	 *
	 * @return bool
	 */
	private function isWPQueryMethodCall( $stackPtr, $method = null ) {
		$next = $this->phpcsFile->findNext(
			Tokens::$emptyTokens,
			$stackPtr + 1,
			null,
			true,
			null,
			true
		);

		if ( ! $next || $this->tokens[ $next ]['type'] !== 'T_OBJECT_OPERATOR' ) {
			return false;
		}

		if ( $method === null ) {
			return true;
		}

		$next = $this->phpcsFile->findNext(
			Tokens::$emptyTokens,
			$next + 1,
			null,
			true,
			null,
			true
		);

		return $next && $this->tokens[ $next ]['code'] === T_STRING && $method === $this->tokens[ $next ]['content'];
	}

	/**
	 * Is the current token a part of a conditional?
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return bool
	 */
	private function isPartOfIfConditional( $stackPtr ) {

		if ( array_key_exists( 'nested_parenthesis', $this->tokens[ $stackPtr ] ) === true
			&& is_array( $this->tokens[ $stackPtr ]['nested_parenthesis'] ) === true
			&& empty( $this->tokens[ $stackPtr ]['nested_parenthesis'] ) === false
		) {
			$previousLocalIf = $this->phpcsFile->findPrevious(
				[ T_IF ],
				$stackPtr - 1,
				null,
				false,
				null,
				true
			);
			if ( $previousLocalIf !== false
				&& $this->tokens[ $previousLocalIf ]['parenthesis_opener'] < $stackPtr
				&& $this->tokens[ $previousLocalIf ]['parenthesis_closer'] > $stackPtr
			) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is the current token inside a conditional?
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return bool
	 */
	private function isInsideIfConditonal( $stackPtr ) {

		if ( array_key_exists( 'conditions', $this->tokens[ $stackPtr ] ) === true
			&& is_array( $this->tokens[ $stackPtr ]['conditions'] ) === true
			&& empty( $this->tokens[ $stackPtr ]['conditions'] ) === false
		) {
			$conditionStackPtrs    = array_keys( $this->tokens[ $stackPtr ]['conditions'] );
			$lastConditionStackPtr = array_pop( $conditionStackPtrs );
			return $this->tokens[ $stackPtr ]['conditions'][ $lastConditionStackPtr ] === T_IF;
		}
		return false;
	}
}
