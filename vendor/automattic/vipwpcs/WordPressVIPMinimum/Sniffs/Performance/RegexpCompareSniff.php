<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Performance;

use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\AbstractArrayAssignmentRestrictionsSniff;

/**
 * Flag REGEXP and NOT REGEXP in meta compare
 */
class RegexpCompareSniff extends AbstractArrayAssignmentRestrictionsSniff {

	/**
	 * Groups of variables to restrict.
	 *
	 * @return array
	 */
	public function getGroups() {
		return [
			'compare' => [
				'type'    => 'error',
				'message' => 'Detected regular expression comparison. `%s` is set to `%s`.',
				'keys'    => [
					'compare',
					'meta_compare',
				],
			],
		];
	}

	/**
	 * Callback to process each confirmed key, to check value.
	 *
	 * @param  string $key   Array index / key.
	 * @param  mixed  $val   Assigned value.
	 * @param  int    $line  Token line.
	 * @param  array  $group Group definition.
	 *
	 * @return bool FALSE if no match, TRUE if matches.
	 */
	public function callback( $key, $val, $line, $group ) {
		$val = TextStrings::stripQuotes( $val );
		return ( strpos( $val, 'NOT REGEXP' ) === 0
			|| strpos( $val, 'REGEXP' ) === 0
		);
	}
}
