<?php
/**
 * AIConnectorAPIKeySniff
 *
 * Detects direct access to WordPress AI Connector API keys.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\Security;

use PHPCSUtils\Utils\PassedParameters;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Detect direct access to WordPress AI Connector API keys.
 *
 * Plugins should use the WordPress AI Connector APIs rather than
 * reading provider API keys directly from the options table.
 */
final class AIConnectorAPIKeySniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @var string
	 */
	protected $group_name = 'ai_connector_api_key';

	/**
	 * List of functions to examine.
	 *
	 * @var array<string, true>
	 */
	protected $target_functions = array(
		'get_option'         => true,
		'get_site_option'    => true,
		'get_network_option' => true,
		'get_options'        => true,
	);

	/**
	 * Parameter positions for supported functions.
	 *
	 * @var array<string, int>
	 */
	private $param_positions = array(
		'get_option'         => 1,
		'get_site_option'    => 1,
		'get_network_option' => 2,
		'get_options'        => 1,
	);

	/**
	 * Process the parameters of a matched function.
	 *
	 * Checks whether the requested option name matches the
	 * WordPress AI Connector API key naming pattern.
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 *
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {

		$param_position = isset( $this->param_positions[ $matched_content ] )
			? $this->param_positions[ $matched_content ]
			: 1;

		$option_param = PassedParameters::getParameterFromStack(
			$parameters,
			$param_position,
			array()
		);

		if ( false === $option_param ) {
			return;
		}

		// Handle get_options().
		if ( 'get_options' === $matched_content ) {

			// Extract option names from the get_options() array parameter.
			preg_match_all(
				'/["\']([^"\']+)["\']/',
				$option_param['clean'],
				$matches
			);

			foreach ( $matches[1] as $option_name ) {
				if ( $this->is_connector_api_key( $option_name ) ) {
					$this->add_error( $stackPtr );
					return;
				}
			}

			return;
		}

		$option_name = TextStrings::stripQuotes( $option_param['clean'] );

		if ( ! $this->is_connector_api_key( $option_name ) ) {
			return;
		}

		$this->add_error( $stackPtr );
	}

	/**
	 * Adds an error for direct AI Connector API key access.
	 *
	 * @param int $stackPtr Position of the function call.
	 *
	 * @return void
	 */
	private function add_error( $stackPtr ) {

		$this->phpcsFile->addError(
			'Your plugin reads WordPress AI Connector API keys directly from the options table. Plugins should not access connector credentials directly. Use the WordPress AI Client instead.',
			$stackPtr,
			'DirectAIConnectorAPIKeyAccess'
		);
	}

	/**
	 * Checks whether an option name matches the AI Connector API key pattern.
	 *
	 * @param string $option_name Option name.
	 * @return bool True when the option name matches the AI Connector API key pattern.
	 */
	private function is_connector_api_key( $option_name ) {
		return (bool) preg_match(
			'/^connectors_ai_[a-z0-9_]+_api_key$/i',
			$option_name
		);
	}
}
