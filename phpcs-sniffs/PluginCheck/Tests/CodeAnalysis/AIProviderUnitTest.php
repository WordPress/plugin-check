<?php
/**
 * Unit tests for AIProviderSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\CodeAnalysis;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis\AIProviderSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for AIProviderSniff.
 */
final class AIProviderUnitTest extends AbstractSniffUnitTest {

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
			4  => 1, // Case: testOpenAiInSingleQuotedString.
			7  => 1, // Case: testAnthropicInSingleQuotedString.
			10 => 1, // Case: testGeminiInDoubleQuotedString.
			13 => 1, // Case: testGrokInSingleQuotedString.
			16 => 1, // Case: testMistralInSingleQuotedString.
			19 => 1, // Case: testCohereAiInSingleQuotedString.
			22 => 1, // Case: testCohereComInSingleQuotedString.
			25 => 1, // Case: testGroqInSingleQuotedString.
			28 => 1, // Case: testPerplexityInSingleQuotedString.
			31 => 1, // Case: testDeepSeekInSingleQuotedString.
			34 => 1, // Case: testOpenRouterInSingleQuotedString.
			37 => 1, // Case: testHttpSchemeIsMatched.
			41 => 1, // Case: testProviderInHeredoc.
			46 => 1, // Case: testProviderInNowdoc.
		);
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return AIProviderSniff::class;
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
