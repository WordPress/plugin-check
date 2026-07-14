<?php
/**
 * Unit tests for NonceVerificationSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\Security;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\Security\NonceVerificationSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for NonceVerificationSniff.
 */
final class NonceVerificationUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return array(
			10 => 1, // $_POST inside wp_unslash() in global scope without nonce.
			13 => 1, // $_POST inside wp_unslash() in function without nonce.
			17 => 1, // $_POST direct assignment without nonce.
			18 => 1, // $_POST direct assignment without nonce.
			26 => 2, // Two $_POST accesses in computation without nonce.
		);
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList() {
		return array(
			22 => 1, // $_GET inside wp_unslash() without nonce.
			63 => 1, // $_GET inside sanitize_text_field(wp_unslash()) without nonce.
		);
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return NonceVerificationSniff::class;
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
