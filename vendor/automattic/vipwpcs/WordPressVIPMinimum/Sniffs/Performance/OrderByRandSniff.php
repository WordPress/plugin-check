<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace WordPressVIPMinimum\Sniffs\Performance;

use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\AbstractArrayAssignmentRestrictionsSniff;

/**
 * Flag using orderby => rand.
 *
 * @link https://docs.wpvip.com/technical-references/code-review/vip-errors/#h-order-by-rand
 *
 * @since 0.5.0
 */
class OrderByRandSniff extends AbstractArrayAssignmentRestrictionsSniff {

	/**
	 * Groups of variables to restrict.
	 *
	 * @return array
	 */
	public function getGroups() {
		return [
			'orderby' => [
				'type'    => 'error',
				'message' => 'Detected forbidden query_var "%s" of %s. Use vip_get_random_posts() instead.',
				'keys'    => [
					'orderby',
				],
			],
		];
	}

	/**
	 * Callback to process each confirmed key, to check value
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
		return strtolower( $val ) === 'rand';
	}
}
