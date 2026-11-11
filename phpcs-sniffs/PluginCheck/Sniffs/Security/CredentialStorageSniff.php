<?php
/**
 * CredentialStorageSniff
 *
 * Detects credentials stored in options instead of the Secrets API.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\Security;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\PassedParameters;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Detect credentials stored in options instead of the Secrets API.
 *
 * Since WordPress 7.2, plugins should store API keys, tokens and passwords
 * with wp_set_secret() so the value is encrypted at rest by core.
 *
 * @since 2.3.0
 */
final class CredentialStorageSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	protected $group_name = 'credential_storage';

	/**
	 * List of functions to examine.
	 *
	 * @since 2.3.0
	 *
	 * @var array<string, true>
	 */
	protected $target_functions = array(
		'add_option'            => true,
		'update_option'         => true,
		'add_site_option'       => true,
		'update_site_option'    => true,
		'add_network_option'    => true,
		'update_network_option' => true,
		'register_setting'      => true,
	);

	/**
	 * Position and name of the option parameter for each function.
	 *
	 * @since 2.3.0
	 *
	 * @var array<string, array{position: int, name: string}>
	 */
	private $option_params = array(
		'add_option'            => array(
			'position' => 1,
			'name'     => 'option',
		),
		'update_option'         => array(
			'position' => 1,
			'name'     => 'option',
		),
		'add_site_option'       => array(
			'position' => 1,
			'name'     => 'option',
		),
		'update_site_option'    => array(
			'position' => 1,
			'name'     => 'option',
		),
		'add_network_option'    => array(
			'position' => 2,
			'name'     => 'option',
		),
		'update_network_option' => array(
			'position' => 2,
			'name'     => 'option',
		),
		'register_setting'      => array(
			'position' => 2,
			'name'     => 'option_name',
		),
	);

	/**
	 * Process the parameters of a matched function.
	 *
	 * @since 2.3.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 *
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$param = $this->option_params[ $matched_content ];

		$option_param = PassedParameters::getParameterFromStack( $parameters, $param['position'], $param['name'] );

		// Only plain string option names can be checked reliably.
		if ( false === $option_param || ! preg_match( '/^(\'[^\']*\'|"[^"$]*")$/', $option_param['clean'] ) ) {
			return;
		}

		$option_name = TextStrings::stripQuotes( $option_param['clean'] );

		if ( ! $this->is_credential_name( $option_name ) || $this->is_guarded_fallback( $stackPtr ) ) {
			return;
		}

		$is_network = false !== strpos( $matched_content, '_site_' ) || false !== strpos( $matched_content, '_network_' );

		$this->phpcsFile->addWarning(
			'The option "%1$s" appears to store a credential outside the Secrets API. Since WordPress 7.2, use %2$s() and %3$s() so the value is encrypted at rest with core key management and rotation, instead of plain text or custom encryption. Check wp_is_secrets_api_available() (after function_exists( \'wp_is_secrets_api_available\' ) on older WordPress versions) and keep your current storage only as a fallback when it returns false.',
			$stackPtr,
			'CredentialOutsideSecretsAPI',
			array(
				$option_name,
				$is_network ? 'wp_set_network_secret' : 'wp_set_secret',
				$is_network ? 'wp_get_network_secret' : 'wp_get_secret',
			)
		);
	}

	/**
	 * Checks whether an option name looks like it holds a credential.
	 *
	 * @since 2.3.0
	 *
	 * @param string $option_name Option name.
	 * @return bool True when the option name looks like a credential.
	 */
	private function is_credential_name( $option_name ) {
		// Names like "foo_token_expires" or "foo_api_key_valid" hold metadata, not the secret.
		if ( preg_match( '/[_-](expire[sd]?|expires[_-](at|in|on)|expiry|expiration|ttl|set|valid|status|hint|masked|last4)$/i', $option_name ) ) {
			return false;
		}

		// Match whole name parts only, so "tokenizer" or "passwordless" are not flagged.
		return (bool) preg_match(
			'/(?<![a-z0-9])(?:api[_-]?keys?|api[_-]?secrets?|secrets?|tokens?|passwords?|passwd|pass|pwd|private[_-]?keys?|license[_-]?keys?)(?![a-z0-9])/i',
			$option_name
		);
	}

	/**
	 * Checks whether the option write only runs when the Secrets API is not available.
	 *
	 * This is the case when the write is inside a branch that is only reached when an
	 * availability check fails, or after an if block that checks availability and
	 * returns early. Conditions that mix positive and negated checks keep the warning.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the option function call.
	 * @return bool True when the option write is a guarded fallback.
	 */
	private function is_guarded_fallback( $stackPtr ) {
		$tokens     = $this->phpcsFile->getTokens();
		$conditions = $tokens[ $stackPtr ]['conditions'];

		foreach ( $conditions as $ptr => $code ) {
			if ( in_array( $code, array( T_IF, T_ELSEIF, T_ELSE ), true ) && $this->is_unavailable_branch( $ptr ) ) {
				return true;
			}
		}

		// Early return, for example "if ( wp_is_secrets_api_available() ) { return wp_set_secret( ... ); }".
		$scope_start = 0;
		if ( ! empty( $conditions ) ) {
			end( $conditions );
			$scope_start = key( $conditions );
		}

		for ( $i = $stackPtr - 1; $i > $scope_start; $i-- ) {
			if ( T_IF !== $tokens[ $i ]['code'] || $tokens[ $i ]['conditions'] !== $conditions || ! isset( $tokens[ $i ]['scope_opener'], $tokens[ $i ]['scope_closer'] ) ) {
				continue;
			}

			if ( 1 === $this->get_availability_check( $i ) && $this->block_always_exits( $i ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether an if, elseif or else branch only runs when the Secrets API is not available.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the T_IF, T_ELSEIF or T_ELSE token.
	 * @return bool True for a negated availability check, or for a later branch of a positive check.
	 */
	private function is_unavailable_branch( $stackPtr ) {
		$tokens = $this->phpcsFile->getTokens();

		if ( T_ELSE !== $tokens[ $stackPtr ]['code'] && -1 === $this->get_availability_check( $stackPtr ) ) {
			return true;
		}

		while ( T_IF !== $tokens[ $stackPtr ]['code'] ) {
			// Go back to the previous if or elseif in the chain.
			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true );
			if ( false === $prev || T_CLOSE_CURLY_BRACKET !== $tokens[ $prev ]['code'] || ! isset( $tokens[ $prev ]['scope_condition'] ) ) {
				return false;
			}

			$stackPtr = $tokens[ $prev ]['scope_condition'];

			if ( 1 === $this->get_availability_check( $stackPtr ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether an if block ends with a return, exit or throw at its top level.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the T_IF token.
	 * @return bool True when the block always leaves the current scope.
	 */
	private function block_always_exits( $stackPtr ) {
		$tokens = $this->phpcsFile->getTokens();
		$depth  = count( $tokens[ $stackPtr ]['conditions'] ) + 1;

		for ( $i = $tokens[ $stackPtr ]['scope_opener'] + 1; $i < $tokens[ $stackPtr ]['scope_closer']; $i++ ) {
			if ( in_array( $tokens[ $i ]['code'], array( T_RETURN, T_EXIT, T_THROW ), true ) && count( $tokens[ $i ]['conditions'] ) === $depth ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detects a Secrets API availability check in the condition of an if or elseif.
	 *
	 * Supported checks are wp_is_secrets_api_available() and
	 * function_exists( 'wp_set_secret' ), optionally negated with "!".
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the T_IF or T_ELSEIF token.
	 * @return int 1 for a positive check, -1 for a negated check, 0 for none or mixed checks.
	 */
	private function get_availability_check( $stackPtr ) {
		$tokens = $this->phpcsFile->getTokens();

		if ( ! isset( $tokens[ $stackPtr ]['parenthesis_opener'], $tokens[ $stackPtr ]['parenthesis_closer'] ) ) {
			return 0;
		}

		$found = array();

		for ( $i = $tokens[ $stackPtr ]['parenthesis_opener'] + 1; $i < $tokens[ $stackPtr ]['parenthesis_closer']; $i++ ) {
			$name = $this->get_function_name( $i );
			$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $i + 1, null, true );

			if ( '' === $name || false === $next || T_OPEN_PARENTHESIS !== $tokens[ $next ]['code'] ) {
				continue;
			}

			$is_check = 'wp_is_secrets_api_available' === $name;

			if ( 'function_exists' === $name ) {
				$arg      = PassedParameters::getParameter( $this->phpcsFile, $i, 1, 'function' );
				$is_check = false !== $arg && in_array(
					strtolower( ltrim( TextStrings::stripQuotes( $arg['clean'] ), '\\' ) ),
					array( 'wp_set_secret', 'wp_set_network_secret', 'wp_is_secrets_api_available' ),
					true
				);
			}

			if ( $is_check ) {
				$prev    = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $i - 1, null, true );
				$found[] = ( false !== $prev && T_BOOLEAN_NOT === $tokens[ $prev ]['code'] ) ? -1 : 1;
			}
		}

		$found = array_unique( $found );

		return 1 === count( $found ) ? reset( $found ) : 0;
	}

	/**
	 * Gets the lowercase function name of a T_STRING or fully qualified name token.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the token.
	 * @return string Function name without leading backslash, or empty string for other tokens.
	 */
	private function get_function_name( $stackPtr ) {
		$token = $this->phpcsFile->getTokens()[ $stackPtr ];

		// PHP 8 tokenizes \wp_set_secret as a single T_NAME_FULLY_QUALIFIED token.
		if ( T_STRING !== $token['code'] && ( ! defined( 'T_NAME_FULLY_QUALIFIED' ) || T_NAME_FULLY_QUALIFIED !== $token['code'] ) ) {
			return '';
		}

		return strtolower( ltrim( $token['content'], '\\' ) );
	}
}
