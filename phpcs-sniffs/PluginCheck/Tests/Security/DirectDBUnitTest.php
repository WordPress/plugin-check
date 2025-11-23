<?php
/**
 * Unit tests for DirectDBSniff.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\Security;

use PHP_CodeSniffer\Sniffs\Sniff;
use PluginCheckCS\PluginCheck\Sniffs\Security\DirectDBSniff;
use PluginCheckCS\PluginCheck\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for DirectDBSniff.
 */
final class DirectDBUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return array(
			14  => 1, // Unescaped parameter $foo used in $wpdb->query.
			22  => 1, // Unescaped parameter $foo used in $wpdb->query.
			30  => 1, // Unescaped parameter $foo used in $wpdb->query.
			38  => 1, // Unescaped parameter $foo[1] used in $wpdb->query.
			45  => 1, // Unescaped parameter $_POST['foo'] used in $wpdb->query.
			52  => 1, // Unescaped parameter $foo->bar used in $wpdb->query.
			59  => 1, // Unescaped parameter baz( $foo ) used in $wpdb->query.
			66  => 1, // Unescaped parameter $foo used in $this->wpdb->query.
			75  => 1, // Unescaped parameter $esc[2]->foo used in $wpdb->query.
			82  => 1, // Unescaped parameter $foo used in $wpdb->query.
			97  => 1, // Unescaped parameter $foo used in $wpdb->query.
			106 => 1, // Unescaped parameter $sql used in $wpdb->get_results.
			113 => 1, // Unescaped parameter $user_id used in $wpdb->get_var.
			120 => 1, // Unescaped parameter $foo used in $wpdb->query.
			140 => 1, // Unescaped parameter $id used in $wpdb->get_results.
			159 => 1, // Unescaped parameter $bar used in $wpdb->get_results.
			168 => 1, // Unescaped parameter $bar used in $wpdb->query.
			181 => 1, // Unescaped parameter $bar used in $wpdb->query.
			200 => 1, // Unescaped parameter $where_clause used in $wpdb->query.
			221 => 1, // Unescaped parameter $sql used in $wpdb->query.
			258 => 1, // Unescaped parameter $query used in $wpdb->get_var.
			270 => 1, // Unescaped parameter $sql used in $wpdb->get_row.
			292 => 1, // Unescaped parameter $sql used in $wpdb->get_results.
			310 => 1, // Unescaped parameter $foo used in $wpdb->query.
			316 => 1, // Unescaped parameter $foo used in $wpdb->query.
			328 => 1, // Unescaped parameter $sql_query used in $wpdb->query.
			335 => 1, // Unescaped parameter $sql used in $wpdb->query.
			342 => 1, // Unescaped parameter $query used in $wpdb->get_results.
		);
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList() {
		return array(
			89  => 1, // Unescaped parameter $table used in $wpdb->query (warn only).
			149 => 1, // Unescaped parameter $this->getPrefix() used in $wpdb->get_results (warn only).
			188 => 1, // Unescaped parameter $where_clause used in $wpdb->query (warn only).
			191 => 1, // Unescaped parameter $where_clause used in $wpdb->query (warn only).
			196 => 1, // Unescaped parameter $where_clause used in $wpdb->query (warn only).
			203 => 1, // Unescaped parameter $where_clause used in $wpdb->query (warn only).
			207 => 1, // Unescaped parameter $coupon_subquery used in $wpdb->get_var (warn only).
			278 => 1, // Unescaped parameter $this->my_table_name used in $wpdb->get_var (warn only).
			279 => 1, // Unescaped parameter $this->my_table_name used in $wpdb->get_var (warn only).
			280 => 1, // Unescaped parameter $this->get_table_name() used in $wpdb->get_var (warn only).
			281 => 1, // Unescaped parameter $this->my_table_name used in $wpdb->get_var (warn only).
			301 => 1, // Unescaped parameter $table_name used in $wpdb->query (warn only).
		);
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return DirectDBSniff::class;
	}

	/**
	 * Sets the parameters for the sniff.
	 *
	 * @throws \RuntimeException If unable to set the ruleset parameters required for the test.
	 *
	 * @param Sniff $sniff The sniff being tested.
	 */
	public function set_sniff_parameters( Sniff $sniff ) {
		// No specific parameters needed for DirectDBSniff.
	}
}
