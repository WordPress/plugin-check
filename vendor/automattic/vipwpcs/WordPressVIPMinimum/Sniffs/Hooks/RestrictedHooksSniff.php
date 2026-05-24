<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Hooks;

use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * This sniff restricts usage of some action and filter hooks.
 *
 * @since 0.4.0
 */
class RestrictedHooksSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @var string
	 */
	protected $group_name = 'restricted_hooks';

	/**
	 * Functions this sniff is looking for.
	 *
	 * @var array The only requirement for this array is that the top level
	 *            array keys are the names of the functions you're looking for.
	 *            Other than that, the array can have arbitrary content
	 *            depending on your needs.
	 */
	protected $target_functions = [
		'add_filter' => true,
		'add_action' => true,
	];

	/**
	 * List of restricted filters by groups.
	 *
	 * @var array
	 */
	private $restricted_hook_groups = [
		'upload_mimes' => [
			// TODO: This error message needs a link to the VIP Documentation, see https://github.com/Automattic/VIP-Coding-Standards/issues/235.
			'type'  => 'Warning',
			'msg'   => 'Please ensure that the mimes being filtered do not include insecure types (i.e. SVG, SWF, etc.). Manual inspection required.',
			'hooks' => [
				'upload_mimes',
			],
		],
		'http_request' => [
			// https://docs.wpvip.com/technical-references/code-quality-and-best-practices/retrieving-remote-data/.
			'type'  => 'Warning',
			'msg'   => 'Please ensure that the timeout being filtered is not greater than 3s since remote requests require the user to wait for completion before the rest of the page will load. Manual inspection required.',
			'hooks' => [
				'http_request_timeout',
				'http_request_args',
			],
		],
		'robotstxt' => [
			// https://docs.wpvip.com/how-tos/modify-the-robots-txt-file/.
			'type'  => 'Warning',
			'msg'   => 'Don\'t forget to flush the robots.txt cache by going to Settings > Reading and toggling the privacy settings.',
			'hooks' => [
				'do_robotstxt',
				'robots_txt',
			],
		],
	];

	/**
	 * Process the parameters of a matched function.
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched
	 *                                in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 *
	 * @return int|void Integer stack pointer to skip forward or void to continue
	 *                  normal file processing.
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		foreach ( $this->restricted_hook_groups as $group => $group_args ) {
			foreach ( $group_args['hooks'] as $hook ) {
				if ( $this->normalize_hook_name_from_parameter( $parameters[1] ) === $hook ) {
					$addMethod = 'add' . $group_args['type'];
					$this->phpcsFile->{$addMethod}( $group_args['msg'], $stackPtr, $hook );
				}
			}
		}
	}

	/**
	 * Normalize hook name parameter.
	 *
	 * @param array $parameter Array with information about a parameter.
	 *
	 * @return string Normalized hook name.
	 */
	private function normalize_hook_name_from_parameter( $parameter ) {
		// If concatenation is found, build hook name.
		$concat_ptr = $this->phpcsFile->findNext(
			T_STRING_CONCAT,
			$parameter['start'],
			$parameter['end'],
			false,
			null,
			true
		);

		if ( $concat_ptr ) {
			$hook_name = '';
			for ( $i = $parameter['start'] + 1; $i < $parameter['end']; $i++ ) {
				if ( $this->tokens[ $i ]['code'] === T_CONSTANT_ENCAPSED_STRING ) {
					$hook_name .= str_replace( [ "'", '"' ], '', $this->tokens[ $i ]['content'] );
				}
			}
		} else {
			$hook_name = $parameter['raw'];
		}

		// Remove quotes (double and single), and use lowercase.
		return strtolower( str_replace( [ "'", '"' ], '', $hook_name ) );
	}
}
