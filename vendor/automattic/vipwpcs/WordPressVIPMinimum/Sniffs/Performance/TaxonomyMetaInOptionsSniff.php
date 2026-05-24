<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 */

namespace WordPressVIPMinimum\Sniffs\Performance;

use PHP_CodeSniffer\Util\Tokens;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * Restricts the implementation of taxonomy term meta via options.
 */
class TaxonomyMetaInOptionsSniff extends Sniff {

	/**
	 * List of options_ functions
	 *
	 * @var array
	 */
	public $option_functions = [
		'get_option',
		'add_option',
		'update_option',
		'delete_option',
	];

	/**
	 * List of possible variable names holding term ID.
	 *
	 * @var array
	 */
	public $taxonomy_term_patterns = [
		'category_id',
		'cat_id',
		'cat',
		'term_id',
		'term',
		'tag_id',
		'tag',
	];

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

		if ( in_array( $this->tokens[ $stackPtr ]['content'], $this->option_functions, true ) === false ) {
			return;
		}

		$openBracket = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );

		if ( $this->tokens[ $openBracket ]['code'] !== T_OPEN_PARENTHESIS ) {
			return;
		}

		$param_ptr = $this->phpcsFile->findNext( Tokens::$emptyTokens, $openBracket + 1, null, true );

		if ( $this->tokens[ $param_ptr ]['code'] === T_DOUBLE_QUOTED_STRING ) {
			foreach ( $this->taxonomy_term_patterns as $taxonomy_term_pattern ) {
				if ( strpos( $this->tokens[ $param_ptr ]['content'], $taxonomy_term_pattern ) !== false ) {
					$this->addPossibleTermMetaInOptionsWarning( $stackPtr );
					return;
				}
			}
		} elseif ( $this->tokens[ $param_ptr ]['code'] === T_CONSTANT_ENCAPSED_STRING ) {

			$string_concat = $this->phpcsFile->findNext( Tokens::$emptyTokens, $param_ptr + 1, null, true );
			if ( $this->tokens[ $string_concat ]['code'] !== T_STRING_CONCAT ) {
				return;
			}

			$variable_name = $this->phpcsFile->findNext( Tokens::$emptyTokens, $string_concat + 1, null, true );
			if ( $this->tokens[ $variable_name ]['code'] !== T_VARIABLE ) {
				return;
			}

			foreach ( $this->taxonomy_term_patterns as $taxonomy_term_pattern ) {
				if ( strpos( $this->tokens[ $variable_name ]['content'], $taxonomy_term_pattern ) !== false ) {
					$this->addPossibleTermMetaInOptionsWarning( $stackPtr );
					return;
				}
			}

			$object_operator = $this->phpcsFile->findNext( Tokens::$emptyTokens, $variable_name + 1, null, true );
			if ( $this->tokens[ $object_operator ]['code'] !== T_OBJECT_OPERATOR ) {
				return;
			}

			$object_property = $this->phpcsFile->findNext( Tokens::$emptyTokens, $object_operator + 1, null, true );
			if ( $this->tokens[ $object_property ]['code'] !== T_STRING ) {
				return;
			}

			foreach ( $this->taxonomy_term_patterns as $taxonomy_term_pattern ) {
				if ( strpos( $this->tokens[ $object_property ]['content'], $taxonomy_term_pattern ) !== false ) {
					$this->addPossibleTermMetaInOptionsWarning( $stackPtr );
					return;
				}
			}
		}
	}

	/**
	 * Helper method for composing the Warning for all possible cases.
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function addPossibleTermMetaInOptionsWarning( $stackPtr ) {
		$message = 'Possible detection of storing taxonomy term meta in options table. Needs manual inspection. All such data should be stored in term_meta.';
		$this->phpcsFile->addWarning( $message, $stackPtr, 'PossibleTermMetaInOptions' );
	}
}
