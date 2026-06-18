<?php
/**
 * Unit tests for WriteFileSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\CodeAnalysis;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis\WriteFileSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for WriteFileSniff.
 */
final class WriteFileUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return array(
			5  => 1, // file_put_contents with __FILE__.
			8  => 1, // fwrite with __DIR__.
			11 => 1, // fputs with plugin_dir_path().
			14 => 1, // copy with WP_PLUGIN_DIR.
			17 => 1, // rename with plugin_dir_path().
			20 => 1, // touch with WP_CONTENT_DIR.
			23 => 1, // file_put_contents with plugins_url().
			27 => 1, // file_put_contents with variable assigned from plugin_dir_path().
			30 => 1, // file_put_contents with plugin_dir_path() and wp_upload_dir().
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
		return WriteFileSniff::class;
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
