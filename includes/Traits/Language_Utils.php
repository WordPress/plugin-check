<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Language_Utils
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use LanguageDetection\Language;

/**
 * Trait for language utilities.
 *
 * @since 1.8.0
 */
trait Language_Utils {

	/**
	 * Checks if the content is in an official WordPress language.
	 *
	 * @since 1.8.0
	 *
	 * @param string $content The content to check.
	 * @return bool True if the content is in an official language, otherwise false.
	 */
	protected function is_on_official_language( string $content ): bool {
		// Strip HTML tags and normalize whitespace.
		$content = trim( wp_strip_all_tags( $content ) );

		// Strip inline code and code blocks that might confuse language detection.
		$content = preg_replace( '/`[^`]+`/', '', $content );
		$content = preg_replace( '/```[\s\S]*?```/', '', $content );

		// Remove URLs and email addresses.
		$content = preg_replace( '#https?://[^\s]+#i', '', $content );
		$content = preg_replace( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '', $content );

		// Normalize whitespace after removals.
		$content = trim( preg_replace( '/\s+/', ' ', $content ) );

		// Need minimum content for accurate detection (language detection is unreliable on short strings).
		if ( strlen( $content ) < 30 ) {
			return true; // Too short to reliably detect, give benefit of doubt.
		}

		$lang_detector = new Language();
		$results       = $lang_detector->detect( $content )->bestResults()->close();

		// Check if English is detected with reasonable confidence.
		if ( isset( $results['en'] ) && $results['en'] > 0.3 ) {
			return true;
		}

		// Interlingua is sometimes confused with English, accept if high confidence.
		if ( ! isset( $results['en'] ) && isset( $results['ia'] ) && $results['ia'] > 0.5 ) {
			return true;
		}

		return false;
	}
}
