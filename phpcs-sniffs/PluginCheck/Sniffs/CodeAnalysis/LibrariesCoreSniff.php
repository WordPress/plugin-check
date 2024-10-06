<?php
/**
 * LibrariesCoreSniff
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
final class LibrariesCoreSniff extends Sniff {

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

		// Known LibrariesCore services.
		$look_known_LibrariesCore_services = array(
			'/(?<![\.|-])jquery(-[0-9|\.]*)?(\.slim)?(\.min)?\.js(?!\/)/i',
			'/jquery-ui(-[0-9|\.]*)?(\.slim)?(\.min)?\.js(?!\/)/i',
			'/jquery.color(\.slim)?(\.min)?\.js(?!\/)/i',
			'/jquery.ui.touch-punch(?!\/)/i',
			'/jquery.hoverintent(?!\/)/i',
			'/jquery.imgareaselect(?!\/)/i',
			'/jquery.hotkeys(?!\/)/i',
			'/jquery.ba-serializeobject(?!\/)/i',
			'/jquery.query-object(?!\/)/i',
			'/jquery.suggest(?!\/)/i',
			'/\/polyfill(\.min)?\.js(?!\/)/i',
			'/\/iris(\.min)?\.js(?!\/)/i',
			'/\/backbone(\.min)?\.js(?!\/)/i',
			'/\/clipboard(\.min)?\.js(?!\/)/i',
			'/\/closest(\.min)?\.js(?!\/)/i',
			'/\/codemirror(\.min)?\.js(?!\/)/i',
			'/\/formdata(\.min)?\.js(?!\/)/i',
			'/\/json2(\.min)?\.js(?!\/)/i',
			'/\/lodash(\.min)?\.js(?!\/)/i',
			'/\/masonry(\.pkgd)(\.min)?\.js(?!\/)/i',
			'/\/mediaelement-and-player(\.min)?\.js(?!\/)/i',
			'/\/moment(\.min)?\.js(?!\/)/i',
			'/\/plupload(\.full)(\.min)?\.js(?!\/)/i',
			'/\/thickbox(\.min)?\.js(?!\/)/i',
			'/\/twemoji(\.min)?\.js(?!\/)/i',
			'/\/underscore([\.|-]min)?\.js(?!\/)/i',
			'/\/moxie(\.min)?\.js(?!\/)/i',
			'/\/zxcvbn(\.min)?\.js(?!\/)/i',
			'/\/getid3\.php(?!\/)/i',
			'/\/pclzip\.lib\.php(?!\/)/i',
			'/\/PasswordHash\.php(?!\/)/i',
			'/\/PHPMailer\.php(?!\/)/i',
			'/\/SimplePie\.php(?!\/)/i',
		);

		$pattern = '/(' . implode( '|', $look_known_LibrariesCore_services ) . ')/i';

		$matches = array();
		if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) > 0 ) {
			foreach ( $matches[0] as $match ) {
				$this->phpcsFile->addError(
					'LibrariesCore images, js, css, and other scripts to your servers or any remote service is disallowed.',
					$this->find_token_in_multiline_string( $stackPtr, $content, $match[1] ),
					'LibrariesCore'
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
