<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 */

namespace WordPressVIPMinimum\Sniffs\Security;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\TextStrings;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * Looks for instances of unescaped output for Underscore.js templating engine.
 */
class UnderscorejsSniff extends Sniff {

	/**
	 * Regex to match unescaped output notations containing variable interpolation
	 * and retrieve a code snippet.
	 *
	 * @var string
	 */
	const UNESCAPED_INTERPOLATE_REGEX = '`<%=\s*(?:.+?%>|$)`';

	/**
	 * Regex to match execute notations containing a print command
	 * and retrieve a code snippet.
	 *
	 * @var string
	 */
	const UNESCAPED_PRINT_REGEX = '`<%\s*(?:print\s*\(.+?\)\s*;|__p\s*\+=.+?)\s*%>`';

	/**
	 * Regex to match the "interpolate" keyword when used to overrule the ERB-style delimiters.
	 *
	 * @var string
	 */
	const INTERPOLATE_KEYWORD_REGEX = '`(?:templateSettings\.interpolate|\.interpolate\s*=\s*/|interpolate\s*:\s*/)`';

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
		$targets   = Tokens::$textStringTokens;
		$targets[] = T_PROPERTY;
		$targets[] = T_STRING;

		return $targets;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {
		/*
		 * Ignore Gruntfile.js files as they are configuration, not code.
		 */
		$file_name = TextStrings::stripQuotes( $this->phpcsFile->getFileName() );
		$file_name = strtolower( basename( $file_name ) );

		if ( $file_name === 'gruntfile.js' ) {
			return;
		}

		/*
		 * Check for delimiter change in JS files.
		 */
		if ( $this->tokens[ $stackPtr ]['code'] === T_STRING
			|| $this->tokens[ $stackPtr ]['code'] === T_PROPERTY
		) {
			if ( $this->phpcsFile->tokenizerType !== 'JS' ) {
				// These tokens are only relevant for JS files.
				return;
			}

			if ( $this->tokens[ $stackPtr ]['content'] !== 'interpolate' ) {
				return;
			}

			// Check the context to prevent false positives.
			if ( $this->tokens[ $stackPtr ]['code'] === T_STRING ) {
				$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, ( $stackPtr - 1 ), null, true );
				if ( $prev === false || $this->tokens[ $prev ]['code'] !== T_OBJECT_OPERATOR ) {
					return;
				}

				$prevPrev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, ( $stackPtr - 1 ), null, true );
				$next     = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $stackPtr + 1 ), null, true );
				if ( ( $prevPrev === false
					|| $this->tokens[ $prevPrev ]['code'] !== T_STRING
					|| $this->tokens[ $prevPrev ]['content'] !== 'templateSettings' )
					&& ( $next === false
					|| $this->tokens[ $next ]['code'] !== T_EQUAL )
				) {
					return;
				}
			}

			// Underscore.js delimiter change.
			$message = 'Found Underscore.js delimiter change notation.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'InterpolateFound' );

			return;
		}

		$content = TextStrings::stripQuotes( $this->tokens[ $stackPtr ]['content'] );

		$match_count = preg_match_all( self::UNESCAPED_INTERPOLATE_REGEX, $content, $matches );
		if ( $match_count > 0 ) {
			foreach ( $matches[0] as $match ) {
				if ( strpos( $match, '_.escape(' ) !== false ) {
					continue;
				}

				// Underscore.js unescaped output.
				$message = 'Found Underscore.js unescaped output notation: "%s".';
				$data    = [ $match ];
				$this->phpcsFile->addWarning( $message, $stackPtr, 'OutputNotation', $data );
			}
		}

		$match_count = preg_match_all( self::UNESCAPED_PRINT_REGEX, $content, $matches );
		if ( $match_count > 0 ) {
			foreach ( $matches[0] as $match ) {
				if ( strpos( $match, '_.escape(' ) !== false ) {
					continue;
				}

				// Underscore.js unescaped output.
				$message = 'Found Underscore.js unescaped print execution: "%s".';
				$data    = [ $match ];
				$this->phpcsFile->addWarning( $message, $stackPtr, 'PrintExecution', $data );
			}
		}

		if ( $this->phpcsFile->tokenizerType !== 'JS'
			&& preg_match( self::INTERPOLATE_KEYWORD_REGEX, $content ) > 0
		) {
			// Underscore.js delimiter change.
			$message = 'Found Underscore.js delimiter change notation.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'InterpolateFound' );
		}
	}
}
