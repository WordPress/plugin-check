<?php
/**
 * Unit tests for VerifyNonceSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\Security;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\Security\VerifyNonceSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for VerifyNonceSniff.
 */
final class VerifyNonceUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return array(
			8  => 1, // insecure_nonce_1: Nonce not checked if it's unset (isset && !wp_verify_nonce).
			15 => 1, // insecure_nonce_2: Nonce not checked if it's unset (isset && !wp_verify_nonce).
			21 => 1, // insecure_nonce_3: Nonce not checked if it's unset (isset && !wp_verify_nonce).
			28 => 1, // insecure_nonce_4: Unconditional wp_verify_nonce call.
			42 => 1, // insecure_nonce_6: AND instead of OR (!isset && !wp_verify_nonce).
			47 => 1, // insecure_nonce_7: AND instead of OR (!isset && !wp_verify_nonce).
		);
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList() {
		return array(
			33 => 1, // insecure_nonce_5: OR condition with else.
			56 => 1, // insecure_nonce_8: OR condition without proper else handling.
		);
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return VerifyNonceSniff::class;
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
