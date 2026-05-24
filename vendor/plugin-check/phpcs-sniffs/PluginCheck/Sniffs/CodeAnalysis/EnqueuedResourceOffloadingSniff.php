<?php
/**
 * EnqueuedResourceOffloadingSniff
 *
 * Based on code from {@link https://github.com/WordPress/WordPress-Coding-Standards}
 * which is licensed under {@link https://opensource.org/licenses/MIT}.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\PassedParameters;
use PluginCheckCS\PluginCheck\Helpers\OffloadingServicesTrait;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * This checks the enqueued 2nd parameter ($src) to verify resources are not loaded from external sources.
 *
 * @link https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
 * @link https://developer.wordpress.org/reference/functions/wp_register_script/
 * @link https://developer.wordpress.org/reference/functions/wp_enqueue_script/
 * @link https://developer.wordpress.org/reference/functions/wp_register_style/
 * @link https://developer.wordpress.org/reference/functions/wp_enqueue_style/
 *
 * @since 1.1.0
 */
final class EnqueuedResourceOffloadingSniff extends AbstractFunctionParameterSniff {
	use OffloadingServicesTrait;

	/**
	 * The group name for this group of functions.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $group_name = 'Enqueued';

	/**
	 * List of functions to check.
	 *
	 * @since 1.1.0
	 *
	 * @var array<string, true> Key is function name, value irrelevant.
	 */
	protected $target_functions = array(
		'wp_register_script' => true,
		'wp_enqueue_script'  => true,
		'wp_register_style'  => true,
		'wp_enqueue_style'   => true,
	);

	/**
	 * Process the parameters of a matched function.
	 *
	 * @since 1.1.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched
	 *                                in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 *
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$src_param = PassedParameters::getParameterFromStack( $parameters, 2, 'src' );

		if ( false === $src_param || empty( $src_param['clean'] ) ) {
			return;
		}

		$error_ptr = $this->phpcsFile->findNext( Tokens::$emptyTokens, $src_param['start'], ( $src_param['end'] + 1 ), true );

		$type = 'script';
		if ( strpos( $matched_content, '_style' ) !== false ) {
			$type = 'style';
		}

		$src_string = $src_param['clean'];

		$pattern = $this->get_offloading_services_pattern();

		if ( preg_match( $pattern, $src_string ) > 0 ) {
			$this->phpcsFile->addError(
				'Found call to %s() with external resource. Offloading %ss to your servers or any remote service is disallowed.',
				$error_ptr,
				'OffloadedContent',
				array( $matched_content, $type )
			);
		}
	}
}
