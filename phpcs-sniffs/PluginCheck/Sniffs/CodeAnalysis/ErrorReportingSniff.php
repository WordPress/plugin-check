<?php
/**
 * ErrorReportingSniff
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\MessageHelper;
use PHPCSUtils\Utils\PassedParameters;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\Sniff;

/**
 * Detects changes to error reporting and debug configurations.
 *
 * @since 1.9.0
 */
final class ErrorReportingSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.9.0
	 *
	 * @return array<int>
	 */
	public function register() {
		return array( T_STRING );
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.9.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		$content = strtolower( $this->tokens[ $stackPtr ]['content'] );

		// 1. Check for error_reporting(...) call.
		if ( 'error_reporting' === $content ) {
			if ( $this->is_target_function( $stackPtr ) ) {
				$this->phpcsFile->addWarning(
					'Changing error reporting configuration via error_reporting() is discouraged.',
					$stackPtr,
					'ChangingErrorReportingFound'
				);
			}
			return;
		}

		// 2. Check for ini_set(...) or ini_alter(...) call.
		if ( in_array( $content, array( 'ini_set', 'ini_alter' ), true ) ) {
			if ( $this->is_target_function( $stackPtr ) ) {
				$parameters = PassedParameters::getParameters( $this->phpcsFile, $stackPtr );
				$first_param = PassedParameters::getParameterFromStack( $parameters, 1, 'option' );
				if ( false !== $first_param ) {
					$param_value = TextStrings::stripQuotes( $first_param['clean'] );
					if ( in_array( $param_value, array( 'error_reporting', 'display_errors' ), true ) ) {
						$this->phpcsFile->addWarning(
							'Changing error reporting configuration via %s() is discouraged.',
							$stackPtr,
							'ChangingIniSettingFound',
							array( $this->tokens[ $stackPtr ]['content'] )
						);
					}
				}
			}
			return;
		}

		// 3. Check for define(...) call.
		if ( 'define' === $content ) {
			if ( $this->is_target_function( $stackPtr ) ) {
				$parameters = PassedParameters::getParameters( $this->phpcsFile, $stackPtr );
				$first_param = PassedParameters::getParameterFromStack( $parameters, 1, 'constant_name' );
				if ( false !== $first_param ) {
					$param_value = TextStrings::stripQuotes( $first_param['clean'] );
					$debug_constants = array(
						'WP_DEBUG',
						'WP_DEBUG_LOG',
						'SCRIPT_DEBUG',
						'WP_DEBUG_DISPLAY',
					);
					if ( in_array( $param_value, $debug_constants, true ) ) {
						$this->phpcsFile->addWarning(
							'Defining %s in plugin code is discouraged.',
							$stackPtr,
							'ChangingDebugConstantFound',
							array( $param_value )
						);
					}
				}
			}
			return;
		}
	}

	/**
	 * Determine if the token is a function call.
	 *
	 * @since 1.9.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return bool True if a function call, false otherwise.
	 */
	private function is_target_function( $stackPtr ) {
		// Ensure it has an opening parenthesis next (ignoring whitespace).
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $stackPtr + 1 ), null, true );
		if ( false === $next || T_OPEN_PARENTHESIS !== $this->tokens[ $next ]['code'] ) {
			return false;
		}

		// Ensure it's not a method call or static method call.
		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, ( $stackPtr - 1 ), null, true );
		if ( false !== $prev && in_array( $this->tokens[ $prev ]['code'], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON ), true ) ) {
			return false;
		}

		return true;
	}
}
