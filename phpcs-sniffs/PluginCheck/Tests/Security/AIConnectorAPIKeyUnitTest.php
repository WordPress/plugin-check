<?php
/**
 * Unit tests for AIConnectorAPIKeySniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\Security;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\Security\AIConnectorAPIKeySniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for AIConnectorAPIKeySniff.
 *
 * @since 2.2.0
 */
final class AIConnectorAPIKeyUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @since 2.2.0
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return array(
			3  => 1, // get_option() reading Connector API key.
			4  => 1, // get_site_option() reading Connector API key.
			5  => 1, // get_network_option() reading Connector API key.
			7  => 1, // get_options() reading Connector API key (single quotes).
			14 => 1, // get_options() reading Connector API key (double quotes).
			24 => 1, // get_option() with named arg.
			25 => 1, // get_site_option() with named arg.
			26 => 1, // get_network_option() with named arg.
			27 => 1, // get_options() with named arg.
		);
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @since 2.2.0
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList() {
		return array();
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	protected function get_sniff_fqcn() {
		return AIConnectorAPIKeySniff::class;
	}

	/**
	 * Sets the parameters for the sniff.
	 *
	 * @since 2.2.0
	 *
	 * @param Sniff $sniff The sniff being tested.
	 */
	public function set_sniff_parameters( Sniff $sniff ) {
	}
}
