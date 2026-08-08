<?php
/**
 * PublicContentExportSniff
 *
 * Detects when post content is written to a file or exposed through a public
 * endpoint without apparent access-control guards.
 *
 * @package PluginCheck
 * @since 2.1.0
 */

namespace PluginCheckCS\PluginCheck\Sniffs\Security;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\MessageHelper;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Flags functions that write or expose post content without access-control guards.
 *
 * Advisory check for plugins that export restricted post content through
 * alternative public surfaces such as static files, feeds, REST-like routes,
 * or Markdown endpoints. The check is advisory because third-party access
 * control is not statically knowable.
 *
 * @since 2.1.0
 */
final class PublicContentExportSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	protected $group_name = 'public_content_export';

	/**
	 * List of functions that can export post content, mapped to the parameter
	 * index that holds the content/data being exported.
	 *
	 * @since 2.1.0
	 *
	 * @var array<string, int> Key is function name, value is parameter position.
	 */
	private $content_param_positions = array(
		'file_put_contents' => 2,
		'fwrite'            => 2,
		'fputs'             => 2,
	);

	/**
	 * List of function names that indicate post content is being read.
	 *
	 * @since 2.1.0
	 *
	 * @var array<string, true>
	 */
	private $post_content_functions = array(
		'get_the_content' => true,
		'the_content'     => true,
		'get_the_excerpt' => true,
		'the_excerpt'     => true,
		'get_post_field'  => true,
		'apply_filters'   => true,
	);

	/**
	 * WordPress filter names that imply content is being rendered for export.
	 *
	 * When apply_filters is called with one of these, it is a strong signal
	 * that post content is being surfaced through an alternative channel.
	 *
	 * @since 2.1.0
	 *
	 * @var array<string, true>
	 */
	private $content_filters = array(
		'the_content'        => true,
		'the_content_export' => true,
		'the_content_feed'   => true,
		'the_content_rss'    => true,
		'the_excerpt'        => true,
		'the_excerpt_export' => true,
		'the_excerpt_rss'    => true,
	);

	/**
	 * List of function names that suggest an access-control guard is present.
	 *
	 * @since 2.1.0
	 *
	 * @var array<string, true>
	 */
	private $guard_functions = array(
		'post_password_required' => true,
		'current_user_can'       => true,
		'is_post_type_viewable'  => true,
		'is_user_logged_in'      => true,
	);

	/**
	 * Key list: override the parent's empty target_functions approach.
	 *
	 * {@inheritDoc}
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	protected $target_functions = array(
		'file_put_contents' => true,
		'fwrite'            => true,
		'fputs'             => true,
	);

	/**
	 * Look for post-content references inside the content parameter of a
	 * matched export function.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $stackPtr        Position of the function name token.
	 * @param string $group_name      The group name that was matched.
	 * @param string $matched_content The matched function name (lowercase).
	 * @param array  $parameters      Parsed parameter information.
	 *
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$param_position = isset( $this->content_param_positions[ $matched_content ] )
			? $this->content_param_positions[ $matched_content ]
			: 1;

		$content_param = PassedParameters::getParameterFromStack( $parameters, $param_position, array() );

		if ( false === $content_param ) {
			return;
		}

		// Scan the content parameter tokens for post-content signals.
		if ( ! $this->param_contains_post_content( $content_param['start'], $content_param['end'], $stackPtr ) ) {
			return;
		}

		// Check whether an access-control guard exists in the same function scope.
		if ( $this->has_access_guard_in_scope( $stackPtr ) ) {
			return;
		}

		MessageHelper::addMessage(
			$this->phpcsFile,
			'Post content is exported without an apparent access-control check. Ensure you guard against password-protected or restricted content by checking post_password_required() and providing a filter that allows site owners to veto the export.',
			$content_param['start'],
			false,
			'PostContentExport'
		);
	}

	/**
	 * Scans tokens between start and end for post-content signal patterns.
	 *
	 * Looks for function calls like get_the_content(), the_content(), and
	 * for property access like $post->post_content. Also traces back
	 * variable assignments in the same scope to find post-content sources.
	 *
	 * @since 2.1.0
	 *
	 * @param int $start    Start token pointer.
	 * @param int $end      End token pointer.
	 * @param int $stackPtr Position of the export function call (for scope lookup).
	 *
	 * @return bool True if the parameter references post content.
	 */
	private function param_contains_post_content( $start, $end, $stackPtr ) {
		// First, check for direct post-content signals in the parameter range.
		if ( $this->token_range_has_post_content_signal( $start, $end ) ) {
			return true;
		}

		// No direct signal: collect variables and trace their assignments.
		$found_variables = array();
		for ( $i = $start; $i <= $end; $i++ ) {
			if ( T_VARIABLE === $this->tokens[ $i ]['code'] ) {
				$found_variables[ $this->tokens[ $i ]['content'] ] = true;
			}
		}

		if ( empty( $found_variables ) ) {
			return false;
		}

		$scope_opener = $this->get_scope_opener( $stackPtr );
		$scope_start  = ( null !== $scope_opener ) ? $scope_opener + 1 : 0;

		$visited = array();

		return $this->variable_assigned_from_post_content( $found_variables, $stackPtr, $scope_start, $visited );
	}

	/**
	 * Scans a token range for direct post-content signals only.
	 *
	 * Detects function calls (get_the_content, the_content, etc.) and
	 * property access ($post->post_content) without following variables.
	 *
	 * @since 2.1.0
	 *
	 * @param int $start Start token pointer.
	 * @param int $end   End token pointer.
	 *
	 * @return bool True if a direct post-content signal is found.
	 */
	private function token_range_has_post_content_signal( $start, $end ) {
		for ( $i = $start; $i <= $end; $i++ ) {
			$code = $this->tokens[ $i ]['code'];

			// Detect calls to content-reading functions like the_content().
			if ( T_STRING === $code ) {
				$func_lower = strtolower( $this->tokens[ $i ]['content'] );

				if ( ! isset( $this->post_content_functions[ $func_lower ] ) ) {
					continue;
				}

				$next_non_empty = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $i + 1 ), null, true );
				if ( false === $next_non_empty || T_OPEN_PARENTHESIS !== $this->tokens[ $next_non_empty ]['code'] ) {
					continue;
				}

				// For apply_filters, verify the filter name is content-related.
				if ( 'apply_filters' === $func_lower ) {
					if ( $this->is_content_filter_call( $next_non_empty ) ) {
						return true;
					}
					continue;
				}

				// For get_post_field, check if the first argument is 'post_content'.
				if ( 'get_post_field' === $func_lower && ! $this->is_post_content_field_arg( $next_non_empty ) ) {
					continue;
				}

				return true;
			}

			// Property access: $post->post_content, $some_obj->post_content.
			if ( T_OBJECT_OPERATOR === $code ) {
				$next_non_empty = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $i + 1 ), null, true );
				if ( false !== $next_non_empty
					&& T_STRING === $this->tokens[ $next_non_empty ]['code']
					&& 'post_content' === strtolower( $this->tokens[ $next_non_empty ]['content'] )
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks whether any of the given variables was assigned from a
	 * post-content source within the current scope.
	 *
	 * Traces variable assignments backward, re-scanning each assignment
	 * expression for direct post-content signals and following further
	 * nested variables up to a safe depth.
	 *
	 * @since 2.1.0
	 *
	 * @param array $variables   Map of variable name => true to check.
	 * @param int   $stackPtr    The function call pointer (look-back limit).
	 * @param int   $scope_start First token inside the enclosing scope.
	 * @param array $visited     Already-visited variable names (prevent loops).
	 *
	 * @return bool True if a variable was assigned from post content.
	 */
	private function variable_assigned_from_post_content( $variables, $stackPtr, $scope_start, &$visited ) {
		foreach ( $variables as $var_name => $unused ) {
			if ( isset( $visited[ $var_name ] ) ) {
				continue;
			}
			$visited[ $var_name ] = true;

			$range = $this->find_variable_assignment( $var_name, $stackPtr, $scope_start );
			if ( empty( $range ) ) {
				continue;
			}

			// Direct signal in the assignment expression.
			if ( $this->token_range_has_post_content_signal( $range['start'], $range['end'] ) ) {
				return true;
			}

			// Follow nested variables in the assignment expression.
			$nested = array();
			for ( $i = $range['start']; $i <= $range['end']; $i++ ) {
				if ( T_VARIABLE === $this->tokens[ $i ]['code'] ) {
					$nested_var = $this->tokens[ $i ]['content'];
					if ( ! isset( $visited[ $nested_var ] ) ) {
						$nested[ $nested_var ] = true;
					}
				}
			}

			if ( ! empty( $nested )
				&& $this->variable_assigned_from_post_content( $nested, $stackPtr, $scope_start, $visited )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Finds the most recent assignment expression for a variable in the
	 * current scope, looking backward from the export function call.
	 *
	 * @since 2.1.0
	 *
	 * @param string $var_name    Variable name (including $ prefix).
	 * @param int    $stackPtr    Position of the export function call.
	 * @param int    $scope_start First token index inside the enclosing scope.
	 *
	 * @return array Empty array if not found, or array with 'start' and 'end' keys.
	 */
	private function find_variable_assignment( $var_name, $stackPtr, $scope_start ) {
		$function_scope_ptr = $this->get_scope_opener( $stackPtr );

		for ( $i = $stackPtr - 1; $i >= $scope_start; $i-- ) {
			if ( T_VARIABLE !== $this->tokens[ $i ]['code']
				|| $var_name !== $this->tokens[ $i ]['content']
			) {
				continue;
			}

			// Skip variables declared in a nested scope (not the export call's scope).
			if ( null !== $function_scope_ptr ) {
				$var_scope = $this->get_scope_opener( $i );
				if ( $var_scope !== $function_scope_ptr ) {
					continue;
				}
			}

			$next_non_empty = $this->phpcsFile->findNext(
				Tokens::$emptyTokens,
				( $i + 1 ),
				null,
				true
			);

			if ( false === $next_non_empty || T_EQUAL !== $this->tokens[ $next_non_empty ]['code'] ) {
				continue;
			}

			$assignment_end = $this->phpcsFile->findEndOfStatement( $next_non_empty );

			if ( false === $assignment_end || $assignment_end >= $stackPtr ) {
				continue;
			}

			return array(
				'start' => $next_non_empty + 1,
				'end'   => $assignment_end,
			);
		}

		return array();
	}

	/**
	 * Checks whether an apply_filters call uses a content-related filter name.
	 *
	 * The first argument to apply_filters is the filter tag. If it matches
	 * the_content or similar, this is a content-export signal.
	 *
	 * @since 2.1.0
	 *
	 * @param int $open_paren_ptr Position of the opening parenthesis.
	 *
	 * @return bool True if the filter is content-related.
	 */
	private function is_content_filter_call( $open_paren_ptr ) {
		$closer = isset( $this->tokens[ $open_paren_ptr ]['parenthesis_closer'] )
			? $this->tokens[ $open_paren_ptr ]['parenthesis_closer']
			: null;

		if ( null === $closer ) {
			return false;
		}

		// Find the first non-empty token after the opening paren.
		$first_arg_start = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $open_paren_ptr + 1 ), $closer, true );
		if ( false === $first_arg_start ) {
			return false;
		}

		// Build the filter name string from the first argument's tokens.
		$filter_name = '';
		$paren_depth = 0;

		for ( $i = $first_arg_start; $i < $closer; $i++ ) {
			if ( T_OPEN_PARENTHESIS === $this->tokens[ $i ]['code'] ) {
				++$paren_depth;
				continue;
			}
			if ( T_CLOSE_PARENTHESIS === $this->tokens[ $i ]['code'] ) {
				if ( $paren_depth > 0 ) {
					--$paren_depth;
					continue;
				}
				break;
			}
			if ( T_COMMA === $this->tokens[ $i ]['code'] && 0 === $paren_depth ) {
				break;
			}

			$filter_name .= $this->tokens[ $i ]['content'];
		}

		$filter_name = trim( strtolower( $filter_name ), "'\" \t\n\r\0\x0B" );

		return isset( $this->content_filters[ $filter_name ] );
	}

	/**
	 * Checks whether a get_post_field call uses 'post_content' as the first argument.
	 *
	 * @since 2.1.0
	 *
	 * @param int $open_paren_ptr Position of the opening parenthesis.
	 *
	 * @return bool True if the first argument is 'post_content'.
	 */
	private function is_post_content_field_arg( $open_paren_ptr ) {
		$closer = isset( $this->tokens[ $open_paren_ptr ]['parenthesis_closer'] )
			? $this->tokens[ $open_paren_ptr ]['parenthesis_closer']
			: null;

		if ( null === $closer ) {
			return false;
		}

		$first_arg_start = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $open_paren_ptr + 1 ), $closer, true );
		if ( false === $first_arg_start ) {
			return false;
		}

		$content = trim( $this->tokens[ $first_arg_start ]['content'], "'\" \t\n\r\0\x0B" );

		return 'post_content' === $content;
	}

	/**
	 * Determines whether an access-control guard exists in the same function
	 * scope as the export call.
	 *
	 * Walks backward from the export call through the enclosing function body
	 * looking for calls to post_password_required(), current_user_can(), or
	 * similar guard functions. A guard within the same scope is treated as
	 * evidence the developer has considered access control, suppressing the
	 * advisory warning.
	 *
	 * @since 2.1.0
	 *
	 * @param int $stackPtr Position of the export function call.
	 *
	 * @return bool True if a guard is found.
	 */
	private function has_access_guard_in_scope( $stackPtr ) {
		$scope_opener = $this->get_scope_opener( $stackPtr );

		if ( null === $scope_opener ) {
			return false;
		}

		for ( $i = $stackPtr - 1; $i > $scope_opener; $i-- ) {
			if ( T_STRING !== $this->tokens[ $i ]['code'] ) {
				continue;
			}

			$func_lower = strtolower( $this->tokens[ $i ]['content'] );

			if ( ! isset( $this->guard_functions[ $func_lower ] ) ) {
				continue;
			}

			$next_non_empty = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $i + 1 ), null, true );
			if ( false !== $next_non_empty && T_OPEN_PARENTHESIS === $this->tokens[ $next_non_empty ]['code'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the pointer to the opening brace of the enclosing function scope.
	 *
	 * @since 2.1.0
	 *
	 * @param int $stackPtr A pointer within the function body.
	 *
	 * @return int|null Pointer to the scope opener, or null if not found.
	 */
	private function get_scope_opener( $stackPtr ) {
		if ( empty( $this->tokens[ $stackPtr ]['conditions'] ) ) {
			return null;
		}

		$conditions = array_reverse( $this->tokens[ $stackPtr ]['conditions'], true );

		foreach ( $conditions as $condition_ptr => $condition_code ) {
			if ( in_array( $condition_code, array( T_FUNCTION, T_CLOSURE, T_FN ), true ) ) {
				return isset( $this->tokens[ $condition_ptr ]['scope_opener'] )
					? $this->tokens[ $condition_ptr ]['scope_opener']
					: null;
			}
		}

		return null;
	}
}
