<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Security;

use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * This sniff ensures that proper sanitization is occurring when PHP's filter_* functions are used.
 *
 * @since 0.4.0
 */
class PHPFilterFunctionsSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @var string
	 */
	protected $group_name = 'php_filter_functions';

	/**
	 * Functions this sniff is looking for.
	 *
	 * @var array The only requirement for this array is that the top level
	 *            array keys are the names of the functions you're looking for.
	 *            Other than that, the array can have arbitrary content
	 *            depending on your needs.
	 */
	protected $target_functions = [
		'filter_var'         => true,
		'filter_input'       => true,
		'filter_var_array'   => true,
		'filter_input_array' => true,
	];

	/**
	 * List of restricted filter names.
	 *
	 * @var array
	 */
	private $restricted_filters = [
		'FILTER_DEFAULT'    => true,
		'FILTER_UNSAFE_RAW' => true,
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
		if ( $matched_content === 'filter_input' ) {
			if ( count( $parameters ) === 2 ) {
				$message = 'Missing third parameter for "%s".';
				$data    = [ $matched_content ];
				$this->phpcsFile->addWarning( $message, $stackPtr, 'MissingThirdParameter', $data );
			}

			if ( isset( $parameters[3], $this->restricted_filters[ $parameters[3]['raw'] ] ) ) {
				$message = 'Please use an appropriate filter to sanitize, as "%s" does no filtering, see: http://php.net/manual/en/filter.filters.sanitize.php.';
				$data    = [ strtoupper( $parameters[3]['raw'] ) ];
				$this->phpcsFile->addWarning( $message, $stackPtr, 'RestrictedFilter', $data );
			}
		} else {
			if ( count( $parameters ) === 1 ) {
				$message = 'Missing second parameter for "%s".';
				$data    = [ $matched_content ];
				$this->phpcsFile->addWarning( $message, $stackPtr, 'MissingSecondParameter', $data );
			}

			if ( isset( $parameters[2], $this->restricted_filters[ $parameters[2]['raw'] ] ) ) {
				$message = 'Please use an appropriate filter to sanitize, as "%s" does no filtering, see http://php.net/manual/en/filter.filters.sanitize.php.';
				$data    = [ strtoupper( $parameters[2]['raw'] ) ];
				$this->phpcsFile->addWarning( $message, $stackPtr, 'RestrictedFilter', $data );
			}
		}
	}
}
