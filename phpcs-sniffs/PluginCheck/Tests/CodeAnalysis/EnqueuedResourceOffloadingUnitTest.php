<?php
/**
 * Unit tests for EnqueuedResourceOffloadingSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\CodeAnalysis;

use PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis\EnqueuedResourceOffloadingSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Unit tests for EnqueuedResourceOffloadingSniff.
 */
final class EnqueuedResourceOffloadingUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return array(
			5  => 1,
			13 => 1,
		);
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList() {
		return array();
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return EnqueuedResourceOffloadingSniff::class;
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
