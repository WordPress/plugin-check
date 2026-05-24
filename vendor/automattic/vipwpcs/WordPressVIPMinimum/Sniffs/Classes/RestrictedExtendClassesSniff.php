<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Classes;

use WordPressCS\WordPress\AbstractClassRestrictionsSniff;

/**
 * WordPressVIPMinimum_Sniffs_Classes_RestrictedExtendClassesSniff.
 *
 * @since 0.4.0
 */
class RestrictedExtendClassesSniff extends AbstractClassRestrictionsSniff {

	/**
	 * Groups of classes to restrict.
	 *
	 * @return array
	 */
	public function getGroups() {
		return [
			'wp_cli' => [
				'type'    => 'warning',
				'message' => 'We recommend extending `WPCOM_VIP_CLI_Command` instead of `WP_CLI_Command` and using the helper functions available in it (such as `stop_the_insanity()`), see https://vip.wordpress.com/documentation/writing-bin-scripts/ for more information.',
				'classes' => [
					'WP_CLI_Command',
				],
			],
		];
	}

	/**
	 * Process a matched token.
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (class name) which was matched
	 *                                in lowercase.
	 *
	 * @return void
	 */
	public function process_matched_token( $stackPtr, $group_name, $matched_content ) {
		$tokens = $this->phpcsFile->getTokens();

		if ( $tokens[ $stackPtr ]['code'] !== T_EXTENDS ) {
			// If not extending, bail.
			return;
		}

		foreach ( $this->getGroups() as $group => $group_args ) {
			$this->phpcsFile->{ 'add' . $group_args['type'] }( $group_args['message'], $stackPtr, $group );
		}
	}
}
