<?php
/**
 * Unit tests for PublicContentExportSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\Security;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\Security\PublicContentExportSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for PublicContentExportSniff.
 */
final class PublicContentExportUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * All findings are warnings for this advisory check, so the error list
	 * is always empty.
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
			17 => 1, // file_put_contents with the_content().
			24 => 1, // fwrite with get_the_content().
			30 => 1, // fputs with get_the_excerpt().
			36 => 1, // file_put_contents with $post->post_content.
			43 => 1, // apply_filters('the_content') into file_put_contents.
			49 => 1, // fwrite with get_post_field('post_content').
		);
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return PublicContentExportSniff::class;
	}

	/**
	 * Sets the parameters for the sniff.
	 *
	 * No additional parameters needed for this sniff.
	 *
	 * @throws \RuntimeException If unable to set the ruleset parameters required for the test.
	 *
	 * @param Sniff $sniff The sniff being tested.
	 */
	public function set_sniff_parameters( Sniff $sniff ) {
	}
}
