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
		$content = $this->tokens[ $stackPtr ]['content'];

		if ( false === stripos( $content, '//' ) ) {
			return;
		}

		if ( preg_match_all( '#(https?:)?\/\/(localhost|127.0.0.1|(.*\.local(host)?))\/#i', $content, $matches ) > 0 ) {
			foreach ( $matches[0] as $match ) {
				$this->phpcsFile->addError(
					'Do not use Localhost/127.0.0.1/*.local in your code. Found: %s',
					$stackPtr,
					'Found',
					array( $match )
				);
			}
		}
	}
}
