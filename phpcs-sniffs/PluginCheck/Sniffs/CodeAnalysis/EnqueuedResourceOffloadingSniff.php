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

	/**
	 * The group name for this group of functions.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $group_name = 'Enqueued';

	/**
	 * List of enqueued functions that need to be checked for use of the in_footer and version arguments.
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
	 * False + the empty tokens array.
	 *
	 * This array is enriched with the $emptyTokens array in the register() method.
	 *
	 * @var array<int|string, int|string>
	 */
	private $false_tokens = array(
		\T_FALSE => \T_FALSE,
	);

	/**
	 * Token codes which are "safe" to accept to determine whether a version would evaluate to `false`.
	 *
	 * This array is enriched with the several of the PHPCS token arrays in the register() method.
	 *
	 * @var array<int|string, int|string>
	 */
	private $safe_tokens = array(
		\T_NULL                     => \T_NULL,
		\T_FALSE                    => \T_FALSE,
		\T_TRUE                     => \T_TRUE,
		\T_LNUMBER                  => \T_LNUMBER,
		\T_DNUMBER                  => \T_DNUMBER,
		\T_CONSTANT_ENCAPSED_STRING => \T_CONSTANT_ENCAPSED_STRING,
		\T_START_NOWDOC             => \T_START_NOWDOC,
		\T_NOWDOC                   => \T_NOWDOC,
		\T_END_NOWDOC               => \T_END_NOWDOC,
		\T_OPEN_PARENTHESIS         => \T_OPEN_PARENTHESIS,
		\T_CLOSE_PARENTHESIS        => \T_CLOSE_PARENTHESIS,
		\T_STRING_CONCAT            => \T_STRING_CONCAT,
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * Overloads and calls the parent method to allow for adding additional tokens to the $safe_tokens property.
	 *
	 * @return array
	 */
	public function register() {
		$this->false_tokens += Tokens::$emptyTokens;

		$this->safe_tokens += Tokens::$emptyTokens;
		$this->safe_tokens += Tokens::$assignmentTokens;
		$this->safe_tokens += Tokens::$comparisonTokens;
		$this->safe_tokens += Tokens::$operators;
		$this->safe_tokens += Tokens::$booleanOperators;
		$this->safe_tokens += Tokens::$castTokens;

		return parent::register();
	}

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

		if ( false === $src_param ) {
			return;
		}

		// Known offloading services.
		$look_known_offloading_services = array(
			'code\.jquery\.com',
			'(?<!api\.)cloudflare\.com',
			'cdn\.jsdelivr\.net',
			'cdn\.rawgit\.com',
			'code\.getmdl\.io',
			'bootstrapcdn',
			'cl\.ly',
			'cdn\.datatables\.net',
			'aspnetcdn\.com',
			'ajax\.googleapis\.com',
			'webfonts\.zoho\.com',
			'raw\.githubusercontent\.com',
			'github\.com\/.*\/raw',
			'unpkg\.com',
			'imgur\.com',
			'rawgit\.com',
			'amazonaws\.com',
			'cdn\.tiny\.cloud',
			'tiny\.cloud',
			'tailwindcss\.com',
			'herokuapp\.com',
			'(?<!fonts\.)gstatic\.com',
			'kit\.fontawesome',
			'use\.fontawesome',
			'googleusercontent\.com',
			'placeholder\.com',
			's\.w\.org',
		);

		$pattern = '/(' . implode( '|', $look_known_offloading_services ) . ')/i';

		$error_ptr = $this->phpcsFile->findNext( Tokens::$emptyTokens, $src_param['start'], ( $src_param['end'] + 1 ), true );
		if ( false === $error_ptr ) {
			$error_ptr = $src_param['start'];
		}

		$type = 'script';
		if ( strpos( $matched_content, '_style' ) !== false ) {
			$type = 'style';
		}

		$src_string = $src_param['clean'];

		$matches = array();
		if ( preg_match( $pattern, $src_string, $matches, PREG_OFFSET_CAPTURE ) > 0 ) {
			$this->phpcsFile->addError(
				'Found call to %s() with external resource. Offloading %ss to your servers or any remote service is disallowed.',
				$error_ptr,
				'OffloadedContent',
				array( $matched_content, $type )
			);
		}
	}
}
