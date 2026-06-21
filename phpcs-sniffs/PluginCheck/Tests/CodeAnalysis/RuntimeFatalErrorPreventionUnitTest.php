<?php
/**
 * Unit tests for RuntimeFatalErrorPreventionSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\CodeAnalysis;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis\RuntimeFatalErrorPreventionSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for RuntimeFatalErrorPreventionSniff.
 */
final class RuntimeFatalErrorPreventionUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * All findings for this sniff are emitted as warnings to avoid blocking
	 * submission on potentially legitimate integration patterns.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList() {
		return array();
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array<int, int>
	 */
	public function getWarningList() {
		return array(
			5  => 1,
			16 => 1,
			23 => 1,
			30 => 1,
			36 => 1,
			47 => 1,
			59 => 1,
		);
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return RuntimeFatalErrorPreventionSniff::class;
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
