<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace WordPressVIPMinimum\Sniffs\Variables;

use WordPressVIPMinimum\Sniffs\AbstractVariableRestrictionsSniff;

/**
 * Restricts usage of some variables in VIP context.
 *
 * @since 0.5.0
 */
class RestrictedVariablesSniff extends AbstractVariableRestrictionsSniff {

	/**
	 * Groups of variables to restrict.
	 *
	 * Example: groups => array(
	 *  'wpdb' => array(
	 *      'type'          => 'error' | 'warning',
	 *      'message'       => 'Dont use this one please!',
	 *      'variables'     => array( '$val', '$var' ),
	 *      'object_vars'   => array( '$foo->bar', .. ),
	 *      'array_members' => array( '$foo['bar']', .. ),
	 *  )
	 * )
	 *
	 * @return array
	 */
	public function getGroups() {
		return [
			'user_meta' => [
				'type'        => 'error',
				'message'     => 'Usage of users tables is highly discouraged in VIP context',
				'object_vars' => [
					'$wpdb->users',
				],
			],
			'session' => [
				'type'      => 'error',
				'message'   => 'Usage of $_SESSION variable is prohibited.',
				'variables' => [
					'$_SESSION',
				],
			],

			// @link https://docs.wpvip.com/technical-references/code-review/vip-errors/#h-cache-constraints
			'cache_constraints' => [
				'type'          => 'warning',
				'message'       => 'Due to server-side caching, server-side based client related logic might not work. We recommend implementing client side logic in JavaScript instead.',
				'variables'     => [
					'$_COOKIE',
				],
				'array_members' => [
					'$_SERVER[\'HTTP_USER_AGENT\']',
					'$_SERVER[\'REMOTE_ADDR\']',
				],
			],
		];
	}
}
