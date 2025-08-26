<?php
/**
 * I18nTextDomainSniff
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\MessageHelper;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Detect text domain usage in internationalization functions.
 *
 * @since 1.0.0
 */
final class I18nTextDomainSniff extends AbstractFunctionParameterSniff {

	/**
	 * The I18N functions.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string> Key is function name, value is the function type.
	 */
	protected $i18n_functions = array(
		'translate'                      => 'simple',
		'__'                             => 'simple',
		'esc_attr__'                     => 'simple',
		'esc_html__'                     => 'simple',
		'_e'                             => 'simple',
		'esc_attr_e'                     => 'simple',
		'esc_html_e'                     => 'simple',
		'translate_with_gettext_context' => 'context',
		'_x'                             => 'context',
		'_ex'                            => 'context',
		'esc_attr_x'                     => 'context',
		'esc_html_x'                     => 'context',
		'_n'                             => 'number',
		'_nx'                            => 'number_context',
		'_n_noop'                        => 'noopnumber',
		'_nx_noop'                       => 'noopnumber_context',
	);

	/**
	 * Core WordPress translations.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, int> Array of core translation keys.
	 */
	private $core_translations = null;

	/**
	 * Core translations file paths.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	private static $core_translations_files = null;

	/**
	 * Parameter specifications for the functions in each group.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array> Array of the parameter positions and names.
	 */
	private $parameter_specs = array(
		'simple'             => array(
			1 => 'text',
			2 => 'domain',
		),
		'context'            => array(
			1 => 'text',
			2 => 'context',
			3 => 'domain',
		),
		'number'             => array(
			1 => 'single',
			2 => 'plural',
			3 => 'number',
			4 => 'domain',
		),
		'number_context'     => array(
			1 => 'single',
			2 => 'plural',
			3 => 'number',
			4 => 'context',
			5 => 'domain',
		),
		'noopnumber'         => array(
			1 => 'singular',
			2 => 'plural',
			3 => 'domain',
		),
		'noopnumber_context' => array(
			1 => 'singular',
			2 => 'plural',
			3 => 'context',
			4 => 'domain',
		),
	);

	/**
	 * Groups of functions.
	 *
	 * @return array
	 */
	public function getGroups() {
		return array(
			'i18n'  => array(
				'functions' => array_keys( $this->i18n_functions ),
			),
			'typos' => array(
				'functions' => array(
					'_',
				),
			),
		);
	}

	/**
	 * Process a matched token.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @return void
	 */
	public function process_matched_token( $stackPtr, $group_name, $matched_content ) {
		$func_open_paren_token = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $stackPtr + 1 ), null, true );

		if ( ! isset( $this->tokens[ $func_open_paren_token ]['parenthesis_closer'] ) ) {
			return;
		}

		parent::process_matched_token( $stackPtr, $group_name, $matched_content );
	}

	/**
	 * Process the parameters of a matched function.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$function_param_specs = $this->parameter_specs[ $this->i18n_functions[ $matched_content ] ];

		// Check for missing domain parameter.
		$domain_param_position = null;
		foreach ( $function_param_specs as $position => $name ) {
			if ( 'domain' === $name ) {
				$domain_param_position = $position;
				break;
			}
		}

		if ( null !== $domain_param_position ) {
			$domain_param = PassedParameters::getParameterFromStack( $parameters, $domain_param_position, 'domain' );

			// Check if domain parameter is missing or empty.
			if ( false === $domain_param || '' === trim( $domain_param['clean'] ) ) {
				// Check if this is a core translation.
				$is_core_translation = $this->is_core_translation( $parameters, $function_param_specs );

				// Show error for non-core translations.
				if ( ! $is_core_translation ) {
					$error_code = MessageHelper::stringToErrorcode( 'MissingDomain' );

					$this->phpcsFile->addError(
						'Missing text domain parameter in function call to %s().',
						$stackPtr,
						$error_code . 'Required',
						array( $matched_content )
					);
				}
			}
		}
	}

	/**
	 * Load core translations from external files.
	 *
	 * @since 1.0.0
	 */
	private function load_core_translations() {
		if ( null === $this->core_translations ) {
			// Cache the file paths.
			if ( null === self::$core_translations_files ) {
				$vars_dir = __DIR__ . '/../../Vars/';

				self::$core_translations_files = array(
					$vars_dir . 'i18n-admin.php',
					$vars_dir . 'i18n-core.php',
				);
			}

			$this->core_translations = array();

			// Load translations from both files.
			foreach ( self::$core_translations_files as $file_path ) {
				if ( file_exists( $file_path ) ) {
					$translations = include $file_path;

					if ( is_array( $translations ) ) {
						// Convert array to associative array with translation as key.
						foreach ( $translations as $translation ) {
							$this->core_translations[ $translation ] = 1;
						}
					}
				}
			}
		}
	}

	/**
	 * Check if the translation is a core WordPress translation.
	 *
	 * @since 1.0.0
	 *
	 * @param array $parameters           Array with information about the parameters.
	 * @param array $function_param_specs Parameter specifications for the function.
	 * @return bool True if it's a core translation, false otherwise.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	private function is_core_translation( $parameters, $function_param_specs ) {
		// Load core translations if not already loaded.
		$this->load_core_translations();

		$text_param    = null;
		$context_param = null;

		// Get text parameter.
		foreach ( $function_param_specs as $position => $name ) {
			if ( in_array( $name, array( 'text', 'single', 'singular' ), true ) ) {
				$text_param = PassedParameters::getParameterFromStack( $parameters, $position, $name );
				break;
			}
		}

		// Get context parameter if it exists.
		foreach ( $function_param_specs as $position => $name ) {
			if ( 'context' === $name ) {
				$context_param = PassedParameters::getParameterFromStack( $parameters, $position, $name );
				break;
			}
		}

		// Check if text parameter exists and is a string literal.
		if ( false === $text_param || ! isset( $text_param['clean'] ) || '' === trim( $text_param['clean'] ) ) {
			return false;
		}

		$text_content = trim( $text_param['clean'] );

		// Check if context parameter exists and is a string literal.
		if ( false !== $context_param && isset( $context_param['clean'] ) && '' !== trim( $context_param['clean'] ) ) {
			$context_content = trim( $context_param['clean'] );
			$combined_key    = $context_content . "\0" . $text_content;

			// Check if the combined context + text exists in core translations.
			if ( isset( $this->core_translations[ $combined_key ] ) ) {
				return true;
			}

			// Also check without quotes for context-aware functions.
			$context_content_no_quotes = trim( $context_content, "'\"" );
			$text_content_no_quotes    = trim( $text_content, "'\"" );
			$combined_key_no_quotes    = $context_content_no_quotes . "\0" . $text_content_no_quotes;

			if ( isset( $this->core_translations[ $combined_key_no_quotes ] ) ) {
				return true;
			}
		}

		// Check if just the text exists in core translations.
		if ( isset( $this->core_translations[ $text_content ] ) ) {
			return true;
		}

		// Also check without quotes (in case the clean content still has quotes).
		$text_content_no_quotes = trim( $text_content, "'\"" );
		if ( isset( $this->core_translations[ $text_content_no_quotes ] ) ) {
			return true;
		}

		return false;
	}
}
