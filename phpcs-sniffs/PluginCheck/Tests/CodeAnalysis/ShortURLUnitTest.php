<?php
/**
 * Unit tests for ShortURLSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\CodeAnalysis;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis\ShortURLSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for ShortURLSniff.
 */
final class ShortURLUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array<int, int> Key is the line number and value is the number of expected errors.
	 */
	public function getErrorList() {
		return array();
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array<int, int> Key is the line number and value is the number of expected warnings.
	 */
	public function getWarningList() {
		return array(
			4  => 1, // Case: testShortUrlInHtmlTag.
			7  => 1, // Case: testShortUrlInSingleQuotedString.
			10 => 1, // Case: testShortUrlInDoubleQuotedString.
			15 => 1, // Case: testShortUrlInPhpDocComment.
			22 => 1, // Case: testShortUrlInRegularComment.
			27 => 1, // Case: testShortUrlInHeredoc.
			32 => 1, // Case: testShortUrlInNowdoc.
			36 => 1, // Case: testShortUrlInFileGetContents.
			40 => 1, // Case: testShortUrlInArrayValue (goo.gl).
			41 => 1, // Case: testShortUrlInArrayValue (rb.gy).
			45 => 1, // Case: testShortUrlInEchoStatement.
			51 => 1, // Case: testShortUrlInPhpDocLink.
			58 => 1, // Case: testShortUrlInInlineHtml.
			61 => 2, // Case: testShortUrlInMultipleUrls (bit.ly and lc.chat).
			64 => 1, // Case: testShortUrlInVariableAssignment.
			70 => 1, // Case: testShortUrlInPhpDocDescription.
		);
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return ShortURLSniff::class;
	}

	/**
	 * Sets the parameters for the sniff.
	 *
	 * @throws \RuntimeException If unable to set the ruleset parameters required for the test.
	 *
	 * @param Sniff $sniff The sniff being tested.
	 */
	public function set_sniff_parameters( Sniff $sniff ) {
	}
}
