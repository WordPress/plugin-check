<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Performance;

use WordPressCS\WordPress\AbstractArrayAssignmentRestrictionsSniff;

/**
 * Flag use of a timeout of more than 3 seconds for remote requests.
 */
class RemoteRequestTimeoutSniff extends AbstractArrayAssignmentRestrictionsSniff {

	/**
	 * Groups of variables to restrict.
	 *
	 * @return array
	 */
	public function getGroups() {
		return [
			'timeout' => [
				'type'    => 'error',
				'message' => 'Detected high remote request timeout. `%s` is set to `%d`.',
				'keys'    => [
					'timeout',
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
		return (int) $val > 3;
	}
}
