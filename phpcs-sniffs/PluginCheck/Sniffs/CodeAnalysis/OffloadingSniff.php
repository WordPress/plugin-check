<?php
/**
 * OffloadingSniff
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
 * Verifies any images/styles/scripts are not loaded from external sources.
 *
 * @link https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
 *
 * @since 1.1.0
 */
final class OffloadingSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		$targets   = Collections::textStringStartTokens();
		$targets[] = \T_INLINE_HTML;

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

		// Only match HTML markup not arbitrary strings, as those could be covered by EnqueuedResourceOffloadingSniff already.

		if (
			false === strpos( $content, '<img' ) &&
			false === strpos( $content, '<video' ) &&
			false === strpos( $content, '<audio' ) &&
			false === strpos( $content, '<source' ) &&
			false === strpos( $content, '<link' ) &&
			false === strpos( $content, '<script' )
		) {
			return;
		}

		// Known offloading services.
		$look_known_offloading_services = array(
			'code\.jquery\.com',
			'(?<!api\.)cloudflare\.com',
			'cdn\.jsdelivr\.net',
			'cdn\.rawgit\.com',
			'code\.getmdl\.io',
			'bootstrapcdn',
			'cl\.ly',
			'cdn\.datatables\.net',
			'aspnetcdn\.com',
			'ajax\.googleapis\.com',
			'webfonts\.zoho\.com',
			'raw\.githubusercontent\.com',
			'github\.com\/.*\/raw',
			'unpkg\.com',
			'imgur\.com',
			'rawgit\.com',
			'amazonaws\.com',
			'cdn\.tiny\.cloud',
			'tiny\.cloud',
			'tailwindcss\.com',
			'herokuapp\.com',
			'(?<!fonts\.)gstatic\.com',
			'kit\.fontawesome',
			'use\.fontawesome',
			'googleusercontent\.com',
			'placeholder\.com',
			's\.w\.org',
		);

		$pattern = '/(' . implode( '|', $look_known_offloading_services ) . ')/i';

		$matches = array();
		if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) > 0 ) {
			foreach ( $matches[0] as $match ) {
				$this->phpcsFile->addError(
					'Offloading images, js, css, and other scripts to your servers or any remote service is disallowed.',
					$this->find_token_in_multiline_string( $stackPtr, $content, $match[1] ),
					'OffloadedContent'
				);
			}
			return ( $end_ptr + 1 );
		}

		// Known offloading extensions.
		$look_known_offloading_ext = array(
			'css',
			'svg',
			'jpg',
			'jpeg',
			'gif',
			'png',
			'webm',
			'mp4',
			'mpg',
			'mpeg',
			'mp3',
			'json',
		);

		$offloading_ext = '\.' . implode( '|\.', $look_known_offloading_ext );
		$pattern        = '/(https?:\/\/[www\.]?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b[-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*(' . $offloading_ext . '){1})[\/]?([\?|#]{1}[-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)?[\s|\'|"]/';

		$matches = array();
		if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) > 0 ) {
			foreach ( $matches[0] as $match ) {
				$this->phpcsFile->addError(
					'Offloading images, js, css, and other scripts to your servers or any remote service is disallowed.',
					$this->find_token_in_multiline_string( $stackPtr, $content, $match[1] ),
					'OffloadedContent'
				);
			}
		}

		return ( $end_ptr + 1 );
	}

	/**
	 * Find the exact token on which the error should be reported for multi-line strings.
	 *
	 * @param int    $stackPtr     The position of the current token in the stack.
	 * @param string $content      The complete, potentially multi-line, text string.
	 * @param int    $match_offset The offset within the content at which the match was found.
	 *
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
