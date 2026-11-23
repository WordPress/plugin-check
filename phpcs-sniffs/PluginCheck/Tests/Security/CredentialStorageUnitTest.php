<?php
/**
 * Unit tests for CredentialStorageSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\Security;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\Security\CredentialStorageSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for CredentialStorageSniff.
 *
 * @since 2.3.0
 */
final class CredentialStorageUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @since 2.3.0
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return array();
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @since 2.3.0
	 *
	 * @param string $test_file The name of the test file being checked.
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList( $test_file = '' ) {
		switch ( $test_file ) {
			case 'CredentialStorageUnitTest.1.inc':
				return array(
					3  => 1,
					4  => 1,
					5  => 1,
					6  => 1,
					7  => 1,
					8  => 1,
					9  => 1,
					10 => 1,
					11 => 1,
					12 => 1,
					13 => 1,
					14 => 1,
					25 => 1,
					26 => 1,
					32 => 1,
					33 => 1,
					34 => 1,
					35 => 1,
					36 => 1,
					50 => 1,
					51 => 1,
				);
			case 'CredentialStorageUnitTest.3.inc':
				return array(
					13  => 1,
					18  => 1,
					25  => 1,
					46  => 1,
					51  => 1,
					57  => 1,
					67  => 1,
					74  => 1,
					83  => 1,
					91  => 1,
					99  => 1,
					119 => 1,
					133 => 1,
					149 => 1,
					192 => 1,
					198 => 1,
					212 => 1,
					213 => 1,
					214 => 1,
					221 => 1,
					228 => 1,
					233 => 1,
					248 => 1,
					255 => 1,
					283 => 1,
					294 => 1,
					299 => 1,
					306 => 1,
					312 => 1,
					317 => 1,
				);
			default:
				return array();
		}
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @since 2.3.0
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return CredentialStorageSniff::class;
	}

	/**
	 * Sets the parameters for the sniff.
	 *
	 * @since 2.3.0
	 *
	 * @throws \RuntimeException If unable to set the ruleset parameters required for the test.
	 *
	 * @param Sniff $sniff The sniff being tested.
	 */
	public function set_sniff_parameters( Sniff $sniff ) {
	}
}
