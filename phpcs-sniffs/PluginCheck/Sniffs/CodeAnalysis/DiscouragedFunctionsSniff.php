<?php
/**
 * DiscouragedFunctionsSniff
 *
 * Based on code from {@link https://github.com/WordPress/WordPress-Coding-Standards}
 * which is licensed under {@link https://opensource.org/licenses/MIT}.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHPCSUtils\Utils\MessageHelper;
use WordPressCS\WordPress\AbstractFunctionRestrictionsSniff;
use WordPressCS\WordPress\Helpers\MinimumWPVersionTrait;

/**
 * Detect discouraged functions.
 *
 * @link https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
 *
 * @since 1.6.0
 */
final class DiscouragedFunctionsSniff extends AbstractFunctionRestrictionsSniff {

	use MinimumWPVersionTrait;

	/**
	 * List of discouraged functions.
	 *
	 * @var array
	 */
	private $discouraged_functions = array(
		'load_plugin_textdomain' => array(
			'version' => '4.6',
		),
	);

	/**
	 * Groups of functions to discourage.
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	public function getGroups() {
		// Make sure all array keys are lowercase.
		$this->discouraged_functions = array_change_key_case( $this->discouraged_functions, \CASE_LOWER );

		return array(
			'discouraged_functions' => array(
				'functions' => array_keys( $this->discouraged_functions ),
			),
		);
	}

	/**
	 * Process a matched token.
	 *
	 * @since 1.6.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched. Will always be 'discouraged_functions'.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 *
	 * @return void
	 */
	public function process_matched_token( $stackPtr, $group_name, $matched_content ) {
		$this->set_minimum_wp_version();

		$message = '%s() has been discouraged since WordPress version %s.';

		$data = array(
			$this->tokens[ $stackPtr ]['content'],
			$this->discouraged_functions[ $matched_content ]['version'],
		);

		if ( ! empty( $this->discouraged_functions[ $matched_content ]['alt'] ) ) {
			$message .= ' Use %s instead.';
			$data[]   = $this->discouraged_functions[ $matched_content ]['alt'];
		}

		if ( $this->wp_version_compare( $this->discouraged_functions[ $matched_content ]['version'], $this->minimum_wp_version, '<' ) ) {
			MessageHelper::addMessage(
				$this->phpcsFile,
				$message,
				$stackPtr,
				false,
				MessageHelper::stringToErrorcode( $matched_content . 'Found' ),
				$data
			);
		}
	}
}
