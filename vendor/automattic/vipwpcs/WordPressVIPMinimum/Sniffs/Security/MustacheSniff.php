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
 * Looks for instances of unescaped output for Mustache templating engine and Handlebars.js.
 */
class MustacheSniff extends Sniff {

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
			T_STRING,
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

		if ( strpos( $this->tokens[ $stackPtr ]['content'], '{{{' ) !== false && strpos( $this->tokens[ $stackPtr ]['content'], '}}}' ) !== false ) {
			// Mustache unescaped output notation.
			$message = 'Found Mustache unescaped output notation: "{{{}}}".';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'OutputNotation' );
		}

		if ( strpos( $this->tokens[ $stackPtr ]['content'], '{{&' ) !== false ) {
			// Mustache unescaped variable notation.
			$message = 'Found Mustache unescape variable notation: "{{&".';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'VariableNotation' );
		}

		if ( strpos( $this->tokens[ $stackPtr ]['content'], '{{=' ) !== false ) {
			// Mustache delimiter change.
			$new_delimiter = trim( str_replace( [ '{{=', '=}}' ], '', substr( $this->tokens[ $stackPtr ]['content'], 0, strpos( $this->tokens[ $stackPtr ]['content'], '=}}' ) + 3 ) ) );
			$message       = 'Found Mustache delimiter change notation. New delimiter is: %s.';
			$data          = [ $new_delimiter ];
			$this->phpcsFile->addWarning( $message, $stackPtr, 'DelimiterChange', $data );
		}

		if ( strpos( $this->tokens[ $stackPtr ]['content'], 'SafeString' ) !== false ) {
			// Handlebars.js Handlebars.SafeString does not get escaped.
			$message = 'Found Handlebars.SafeString call which does not get escaped.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'SafeString' );
		}
	}
}
