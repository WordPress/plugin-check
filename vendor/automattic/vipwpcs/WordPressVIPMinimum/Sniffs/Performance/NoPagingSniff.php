<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace WordPressVIPMinimum\Sniffs\Performance;

use WordPressCS\WordPress\AbstractArrayAssignmentRestrictionsSniff;

/**
 * Flag returning high or infinite posts_per_page.
 *
 * @link https://docs.wpvip.com/technical-references/code-review/#no-limit-queries
 *
 * @since 0.5.0
 */
class NoPagingSniff extends AbstractArrayAssignmentRestrictionsSniff {

	/**
	 * Groups of variables to restrict.
	 *
	 * @return array
	 */
	public function getGroups() {
		return [
			'nopaging' => [
				'type'    => 'error',
				'message' => 'Disabling pagination is prohibited in VIP context, do not set `%s` to `%s` ever.',
				'keys'    => [
					'nopaging',
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
		$key = strtolower( $key );

		return ( $key === 'nopaging' && ( $val === 'true' || $val === '1' ) );
	}
}
