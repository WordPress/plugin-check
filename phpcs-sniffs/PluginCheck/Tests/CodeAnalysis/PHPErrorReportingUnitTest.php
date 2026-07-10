<?php
/**
 * Unit tests for PHPErrorReportingSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\CodeAnalysis;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis\PHPErrorReportingSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for PHPErrorReportingSniff.
 *
 * Exercises the full set of patterns the check must detect:
 *   - direct error_reporting() calls
 *   - ini_set() / ini_alter() with error_reporting or display_errors
 *   - define() of WP_DEBUG, WP_DEBUG_LOG, WP_DEBUG_DISPLAY, SCRIPT_DEBUG
 *   - const declarations of the same debug constants
 */
final class PHPErrorReportingUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return array();
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList() {
		return array(
			10 => 1,
			13 => 1,
			16 => 1,
			19 => 1,
			22 => 1,
			25 => 1,
			28 => 1,
			31 => 1,
			34 => 1,
			37 => 1,
			40 => 1,
			43 => 1,
		);
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string
	 */
	protected function get_sniff_fqcn() {
		return PHPErrorReportingSniff::class;
	}

	/**
	 * Sets the parameters for the sniff.
	 *
	 * @param Sniff $sniff The sniff being tested.
	 *
	 * @return void
	 */
	public function set_sniff_parameters( Sniff $sniff ) {
	}
}
