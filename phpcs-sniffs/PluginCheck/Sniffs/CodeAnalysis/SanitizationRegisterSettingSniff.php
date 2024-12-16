<?php
/**
 * SanitizationRegisterSettingSniff
 *
 * Based on code from {@link https://github.com/WordPress/WordPress-Coding-Standards}
 * which is licensed under {@link https://opensource.org/licenses/MIT}.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\Sniff;

/**
 * Verifies the registration of fields should be sanitized properly..
 *
 * @link https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
 *
 * @since 1.4.0
 */
final class SanitizationRegisterSettingSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		$targets   = Collections::textStringStartTokens();
		$targets[] = \T_STRING;
		$targets[] = \T_INLINE_HTML;
		$targets[] = \T_CONSTANT_ENCAPSED_STRING;
		$targets[] = \T_VARIABLE;

		return $targets;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 *
	 * @return int|void Integer stack pointer to skip forward or void to continue
	 *                  normal file processing.
	 */
	public function process_token( $stackPtr ) {
		$end_ptr = $stackPtr;
		$content = $this->tokens[ $stackPtr ]['content'];

		if ( \T_INLINE_HTML !== $this->tokens[ $stackPtr ]['code'] ) {
			try {
				$end_ptr = TextStrings::getEndOfCompleteTextString( $this->phpcsFile, $stackPtr );
				$content = TextStrings::getCompleteTextString( $this->phpcsFile, $stackPtr );
			} catch ( RuntimeException $e ) {
				// Parse error/live coding.
				return;
			}
		}

		if ( empty( trim( $content ) ) ) {
			return;
		}

		// Only cover register_setting function.
		if ( false === strpos( $content, 'register_setting(' ) ) {
			return;
		}
		return ( $end_ptr + 1 );
	}
}
