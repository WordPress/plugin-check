<?php
/**
 * LocalhostSniff
 *
 * Based on code from {@link https://github.com/WordPress/WordPress-Coding-Standards}
 * which is licensed under {@link https://opensource.org/licenses/MIT}.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Util\Tokens;
use WordPressCS\WordPress\Sniff;

/**
 * Detect localhost.
 *
 * @link https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
 *
 * @since 1.3.0
 */
final class LocalhostSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	public function register() {
		return Tokens::$textStringTokens;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.3.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		$end_ptr = $stackPtr;
		$content = $this->tokens[ $stackPtr ]['content'];

		if ( false === stripos( $content, '//' ) ) {
			return;
		}

		if ( preg_match_all( '#https?:\/\/(localhost|127.0.0.1|(.*\.local(host)?))\/#', $content, $matches, PREG_OFFSET_CAPTURE ) > 0 ) {
			foreach ( $matches[0] as $match ) {
				$this->phpcsFile->addError(
					'Do not use Localhost/127.0.0.1 in your code. Found: %s',
					$this->find_token_in_multiline_string( $stackPtr, $content, $match[1] ),
					'Found',
					[ $match[0] ]
				);
			}
		}

		return ( $end_ptr + 1 );
	}

	/**
	 * Find the exact token on which the error should be reported for multi-line strings.
	 *
	 * @since 1.3.0
	 *
	 * @param int    $stackPtr     The position of the current token in the stack.
	 * @param string $content      The complete, potentially multi-line, text string.
	 * @param int    $match_offset The offset within the content at which the match was found.
	 * @return int The stack pointer to the token containing the start of the match.
	 */
	private function find_token_in_multiline_string( $stackPtr, $content, $match_offset ) {
		$newline_count = 0;
		if ( $match_offset > 0 ) {
			$newline_count = substr_count( $content, "\n", 0, $match_offset );
		}

		// Account for heredoc/nowdoc text starting at the token *after* the opener.
		if ( isset( Tokens::$heredocTokens[ $this->tokens[ $stackPtr ]['code'] ] ) === true ) {
			++$newline_count;
		}

		return ( $stackPtr + $newline_count );
	}
}
