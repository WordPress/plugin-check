<?php
/**
 * ReviewsFilterSniff
 *
 * This sniff detects the regex pattern '/reviews\/\?filter=5/' in PHP files.
 *
 * @link https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Util\Tokens;
use WordPressCS\WordPress\Sniff;

/**
 * Detect regex pattern '/reviews\/\?filter=5/' in PHP files.
 *
 * @link https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
 *
 * @since 1.0.0
 */
final class ReviewsFilterSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function register() {
		return Tokens::$textStringTokens;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		$content = $this->tokens[ $stackPtr ]['content'];

		// Check for the specific regex pattern '/reviews\/\?filter=5/'.
		if ( preg_match( '#/reviews\\/\\?filter=5/#', $content ) ) {
			$this->phpcsFile->addError(
				'Linking directly to 5 stars reviews. Linking to a filtered view of the reviews section to only show 5-star ratings presents a misleading picture of the plugin’s overall feedback and reputation. Found in: %s',
				$stackPtr,
				'FoundReviewsFilter',
				array( $content )
			);
		}
	}
}