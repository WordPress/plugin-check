<?php
/**
 * Unit tests for UnclosedObStartSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\CodeAnalysis;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis\UnclosedObStartSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for UnclosedObStartSniff.
 */
final class UnclosedObStartUnitTest extends AbstractSniffUnitTest {

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
			14 => 1, // Case: ob_start() at file scope with no closing call.
			17 => 1, // Case: multiple ob_start() in the same scope, only one closed.
			29 => 1, // Case: ob_start() closed only conditionally via an if block.
		);
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return UnclosedObStartSniff::class;
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
