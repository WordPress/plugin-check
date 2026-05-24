<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Performance;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * This sniff throws a warning when low cache times are set.
 *
 * {@internal VIP uses the Memcached object cache implementation. {@link https://github.com/Automattic/wp-memcached}}
 *
 * @since 0.4.0
 */
class LowExpiryCacheTimeSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @var string
	 */
	protected $group_name = 'cache_functions';

	/**
	 * Functions this sniff is looking for.
	 *
	 * @var array The only requirement for this array is that the top level
	 *            array keys are the names of the functions you're looking for.
	 *            Other than that, the array can have arbitrary content
	 *            depending on your needs.
	 */
	protected $target_functions = [
		'wp_cache_set'     => true,
		'wp_cache_add'     => true,
		'wp_cache_replace' => true,
	];

	/**
	 * List of WP time constants, see https://codex.wordpress.org/Easier_Expression_of_Time_Constants.
	 *
	 * @var array
	 */
	protected $wp_time_constants = [
		'MINUTE_IN_SECONDS' => 60,
		'HOUR_IN_SECONDS'   => 3600,
		'DAY_IN_SECONDS'    => 86400,
		'WEEK_IN_SECONDS'   => 604800,
		'MONTH_IN_SECONDS'  => 2592000,
		'YEAR_IN_SECONDS'   => 31536000,
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
		if ( isset( $parameters[4] ) === false ) {
			// If no cache expiry time, bail (i.e. we don't want to flag for something like feeds where it is cached indefinitely until a hook runs).
			return;
		}

		$param          = $parameters[4];
		$tokensAsString = '';
		$reportPtr      = null;
		$openParens     = 0;

		$message    = 'Cache expiry time could not be determined. Please inspect that the fourth parameter passed to %s() evaluates to 300 seconds or more. Found: "%s"';
		$error_code = 'CacheTimeUndetermined';
		$data       = [ $matched_content, $parameters[4]['raw'] ];

		for ( $i = $param['start']; $i <= $param['end']; $i++ ) {
			if ( isset( Tokens::$emptyTokens[ $this->tokens[ $i ]['code'] ] ) === true ) {
				$tokensAsString .= ' ';
				continue;
			}

			if ( $this->tokens[ $i ]['code'] === T_NS_SEPARATOR ) {
				/*
				 * Ignore namespace separators. If it's part of a global WP time constant, it will be
				 * handled correctly. If it's used in any other context, another token *will* trigger the
				 * "undetermined" warning anyway.
				 */
				continue;
			}

			if ( isset( $reportPtr ) === false ) {
				// Set the report pointer to the first non-empty token we encounter.
				$reportPtr = $i;
			}

			if ( $this->tokens[ $i ]['code'] === T_LNUMBER
				|| $this->tokens[ $i ]['code'] === T_DNUMBER
			) {
				// Integer or float.
				$tokensAsString .= $this->tokens[ $i ]['content'];
				continue;
			}

			if ( $this->tokens[ $i ]['code'] === T_FALSE
				|| $this->tokens[ $i ]['code'] === T_NULL
			) {
				$tokensAsString .= 0;
				continue;
			}

			if ( $this->tokens[ $i ]['code'] === T_TRUE ) {
				$tokensAsString .= 1;
				continue;
			}

			if ( isset( Tokens::$arithmeticTokens[ $this->tokens[ $i ]['code'] ] ) === true ) {
				$tokensAsString .= $this->tokens[ $i ]['content'];
				continue;
			}

			// If using time constants, we need to convert to a number.
			if ( $this->tokens[ $i ]['code'] === T_STRING
				&& isset( $this->wp_time_constants[ $this->tokens[ $i ]['content'] ] ) === true
			) {
				$tokensAsString .= $this->wp_time_constants[ $this->tokens[ $i ]['content'] ];
				continue;
			}

			if ( $this->tokens[ $i ]['code'] === T_OPEN_PARENTHESIS ) {
				$tokensAsString .= $this->tokens[ $i ]['content'];
				++$openParens;
				continue;
			}

			if ( $this->tokens[ $i ]['code'] === T_CLOSE_PARENTHESIS ) {
				$tokensAsString .= $this->tokens[ $i ]['content'];
				--$openParens;
				continue;
			}

			if ( $this->tokens[ $i ]['code'] === T_CONSTANT_ENCAPSED_STRING ) {
				$content = TextStrings::stripQuotes( $this->tokens[ $i ]['content'] );
				if ( is_numeric( $content ) === true ) {
					$tokensAsString .= $content;
					continue;
				}
			}

			// Encountered an unexpected token. Manual inspection needed.
			$this->phpcsFile->addWarning( $message, $reportPtr, $error_code, $data );

			return;
		}

		if ( $tokensAsString === '' ) {
			// Nothing found to evaluate.
			return;
		}

		$tokensAsString = trim( $tokensAsString );

		if ( $openParens !== 0 ) {
			/*
			 * Shouldn't be possible as that would indicate a parse error in the original code,
			 * but let's prevent getting parse errors in the `eval`-ed code.
			 */
			if ( $openParens > 0 ) {
				$tokensAsString .= str_repeat( ')', $openParens );
			} else {
				$tokensAsString = str_repeat( '(', abs( $openParens ) ) . $tokensAsString;
			}
		}

		$time = @eval( "return $tokensAsString;" ); // phpcs:ignore Squiz.PHP.Eval,WordPress.PHP.NoSilencedErrors -- No harm here.

		if ( $time === false ) {
			/*
			 * The eval resulted in a parse error. This will only happen for backfilled
			 * arithmetic operator tokens, like T_POW, on PHP versions in which the token
			 * did not exist. In that case, flag for manual inspection.
			 */
			$this->phpcsFile->addWarning( $message, $reportPtr, $error_code, $data );
			return;
		}

		if ( $time < 300 && (int) $time !== 0 ) {
			$message = 'Low cache expiry time of %s seconds detected. It is recommended to have 300 seconds or more.';
			$data    = [ $time ];

			if ( (string) $time !== $tokensAsString ) {
				$message .= ' Found: "%s"';
				$data[]   = $tokensAsString;
			}

			$this->phpcsFile->addWarning( $message, $reportPtr, 'LowCacheTime', $data );
		}
	}
}
