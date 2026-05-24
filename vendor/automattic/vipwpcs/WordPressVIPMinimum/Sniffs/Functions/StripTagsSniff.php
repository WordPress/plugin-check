<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Functions;

use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * This sniff ensures proper tag stripping.
 *
 * @since 0.4.0
 */
class StripTagsSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @var string
	 */
	protected $group_name = 'strip_functions';

	/**
	 * Functions this sniff is looking for.
	 *
	 * @var array The only requirement for this array is that the top level
	 *            array keys are the names of the functions you're looking for.
	 *            Other than that, the array can have arbitrary content
	 *            depending on your needs.
	 */
	protected $target_functions = [
		'strip_tags' => true,
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
		if ( count( $parameters ) === 1 ) {
			$message = '`strip_tags()` does not strip CSS and JS in between the script and style tags. Use `wp_strip_all_tags()` to strip all tags.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'StripTagsOneParameter' );
		} elseif ( isset( $parameters[2] ) ) {
			$message = '`strip_tags()` does not strip CSS and JS in between the script and style tags. Use `wp_kses()` instead to allow only the HTML you need.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'StripTagsTwoParameters' );
		}
	}
}
