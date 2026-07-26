<?php
/**
 * AutoLoadedOptionsSniff
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
 * Warns when add_option() / update_option() are called without explicitly
 * setting the $autoload parameter.
 *
 * The default value of $autoload is true, which loads the option on every
 * page request. Plugins that accumulate autoloaded options slow down every
 * request. Letting the author choose explicitly is the goal.
 *
 * @link https://developer.wordpress.org/reference/functions/add_option/
 * @link https://developer.wordpress.org/reference/functions/update_option/
 *
 * @since 2.1.0
 */
final class AutoLoadedOptionsSniff extends AbstractFunctionParameterSniff {

	/**
	 * Position of the $autoload parameter for each target function.
	 *
	 * add_option() is `add_option( $option, $value, $deprecated, $autoload )`
	 * update_option() is `update_option( $option, $value, $autoload )`.
	 *
	 * @since 2.1.0
	 *
	 * @var array<string, int>
	 */
	protected $autoload_positions = array(
		'add_option'    => 4,
		'update_option' => 3,
	);

	/**
	 * List of functions to examine.
	 *
	 * @since 2.1.0
	 *
	 * @var array<string, true>
	 */
	protected $target_functions = array(
		'add_option'    => true,
		'update_option' => true,
	);

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 2.1.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		if ( isset( $this->target_functions[ strtolower( $this->tokens[ $stackPtr ]['content'] ) ] ) ) {
			$this->exclude = array();

			return parent::process_token( $stackPtr );
		}
	}

	/**
	 * Process the parameters of a matched function call.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$position = $this->autoload_positions[ $matched_content ];
		$found    = PassedParameters::getParameterFromStack( $parameters, $position, 'autoload' );

		if ( false === $found ) {
			$error_code = MessageHelper::stringToErrorcode( $matched_content . '_autoload', true );

			$this->phpcsFile->addWarning(
				'The $autoload parameter for %s() is not explicitly set; the option will default to autoloading on every page request. Pass an explicit boolean (true or false) to make the performance trade-off intentional.',
				$stackPtr,
				$error_code . 'Missing',
				array( $matched_content )
			);
		}
	}
}
