<?php
/**
 * PHPErrorReportingSniff
 *
 * Detects runtime changes to PHP error reporting configuration and
 * WordPress debug constants. A plugin must never call error_reporting(),
 * ini_set()/ini_alter() for error_reporting/display_errors, or define
 * WP_DEBUG, WP_DEBUG_LOG, WP_DEBUG_DISPLAY, SCRIPT_DEBUG in production.
 *
 * @package plugin-check
 * @since 2.1.0
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\MessageHelper;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Flags PHP error reporting changes and debug constant overrides.
 *
 * @since 2.1.0
 */
final class PHPErrorReportingSniff implements Sniff {

	/**
	 * WordPress debug constants that must never be redefined by a plugin.
	 *
	 * @since 2.1.0
	 * @var string[]
	 */
	private const DEBUG_CONSTANTS = array(
		'WP_DEBUG',
		'WP_DEBUG_LOG',
		'WP_DEBUG_DISPLAY',
		'SCRIPT_DEBUG',
	);

	/**
	 * INI directives that control error reporting.
	 *
	 * @since 2.1.0
	 * @var string[]
	 */
	private const ERROR_INI_DIRECTIVES = array(
		'error_reporting',
		'display_errors',
	);

	/**
	 * Functions to inspect for the error reporting pattern.
	 *
	 * @since 2.1.0
	 * @var string[]
	 */
	private const TARGET_FUNCTIONS = array(
		'error_reporting',
		'ini_set',
		'ini_alter',
		'define',
	);

	/**
	 * Returns the array of tokens this sniff listens for.
	 *
	 * @since 2.1.0
	 *
	 * @return array<int>
	 */
	public function register() {
		return array(
			T_STRING,
			T_CONST,
		);
	}

	/**
	 * Processes a matched token.
	 *
	 * @since 2.1.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( T_CONST === $tokens[ $stackPtr ]['code'] ) {
			$this->check_const_declaration( $phpcsFile, $stackPtr, $tokens );
			return;
		}

		$content = strtolower( $tokens[ $stackPtr ]['content'] );
		if ( ! in_array( $content, self::TARGET_FUNCTIONS, true ) ) {
			return;
		}

		// Must be followed by an opening parenthesis (function call), not a class/namespace reference.
		$next_non_empty = $phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );
		if ( false === $next_non_empty || T_OPEN_PARENTHESIS !== $tokens[ $next_non_empty ]['code'] ) {
			return;
		}

		$this->check_function_call( $phpcsFile, $stackPtr, $tokens, $content );
	}

	/**
	 * Inspects a function call and reports a violation if the pattern matches.
	 *
	 * @since 2.1.0
	 *
	 * @param File   $phpcsFile The file being scanned.
	 * @param int    $stackPtr  Position of the function name token.
	 * @param array  $tokens    Token stack.
	 * @param string $func_name Lowercase function name.
	 *
	 * @return void
	 */
	private function check_function_call( File $phpcsFile, $stackPtr, $tokens, $func_name ) {
		$opener = $phpcsFile->findNext( T_OPEN_PARENTHESIS, $stackPtr + 1 );
		if ( false === $opener || ! isset( $tokens[ $opener ]['parenthesis_closer'] ) ) {
			return;
		}

		// Direct call to error_reporting() — no argument check needed.
		if ( 'error_reporting' === $func_name ) {
			$this->report( $phpcsFile, $stackPtr, 'DirectErrorReportingCall' );
			return;
		}

		// Resolve the actual first parameter (token-based find is wrong for calls like
		// `ini_set( some_function(), 'error_reporting' )`).
		$params = PassedParameters::getParameters( $phpcsFile, $stackPtr );
		if ( empty( $params ) || ! isset( $params[1] ) ) {
			return;
		}

		$first_string = $phpcsFile->findNext(
			T_CONSTANT_ENCAPSED_STRING,
			$params[1]['start'],
			$params[1]['end'] + 1
		);
		if ( false === $first_string ) {
			return;
		}

		$argument = trim( $tokens[ $first_string ]['content'], "\"' \t" );

		if ( 'ini_set' === $func_name || 'ini_alter' === $func_name ) {
			$normalized = strtolower( $argument );
			if ( in_array( $normalized, self::ERROR_INI_DIRECTIVES, true ) ) {
				$this->report(
					$phpcsFile,
					$stackPtr,
					MessageHelper::stringToErrorcode( 'IniDirective' . ucfirst( $normalized ) )
				);
			}
			return;
		}

		if ( 'define' === $func_name && in_array( $argument, self::DEBUG_CONSTANTS, true ) ) {
			$this->report(
				$phpcsFile,
				$stackPtr,
				MessageHelper::stringToErrorcode( 'Define' . $argument )
			);
		}
	}

	/**
	 * Inspects a `const` declaration block for debug constants.
	 *
	 * Handles both single (`const WP_DEBUG = true;`) and comma-separated
	 * (`const A = 1, WP_DEBUG = true;`) declarations.
	 *
	 * @since 2.1.0
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param int   $stackPtr  Position of the T_CONST token.
	 * @param array $tokens    Token stack.
	 *
	 * @return void
	 */
	private function check_const_declaration( File $phpcsFile, $stackPtr, $tokens ) {
		$end = $phpcsFile->findNext( array( T_SEMICOLON, T_OPEN_CURLY_BRACKET ), $stackPtr + 1 );
		if ( false === $end ) {
			return;
		}

		for ( $i = $stackPtr + 1; $i < $end; $i++ ) {
			if ( T_STRING === $tokens[ $i ]['code'] && in_array( $tokens[ $i ]['content'], self::DEBUG_CONSTANTS, true ) ) {
				$this->report(
					$phpcsFile,
					$i,
					MessageHelper::stringToErrorcode( 'Const' . $tokens[ $i ]['content'] ),
					true
				);
			}
		}
	}

	/**
	 * Emits a single, stable warning for any detected pattern.
	 *
	 * The check layer translates this to its own user-facing message and severity.
	 *
	 * @since 2.1.0
	 *
	 * @param File   $phpcsFile       The file being scanned.
	 * @param int    $stackPtr        Position of the matched token.
	 * @param string $code            Error code suffix (sniff-specific).
	 * @param bool   $is_const_report Whether this is a constant declaration report.
	 *
	 * @return void
	 */
	private function report( File $phpcsFile, $stackPtr, $code, $is_const_report = false ) {
		$tokens  = $phpcsFile->getTokens();
		$message = $is_const_report
			? 'Detected production-time debug constant definition: %s.'
			: 'Detected production-time change to PHP error reporting: %s().';

		MessageHelper::addMessage(
			$phpcsFile,
			$message,
			$stackPtr,
			false,
			$code,
			array( $tokens[ $stackPtr ]['content'] )
		);
	}
}
