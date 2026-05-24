<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Functions;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\TextStrings;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * This sniff enforces that certain functions are not dynamically called.
 *
 * An example:
 * ```php
 *   $func = 'func_num_args';
 *   $func();
 * ```
 *
 * Note that this sniff does not catch all possible forms of dynamic calling, only some.
 *
 * @link http://php.net/manual/en/migration71.incompatible.php
 */
class DynamicCallsSniff extends Sniff {

	/**
	 * Functions that should not be called dynamically.
	 *
	 * @var array
	 */
	private $disallowed_functions = [
		'assert'           => true,
		'compact'          => true,
		'extract'          => true,
		'func_get_args'    => true,
		'func_get_arg'     => true,
		'func_num_args'    => true,
		'get_defined_vars' => true,
		'mb_parse_str'     => true,
		'parse_str'        => true,
	];

	/**
	 * Array of variable assignments encountered, along with their values.
	 *
	 * Populated at run-time.
	 *
	 * @var array The key is the name of the variable, the value, its assigned value.
	 */
	private $variables_arr = [];

	/**
	 * The position in the stack where the token was found.
	 *
	 * @var int
	 */
	private $stackPtr;

	/**
	 * Returns the token types that this sniff is interested in.
	 *
	 * @return array(int)
	 */
	public function register() {
		return [ T_VARIABLE => T_VARIABLE ];
	}

	/**
	 * Processes the tokens that this sniff is interested in.
	 *
	 * @param int $stackPtr The position in the stack where the token was found.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {
		$this->stackPtr = $stackPtr;

		// First collect all variables encountered and their values.
		$this->collect_variables();

		// Then find all dynamic calls, and report them.
		$this->find_dynamic_calls();
	}

	/**
	 * Finds any variable-definitions in the file being processed and stores them
	 * internally in a private array.
	 *
	 * @return void
	 */
	private function collect_variables() {

		$current_var_name = $this->tokens[ $this->stackPtr ]['content'];

		/*
		 * Find assignments ( $foo = "bar"; ) by finding all non-whitespaces,
		 * and checking if the first one is T_EQUAL.
		 */
		$t_item_key = $this->phpcsFile->findNext(
			Tokens::$emptyTokens,
			$this->stackPtr + 1,
			null,
			true,
			null,
			true
		);

		if ( $t_item_key === false || $this->tokens[ $t_item_key ]['code'] !== T_EQUAL ) {
			return;
		}

		/*
		 * Find assignments which only assign a plain text string.
		 */
		$end_of_statement = $this->phpcsFile->findNext( [ T_SEMICOLON, T_CLOSE_TAG ], ( $t_item_key + 1 ) );
		$value_ptr        = null;

		for ( $i = $t_item_key + 1; $i < $end_of_statement; $i++ ) {
			if ( isset( Tokens::$emptyTokens[ $this->tokens[ $i ]['code'] ] ) === true ) {
				continue;
			}

			if ( $this->tokens[ $i ]['code'] !== T_CONSTANT_ENCAPSED_STRING ) {
				// Not a plain text string value. Value cannot be determined reliably.
				return;
			}

			$value_ptr = $i;
		}

		if ( isset( $value_ptr ) === false ) {
			// Parse error. Bow out.
			return;
		}

		/*
		 * If we reached the end of the loop and the $value_ptr was set, we know for sure
		 * this was a plain text string variable assignment.
		 */
		$current_var_value = TextStrings::stripQuotes( $this->tokens[ $value_ptr ]['content'] );

		if ( isset( $this->disallowed_functions[ $current_var_value ] ) === false ) {
			// Text string is not one of the ones we're looking for.
			return;
		}

		/*
		 * Register the variable name and value in the internal array for later usage.
		 */
		$this->variables_arr[ $current_var_name ] = $current_var_value;
	}

	/**
	 * Find any dynamic calls being made using variables.
	 *
	 * Report on this when found, using the name of the function in the message.
	 *
	 * @return void
	 */
	private function find_dynamic_calls() {
		// No variables detected; no basis for doing anything.
		if ( empty( $this->variables_arr ) ) {
			return;
		}

		/*
		 * If variable is not found in our registry of variables, do nothing, as we cannot be
		 * sure that the function being called is one of the disallowed ones.
		 */
		if ( ! isset( $this->variables_arr[ $this->tokens[ $this->stackPtr ]['content'] ] ) ) {
			return;
		}

		/*
		 * Check if we have an '(' next.
		 */
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $this->stackPtr + 1 ), null, true );
		if ( $next === false || $this->tokens[ $next ]['code'] !== T_OPEN_PARENTHESIS ) {
			return;
		}

		$message = 'Dynamic calling is not recommended in the case of %s().';
		$data    = [ $this->variables_arr[ $this->tokens[ $this->stackPtr ]['content'] ] ];
		$this->phpcsFile->addError( $message, $this->stackPtr, 'DynamicCalls', $data );
	}
}
