<?php
/**
 * RequiredFunctionParametersSniff
 *
 * Based on code from {@link https://github.com/WordPress/WordPress-Coding-Standards}
 * which is licensed under {@link https://opensource.org/licenses/MIT}.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHPCSUtils\Utils\MessageHelper;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Detect missing required function parameters.
 *
 * @link https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
 *
 * @since 1.3.0
 */
final class RequiredFunctionParametersSniff extends AbstractFunctionParameterSniff {

	/**
	 * Array of functions to check.
	 *
	 * @since 1.3.0
	 *
	 * @var array<string, array<string, int|string>> Function name as key, array with target parameter and name as value.
	 */
	protected $target_functions = array(
		'parse_str' => array(
			'position' => 2,
			'name'     => 'result',
		),
	);

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.3.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		if ( isset( $this->target_functions[ strtolower( $this->tokens[ $stackPtr ]['content'] ) ] ) ) {
			// Disallow excluding function groups for this sniff.
			$this->exclude = array();

			return parent::process_token( $stackPtr );
		}
	}

	/**
	 * Process the parameters of a matched function call.
	 *
	 * @since 1.3.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$target_param = $this->target_functions[ $matched_content ];

		$found_param = PassedParameters::getParameterFromStack( $parameters, $target_param['position'], $target_param['name'] );

		if ( false === $found_param ) {
			$error_code = MessageHelper::stringToErrorCode( $matched_content . '_' . $target_param['name'], true );

			$this->phpcsFile->addError(
				'The "%s" parameter for function %s() is missing.',
				$stackPtr,
				$error_code . 'Missing',
				array( $target_param['name'], $matched_content )
			);
		}
	}
}
