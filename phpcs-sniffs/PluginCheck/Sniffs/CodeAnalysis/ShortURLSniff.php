<?php
/**
 * ShortURLSniff
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\Sniff;

/**
 * Detects the use of short URLs.
 *
 * @since 1.8.0
 */
final class ShortURLSniff extends Sniff {

	/**
	 * List of short URL domains to detect.
	 *
	 * @since 1.8.0
	 *
	 * @var array<string>
	 */
	protected $short_url_domains = array(
		'adf.ly',
		'bit.do',
		'bit.ly',
		'clck.ru',
		'cutt.ly',
		'df.ly',
		'goo.gl',
		'is.gd',
		'lc.chat',
		'ow.ly',
		'polr.me',
		'rb.gy',
		's2r.co',
		'short.link',
		'shorturl.at',
		'soo.gd',
		'tiny.cc',
		'tinyurl.com',
		'v.gd',
	);

	/**
	 * Compiled regex pattern for detecting short URLs.
	 *
	 * @since 1.8.0
	 *
	 * @var string|null
	 */
	private $pattern = null;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.8.0
	 *
	 * @return array<int>
	 */
	public function register() {
		return array(
			T_COMMENT,
			T_CONSTANT_ENCAPSED_STRING,
			T_DOC_COMMENT,
			T_DOC_COMMENT_STRING,
			T_DOUBLE_QUOTED_STRING,
			T_HEREDOC,
			T_NOWDOC,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.8.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		$content    = $this->tokens[ $stackPtr ]['content'];
		$token_code = $this->tokens[ $stackPtr ]['code'];

		// For comment tokens, use content directly.
		if ( T_DOC_COMMENT === $token_code || T_DOC_COMMENT_STRING === $token_code || T_COMMENT === $token_code ) {
			$string_content = $content;
		} else {
			// Extract string content without quotes.
			$string_content = TextStrings::stripQuotes( $content );
		}

		// Compile regex pattern on first use.
		if ( null === $this->pattern ) {
			$escaped_domains = array_map( 'preg_quote', $this->short_url_domains, array_fill( 0, count( $this->short_url_domains ), '/' ) );
			$this->pattern   = '/https?:\/\/(?:[^\/\s]+\.)?(' . implode( '|', $escaped_domains ) . ')/';
		}

		// Check if any short URL domain is present in the string.
		if ( preg_match( $this->pattern, $string_content, $matches ) ) {
			$error = 'Short URL detected (%s). Use full URLs instead of URL shorteners.';
			$this->phpcsFile->addWarning( $error, $stackPtr, 'Found', array( $matches[1] ) );
		}
	}
}
