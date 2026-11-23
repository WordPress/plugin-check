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
use PHPCSUtils\Tokens\Collections;
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

		if ( ! $this->is_credential_name( $option_name ) || $this->is_hashed_value( $parameters, $matched_content, $param['position'] + 1 ) || $this->is_guarded_fallback( $stackPtr ) ) {
			return;
		}

		$is_network = false !== strpos( $matched_content, '_site_' ) || false !== strpos( $matched_content, '_network_' );

		$this->phpcsFile->addWarning(
			'The option "%1$s" appears to store a credential outside the Secrets API. Since WordPress 7.2, use %2$s() and %3$s() so the value is encrypted at rest with core key management and rotation, instead of plain text or custom encryption.%4$s Keep your current storage only as a fallback when wp_is_secrets_api_available() returns false (check function_exists( \'wp_is_secrets_api_available\' ) first on older WordPress versions). Do not fall back when a write returns a WP_Error, because a host can manage secrets itself.',
			$stackPtr,
			'CredentialOutsideSecretsAPI',
			array(
				$option_name,
				$is_network ? 'wp_set_network_secret' : 'wp_set_secret',
				$is_network ? 'wp_get_network_secret' : 'wp_get_secret',
				$is_network ? '' : ' Use wp_import_option_as_secret() to move an existing option.',
			)
		);
	}

	/**
	 * Checks whether the written value is a password hash.
	 *
	 * A password that the plugin only verifies must stay hashed, not move to reversible
	 * encryption. Only a value that is a single wp_hash_password() or password_hash()
	 * call counts, so "wp_hash_password( $a ) . $b" keeps the warning.
	 *
	 * @since 2.3.0
	 *
	 * @param array  $parameters      Array with information about the parameters.
	 * @param string $matched_content The matched function name in lowercase.
	 * @param int    $position        Position of the value parameter.
	 * @return bool True when the value is the result of a password hashing function.
	 */
	private function is_hashed_value( $parameters, $matched_content, $position ) {
		// register_setting() has no value parameter.
		if ( 'register_setting' === $matched_content ) {
			return false;
		}

		$value = PassedParameters::getParameterFromStack( $parameters, $position, 'value' );

		if ( false === $value ) {
			return false;
		}

		$tokens = $this->phpcsFile->getTokens();
		$skip   = Tokens::$emptyTokens + array( T_NS_SEPARATOR => T_NS_SEPARATOR );
		$name   = $this->phpcsFile->findNext( $skip, $value['start'], $value['end'] + 1, true );

		if ( false === $name || ! in_array( $this->get_function_name( $name ), array( 'wp_hash_password', 'password_hash' ), true ) ) {
			return false;
		}

		$opener = $this->phpcsFile->findNext( Tokens::$emptyTokens, $name + 1, $value['end'] + 1, true );

		if ( false === $opener || T_OPEN_PARENTHESIS !== $tokens[ $opener ]['code'] || ! isset( $tokens[ $opener ]['parenthesis_closer'] ) ) {
			return false;
		}

		// The call must be the whole value, not the start of a larger expression.
		return false === $this->phpcsFile->findNext( Tokens::$emptyTokens, $tokens[ $opener ]['parenthesis_closer'] + 1, $value['end'] + 1, true );
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
		if ( preg_match( '/[_-](expire[sd]?|expires[_-](at|in|on)|expiry|expiration|ttl|set|valid|status|hint|masked|last4|enabled|url|label|limit|length|page|used|count|required)$/i', $option_name ) ) {
			return false;
		}

		// "max_tokens" is a model limit, not a credential.
		if ( preg_match( '/(?<![a-z0-9])max[_-]tokens$/i', $option_name ) ) {
			return false;
		}

		// Match whole name parts only, so "tokenizer" or "passwordless" are not flagged.
		return (bool) preg_match(
			'/(?<![a-z0-9])(?:api[_-]?keys?|api[_-]?secrets?|secrets?|secret[_-]?keys?|access[_-]?tokens?|auth[_-]?tokens?|tokens?|passwords?|passphrases?|passwd|pass|pwd|credentials?|private[_-]?keys?|license[_-]?keys?)(?![a-z0-9])/i',
			$option_name
		);
	}

	/**
	 * Checks whether the option write only runs when the Secrets API is not available.
	 *
	 * This is the case when the write is inside a branch that is only reached when the
	 * Secrets API is unavailable, inside the matching side of a ternary, or after an if
	 * block that checks availability and always exits. A condition that can also be met
	 * when the API is available, for example "! wp_is_secrets_api_available() || $force",
	 * keeps the warning.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the option function call.
	 * @return bool True when the option write is a guarded fallback.
	 */
	private function is_guarded_fallback( $stackPtr ) {
		$conditions = $this->phpcsFile->getTokens()[ $stackPtr ]['conditions'];

		return $this->is_in_unavailable_branch( $conditions )
			|| $this->is_after_early_exit( $stackPtr, $conditions )
			|| $this->is_in_unavailable_ternary( $stackPtr );
	}

	/**
	 * Checks whether the write is inside an if, elseif or else branch that only runs when the API is not available.
	 *
	 * Conditions are walked from the innermost scope outwards and stop at the nearest
	 * function or closure. A guard outside of it controls the declaration, not
	 * the later calls, and the availability can change by then.
	 *
	 * @since 2.3.0
	 *
	 * @param array<int, int|string> $conditions Conditions of the option function call.
	 * @return bool True when an enclosing branch only runs without the Secrets API.
	 */
	private function is_in_unavailable_branch( $conditions ) {
		$boundaries = Collections::functionDeclarationTokens();

		foreach ( array_reverse( $conditions, true ) as $ptr => $code ) {
			if ( isset( $boundaries[ $code ] ) ) {
				return false;
			}

			if ( in_array( $code, array( T_IF, T_ELSEIF, T_ELSE ), true ) && $this->is_unavailable_branch( $ptr ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether an earlier if block in the same scope checks availability and always exits.
	 *
	 * Example: "if ( wp_is_secrets_api_available() ) { return wp_set_secret( ... ); }".
	 *
	 * @since 2.3.0
	 *
	 * @param int                    $stackPtr   Position of the option function call.
	 * @param array<int, int|string> $conditions Conditions of the option function call.
	 * @return bool True when the write can only be reached without the Secrets API.
	 */
	private function is_after_early_exit( $stackPtr, $conditions ) {
		$tokens      = $this->phpcsFile->getTokens();
		$scope_start = 0;

		if ( ! empty( $conditions ) ) {
			end( $conditions );
			$scope_start = key( $conditions );
		}

		for ( $i = $stackPtr - 1; $i > $scope_start; $i-- ) {
			if ( T_IF !== $tokens[ $i ]['code'] || $tokens[ $i ]['conditions'] !== $conditions || ! isset( $tokens[ $i ]['scope_opener'], $tokens[ $i ]['scope_closer'] ) ) {
				continue;
			}

			// A write inside the condition runs before the check is decided, so the block cannot guard it.
			if ( $tokens[ $i ]['scope_closer'] > $stackPtr ) {
				continue;
			}

			if ( $this->condition_implies_unavailable( $i, false ) && $this->block_always_exits( $i ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether the write is on the side of a ternary that only runs without the Secrets API.
	 *
	 * Example: "function_exists( 'wp_set_secret' ) ? wp_set_secret( ... ) : update_option( ... )".
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the option function call.
	 * @return bool True when the write is on the unavailable side of a ternary.
	 */
	private function is_in_unavailable_ternary( $stackPtr ) {
		$tokens   = $this->phpcsFile->getTokens();
		$depth    = $this->get_depth( $stackPtr );
		$colon    = false;
		$question = false;

		for ( $i = $stackPtr - 1; $i >= 0; $i-- ) {
			if ( $this->is_expression_boundary( $i, $depth ) ) {
				break;
			}

			if ( $this->get_depth( $i ) !== $depth ) {
				continue;
			}

			if ( T_INLINE_THEN === $tokens[ $i ]['code'] ) {
				$question = $i;
				break;
			}

			if ( T_INLINE_ELSE === $tokens[ $i ]['code'] && false === $colon ) {
				$colon = $i;
			}
		}

		if ( false === $question ) {
			return false;
		}

		$start = $question;

		while ( $start > 0 && ! $this->is_expression_boundary( $start - 1, $depth ) ) {
			--$start;
		}

		// Before the colon the write runs when the condition is true, after it when the condition is false.
		return $this->implies_unavailable( $start, $question - 1, $depth, false === $colon );
	}

	/**
	 * Checks whether a token ends the expression that contains an option function call.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the token.
	 * @param int $depth    Parenthesis depth of the expression.
	 * @return bool True for statement and argument boundaries, or for a token outside the enclosing parentheses.
	 */
	private function is_expression_boundary( $stackPtr, $depth ) {
		$code        = $this->phpcsFile->getTokens()[ $stackPtr ]['code'];
		$token_depth = $this->get_depth( $stackPtr );

		// Commas and array arrows inside the arguments of a nested call do not end the expression.
		// "and", "or" and "xor" bind weaker than a ternary, so "a ? b : c or write()" is not one ternary.
		return $token_depth < $depth
			|| in_array( $code, array( T_SEMICOLON, T_OPEN_TAG, T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET ), true )
			|| ( $token_depth === $depth && in_array( $code, array( T_COMMA, T_DOUBLE_ARROW, T_LOGICAL_AND, T_LOGICAL_OR, T_LOGICAL_XOR ), true ) );
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

		if ( T_ELSE !== $tokens[ $stackPtr ]['code'] && $this->condition_implies_unavailable( $stackPtr, true ) ) {
			return true;
		}

		while ( T_IF !== $tokens[ $stackPtr ]['code'] ) {
			// Go back to the previous if or elseif in the chain.
			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true );
			if ( false === $prev || T_CLOSE_CURLY_BRACKET !== $tokens[ $prev ]['code'] || ! isset( $tokens[ $prev ]['scope_condition'] ) ) {
				return false;
			}

			$stackPtr = $tokens[ $prev ]['scope_condition'];

			if ( $this->condition_implies_unavailable( $stackPtr, false ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether an if block ends with a return, exit or throw at its top level.
	 *
	 * Only a statement of its own counts. An exit inside an expression such as
	 * "$fail && exit;" does not always run.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the T_IF token.
	 * @return bool True when the block always leaves the current scope.
	 */
	private function block_always_exits( $stackPtr ) {
		$tokens = $this->phpcsFile->getTokens();
		$opener = $tokens[ $stackPtr ]['scope_opener'];
		$depth  = count( $tokens[ $stackPtr ]['conditions'] ) + 1;

		for ( $i = $opener + 1; $i < $tokens[ $stackPtr ]['scope_closer']; $i++ ) {
			if ( ! in_array( $tokens[ $i ]['code'], array( T_RETURN, T_EXIT, T_THROW ), true ) || count( $tokens[ $i ]['conditions'] ) !== $depth ) {
				continue;
			}

			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $i - 1, null, true );

			if ( $prev === $opener || in_array( $tokens[ $prev ]['code'], array( T_SEMICOLON, T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether the condition of an if or elseif tells that the Secrets API is not available.
	 *
	 * @since 2.3.0
	 *
	 * @param int  $stackPtr  Position of the T_IF or T_ELSEIF token.
	 * @param bool $when_true True for the code that runs when the condition is true,
	 *                        false for the code that runs when it is false.
	 * @return bool True when that code only runs without the Secrets API.
	 */
	private function condition_implies_unavailable( $stackPtr, $when_true ) {
		$tokens = $this->phpcsFile->getTokens();

		if ( ! isset( $tokens[ $stackPtr ]['parenthesis_opener'], $tokens[ $stackPtr ]['parenthesis_closer'] ) ) {
			return false;
		}

		$opener = $tokens[ $stackPtr ]['parenthesis_opener'];

		return $this->implies_unavailable( $opener + 1, $tokens[ $stackPtr ]['parenthesis_closer'] - 1, $this->get_depth( $opener ) + 1, $when_true );
	}

	/**
	 * Checks whether a condition tells that the Secrets API is not available.
	 *
	 * Only checks at the top level of the condition count. "! check() && $a" is true
	 * only without the API, so it qualifies when true; "check() || $a" is false only
	 * without the API, so it qualifies when false. The other operator needs a check in
	 * every operand: "function_exists( 'wp_is_secrets_api_available' ) && wp_is_secrets_api_available()"
	 * is false without the API, but "check() && $a" is also false with the API.
	 * Conditions that mix "&&" and "||", or that use a comparison, a ternary or xor, do
	 * not qualify. Supported checks are wp_is_secrets_api_available(),
	 * function_exists( 'wp_set_secret' ) and version_compare() against WordPress 7.2,
	 * optionally negated with "!".
	 *
	 * @since 2.3.0
	 *
	 * @param int  $start     First token of the condition.
	 * @param int  $end       Last token of the condition.
	 * @param int  $depth     Parenthesis depth of the top level of the condition.
	 * @param bool $when_true True when the guarded code runs if the condition is true,
	 *                        false when it runs if the condition is false.
	 * @return bool True when the guarded code only runs without the Secrets API.
	 */
	private function implies_unavailable( $start, $end, $depth, $when_true ) {
		$tokens     = $this->phpcsFile->getTokens();
		$and_tokens = array( T_BOOLEAN_AND, T_LOGICAL_AND );
		$or_tokens  = array( T_BOOLEAN_OR, T_LOGICAL_OR );
		$strict     = $when_true ? $or_tokens : $and_tokens;
		$found      = array();
		$checks     = 0;
		$joins      = 0;
		$has_and    = false;
		$has_or     = false;

		for ( $i = $start; $i <= $end; $i++ ) {
			if ( $this->get_depth( $i ) !== $depth ) {
				continue;
			}

			$code = $tokens[ $i ]['code'];

			// Operators that are not modeled here, or a comparison such as "check() === false", can change what the check means.
			if ( in_array( $code, array( T_LOGICAL_XOR, T_INLINE_THEN, T_BITWISE_OR, T_BITWISE_AND, T_BITWISE_XOR ), true ) || isset( Tokens::$comparisonTokens[ $code ] ) ) {
				return false;
			}

			$has_and = $has_and || in_array( $code, $and_tokens, true );
			$has_or  = $has_or || in_array( $code, $or_tokens, true );
			$joins  += in_array( $code, $strict, true ) ? 1 : 0;

			$sign = $this->get_check_sign( $i );

			if ( 0 !== $sign ) {
				$found[ $sign ] = true;
				++$checks;
			}
		}

		// -1 is a negated check, 1 a positive check. Mixed checks keep the warning.
		if ( array( $when_true ? -1 : 1 ) !== array_keys( $found ) || ( $has_and && $has_or ) ) {
			return false;
		}

		// With the strict operator every operand must be a check: "check() && $a" can be false with the API.
		return 0 === $joins || $checks > $joins;
	}

	/**
	 * Detects a Secrets API availability check at a token.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the token.
	 * @return int 1 when code that runs if the call is false only runs without the API,
	 *             -1 when code that runs if the call is true only runs without the API,
	 *             0 when the token is not an availability check.
	 */
	private function get_check_sign( $stackPtr ) {
		$tokens = $this->phpcsFile->getTokens();
		$name   = $this->get_function_name( $stackPtr );
		$next   = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );

		if ( '' === $name || false === $next || T_OPEN_PARENTHESIS !== $tokens[ $next ]['code'] ) {
			return 0;
		}

		$sign = 0;

		if ( 'wp_is_secrets_api_available' === $name ) {
			$sign = 1;
		} elseif ( 'function_exists' === $name ) {
			$arg  = PassedParameters::getParameter( $this->phpcsFile, $stackPtr, 1, 'function' );
			$sign = false !== $arg && in_array(
				strtolower( ltrim( TextStrings::stripQuotes( $arg['clean'] ), '\\' ) ),
				array( 'wp_set_secret', 'wp_set_network_secret', 'wp_is_secrets_api_available' ),
				true
			) ? 1 : 0;
		} elseif ( 'version_compare' === $name ) {
			$sign = $this->get_version_check_sign( $stackPtr );
		}

		// PHPCS 3 splits a fully qualified name into a namespace separator and a name, so skip the separator.
		$skip = Tokens::$emptyTokens + array( T_NS_SEPARATOR => T_NS_SEPARATOR );
		$prev = $this->phpcsFile->findPrevious( $skip, $stackPtr - 1, null, true );

		// Every "!" flips the meaning, so "!! check()" is a positive check.
		while ( false !== $prev && T_BOOLEAN_NOT === $tokens[ $prev ]['code'] ) {
			$sign = -$sign;
			$prev = $this->phpcsFile->findPrevious( $skip, $prev - 1, null, true );
		}

		return $sign;
	}

	/**
	 * Detects a version_compare() check of the WordPress version against the Secrets API version.
	 *
	 * Supports "version_compare( get_bloginfo( 'version' ), '7.2', '<' )" and the same
	 * check with $wp_version or $GLOBALS['wp_version']. Operators that leave the
	 * Secrets API possible, for example "< 7.3", are not checks.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the version_compare token.
	 * @return int 1 when code that runs if the call is false only runs without the API,
	 *             -1 when code that runs if the call is true only runs without the API,
	 *             0 for any other comparison.
	 */
	private function get_version_check_sign( $stackPtr ) {
		$version  = PassedParameters::getParameter( $this->phpcsFile, $stackPtr, 1, 'version1' );
		$target   = PassedParameters::getParameter( $this->phpcsFile, $stackPtr, 2, 'version2' );
		$operator = PassedParameters::getParameter( $this->phpcsFile, $stackPtr, 3, 'operator' );

		if ( false === $version || false === $target || false === $operator ) {
			return 0;
		}

		$wp_version_pattern = '/^(\\\\?get_bloginfo\([\'"]version[\'"]\)|\$wp_version|\$GLOBALS\[[\'"]wp_version[\'"]\])$/i';
		$target_version     = TextStrings::stripQuotes( $target['clean'] );

		if ( ! preg_match( $wp_version_pattern, preg_replace( '/\s+/', '', $version['clean'] ) ) || ! preg_match( '/^\d+(\.\d+)*$/', $target_version ) ) {
			return 0;
		}

		switch ( strtolower( TextStrings::stripQuotes( $operator['clean'] ) ) ) {
			case '<':
			case 'lt':
				return version_compare( $target_version, '7.2', '<=' ) ? -1 : 0;
			case '<=':
			case 'le':
				return version_compare( $target_version, '7.2', '<' ) ? -1 : 0;
			case '>=':
			case 'ge':
				return version_compare( $target_version, '7.2', '<=' ) ? 1 : 0;
			case '>':
			case 'gt':
				return version_compare( $target_version, '7.2', '<' ) ? 1 : 0;
			default:
				return 0;
		}
	}

	/**
	 * Gets the parenthesis depth of a token.
	 *
	 * @since 2.3.0
	 *
	 * @param int $stackPtr Position of the token.
	 * @return int Number of parentheses the token is inside.
	 */
	private function get_depth( $stackPtr ) {
		$tokens = $this->phpcsFile->getTokens();

		return isset( $tokens[ $stackPtr ]['nested_parenthesis'] ) ? count( $tokens[ $stackPtr ]['nested_parenthesis'] ) : 0;
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
