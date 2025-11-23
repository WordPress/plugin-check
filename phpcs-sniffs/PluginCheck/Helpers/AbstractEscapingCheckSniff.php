<?php
/**
 * AbstractEscapingCheckSniff
 *
 * Based on code from {@link https://github.com/WordPress/WordPress-Coding-Standards}
 * which is licensed under {@link https://opensource.org/licenses/MIT}.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Helpers;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Base class for building context-aware escaping checks.
 *
 * @since 1.7.0
 */
abstract class AbstractEscapingCheckSniff extends AbstractSniffHelper {

	/**
	 * Override these in child classes to list applicable escaping functions etc.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $escapingFunctions = array();

	/**
	 * Functions that are often mistaken for escaping functions, but are not SQL or HTML safe.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $notEscapingFunctions = array(
		'addslashes',
		'addcslashes',
		'filter_input',
	);

	/**
	 * Functions that are neither safe nor unsafe. Their output is as safe as the data passed as parameters.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $neutralFunctions = array(
		'implode'      => true,
		'join'         => true,
		'array_keys'   => true,
		'array_values' => true,
		'array_fill'   => true,
		'sprintf'      => true, // Sometimes used to get around formatting table and column names in queries.
		'array_filter' => true,
	);

	/**
	 * Functions with output that can be assumed to be safe. Escaping is always preferred, but alerting on these is unnecessary noise.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $implicitSafeFunctions = array(
		'gmdate'              => true,
		'current_time'        => true,
		'mktime'              => true,
		'get_post_types'      => true,
		'get_charset_collate' => true,
		'get_blog_prefix'     => true,
		'get_post_stati'      => true,
		'count'               => true,
		'strtotime'           => true,
		'uniqid'              => true,
		'md5'                 => true,
		'sha1'                => true,
		'rand'                => true,
		'mt_rand'             => true,
		'max'                 => true,
	);

	/**
	 * Constants that can be assumed safe.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $safe_constants = array(
		'ARRAY_A' => true,
		'OBJECT'  => true,
	);

	/**
	 * Superglobals that are definitively not safe because they contain unescaped user input.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $unsafe_variables = array(
		'$_GET',
		'$_POST',
		'$_REQUEST',
		'$_COOKIE',
		'$_SERVER', // Includes HTTP headers etc that are user input.
		'$_ENV',    // Could contain CGI vars directly from user input.
	);

	/**
	 * Variable names that should only produce a warning when used unescaped.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $warn_only_parameters = array(
		'$this', // Typically object properties will be initialised safely. Escaping is better but using a warning here helps the signal:noise ratio.
	);

	/**
	 * Variable names that will always produce an error when used unescaped.
	 * NOTE: If set, ALL OTHER INPUT will default to a warning.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $error_always_parameters = array(
		// Use with care!
	);

	/**
	 * Keep track of sanitized and unsanitized variables.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $sanitized_variables = array();

	/**
	 * Unsanitized variables array.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $unsanitized_variables = array();

	/**
	 * Used by certain methods for providing extra context.
	 *
	 * @since 1.7.0
	 *
	 * @var int|null
	 */
	protected $methodPtr = null;

	/**
	 * Unsafe pointer.
	 *
	 * @since 1.7.0
	 *
	 * @var int|null
	 */
	protected $unsafe_ptr = null;
	/**
	 * Unsafe expression.
	 *
	 * @since 1.7.0
	 *
	 * @var string|null
	 */
	protected $unsafe_expression = null;

	/**
	 * Rule name.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	protected $rule_name = __CLASS__;

	/**
	 * Mark the variable at $stackPtr as being safely sanitized for use in a SQL context.
	 * $stackPtr must point to a T_VARIABLE. Handles arrays and (maybe) object properties.
	 *
	 * @since 1.7.0
	 *
	 * @param int      $stackPtr      The position of the variable token.
	 * @param int|null $assignmentPtr The position of the assignment token.
	 * @return bool
	 */
	protected function mark_sanitized_var( $stackPtr, $assignmentPtr = null ) {
		if ( \T_VARIABLE !== $this->tokens[ $stackPtr ]['code'] ) {
			return false;
		}

		// Find the closure or function scope of the variable.
		$context = $this->get_context( $stackPtr );

		$var = $this->get_variable_as_string( $stackPtr );

		$this->sanitized_variables[ $context ][ $var ] = true;

		// Sanitizing only overrides a previously unsafe assignment if it's at a lower level (ie not within a conditional).
		if ( isset( $this->unsanitized_variables[ $context ][ $var ] ) ) {
			if ( 1 === $this->tokens[ $stackPtr ]['level'] ||
				$this->tokens[ $stackPtr ]['level'] < $this->unsanitized_variables[ $context ][ $var ] ) {
					unset( $this->unsanitized_variables[ $context ][ $var ] );
			}
		}

		if ( $assignmentPtr ) {
			$end = $this->phpcsFile->findEndOfStatement( $assignmentPtr );
			$this->assignments[ $context ][ $var ][ $assignmentPtr ] = $this->phpcsFile->getTokensAsString( $stackPtr, $end - $stackPtr );
		}
	}

	/**
	 * Mark the variable at $stackPtr as being unsafe. Opposite of mark_sanitized_var().
	 * Use this to reset a variable that might previously have been marked as sanitized.
	 *
	 * @since 1.7.0
	 *
	 * @param int      $stackPtr      The position of the variable token.
	 * @param int|null $assignmentPtr The position of the assignment token.
	 * @return bool
	 */
	protected function mark_unsanitized_var( $stackPtr, $assignmentPtr = null ) {
		if ( \T_VARIABLE !== $this->tokens[ $stackPtr ]['code'] ) {
			return false;
		}

		// Find the closure or function scope of the variable.
		$context = $this->get_context( $stackPtr );

		$var = $this->get_variable_as_string( $stackPtr );
		// `$foo[] = $unsafe_val` means we have to assume the whole array is unsafe.
		$var = preg_replace( '/\[\]$/', '', $var );

		unset( $this->sanitized_variables[ $context ][ $var ] );

		$this->unsanitized_variables[ $context ][ $var ] = $this->tokens[ $stackPtr ]['level'];

		if ( $assignmentPtr ) {
			$end = $this->phpcsFile->findEndOfStatement( $assignmentPtr );

			$this->assignments[ $context ][ $var ][ $assignmentPtr ] = $this->phpcsFile->getTokensAsString( $stackPtr, $end - $stackPtr );
		}
	}

	/**
	 * Check if the variable at $stackPtr has been sanitized for SQL in the current scope.
	 * $stackPtr must point to a T_VARIABLE. Handles arrays and (maybe) object properties.
	 *
	 * @since 1.7.0
	 *
	 * @param int $stackPtr The position of the variable token.
	 * @return bool
	 */
	protected function is_sanitized_var( $stackPtr ) {
		if ( \T_VARIABLE !== $this->tokens[ $stackPtr ]['code'] ) {
			return false;
		}

		// Find the closure or function scope of the variable.
		$context = $this->get_context( $stackPtr );

		$var = $this->get_variable_as_string( $stackPtr );

		return $this->is_sanitized_var_internal( $var, $context );
	}

	/**
	 * Check if the variable named in $variable_name has been safely sanitized in the given context.
	 *
	 * @since 1.7.0
	 *
	 * @param string $variable_name The variable name.
	 * @param int    $context The context position.
	 * @return bool
	 */
	protected function is_sanitized_var_internal( $variable_name, $context ) {
		// If it's $wpdb->tablename then it's implicitly safe.
		if ( '$wpdb->' === substr( $variable_name, 0, 7 ) || '$this->table' === substr( $variable_name, 0, 12 ) || '$this->the_table' === substr( $variable_name, 0, 16 ) || '$wpdb' === $variable_name ) {
			return true;
		}

		// BuddyPress.
		if ( preg_match( '/^[$]bp->\w+->table_name(?:\w+)?$/', $variable_name ) ) {
			return true;
		}

		// If it's ever been set to something unsanitized in this context then we have to consider it unsafe.
		// See insecure_wpdb_query_17.
		if ( isset( $this->unsanitized_variables[ $context ][ $variable_name ] ) ) {
			return false;
		}

		if ( isset( $this->sanitized_variables[ $context ][ $variable_name ] ) ) {
			return true;
		}

		// Is it an array or an object? If so, was the whole array sanitized?
		if ( preg_match( '/^([$]\w+)\W/', $variable_name, $matches ) ) {
			$var_array = $matches[1];
			if ( isset( $this->unsanitized_variables[ $context ][ $var_array ] ) ) {
				return false;
			}
			if ( isset( $this->sanitized_variables[ $context ][ $var_array ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Unwind the stack to provide an explanation as to why a given variable is considered unsafe.
	 * It might have been previously assigned to an unescaped value; this is to help tell the user exactly where it went wrong.
	 *
	 * @since 1.7.0
	 *
	 * @param int $stackPtr The position of the token.
	 * @param int $limit    The limit for unwinding.
	 * @return array
	 */
	protected function unwind_unsafe_assignments( $stackPtr, $limit = 6 ) {
		$_unsafe_ptr               = $this->unsafe_ptr;
		$_unsafe_expression        = $this->unsafe_expression;
		$this->unsafe_ptr          = null;
		$severity                  = 0;
		$this->expression_severity = $severity;

		if ( --$limit < 0 ) {
			return array();
		}

		$vars_to_explain = array();
		$var             = $this->get_variable_as_string( $stackPtr );
		if ( $var ) {
			$vars_to_explain[ $var ] = true;
		} else {
			$vars = $this->get_interpolated_variables( $stackPtr );
			if ( $vars ) {
				foreach ( $vars as $var ) {
					$vars_to_explain[ $var ] = true;
				}
			}
		}

		$extra_context = array();
		$from          = $stackPtr;

		while ( $vars_to_explain && --$limit >= 0 ) {
			foreach ( $vars_to_explain as $var => $dummy ) {
				$assignments = $this->find_assignments( $from, $var );

				if ( $assignments ) {
					foreach ( array_reverse( $assignments, true ) as $assignmentPtr => $code ) {
						// Ignore assignments that happen later in the execution flow.
						if ( $assignmentPtr >= $stackPtr ) {
							continue;
						}

						$unsafe_ptr = $this->check_expression( $assignmentPtr );
						if ( $unsafe_ptr ) {
							$how             = 'unsafely';
							$extra_context[] = sprintf( "%s assigned %s at line %d:\n %s", addcslashes( $var, "\0\\" ), $how, $this->tokens[ $assignmentPtr ]['line'], addcslashes( $code, "\0\\" ) );
							foreach ( $this->find_functions_in_expression( $assignmentPtr ) as $func ) {
								if ( in_array( $func, $this->notEscapingFunctions, true ) ) {
									$extra_context[] = sprintf( 'Note: %s() is not a safe escaping function.', $func );
									break;
								}
							}
							unset( $vars_to_explain[ $var ] );

							$more_vars = $this->find_variables_in_expression( $unsafe_ptr );
							if ( $more_vars ) {
								foreach ( $more_vars as $var_name ) {
									if ( $var_name ) {
										if ( ! $this->is_sanitized_var_internal( $var_name, $this->get_context( $assignmentPtr ) ) ) {
											$vars_to_explain[ $var_name ] = true;
											if ( preg_match( '/^([$]\w+)\W/', $var_name, $matches ) ) {
												if ( in_array( $matches[1], $this->unsafe_variables, true ) ) {
													$severity = 10;
												}
											}
										}
									}
								}
							}
						}

						if ( ! isset( $vars_to_explain[ $var ] ) ) {
							break; // Out of the assignments loop.
						}
					}
				} elseif ( ! $this->is_sanitized_var_internal( $var, $from ) && ! $this->is_warning_parameter( $var ) ) {
						$extra_context[] = sprintf( '%s used without escaping.', $var );
				}
			}
		}

		$this->unsafe_expression   = $_unsafe_expression;
		$this->unsafe_ptr          = $_unsafe_ptr;
		$this->expression_severity = $severity;

		return array_unique( $extra_context );
	}

	/**
	 * Return a string representing the unsafe portion of code pointed to by $stackPtr, as returned by check_expression().
	 * This is necessary because phpcs hobbles the parsing of variables in strings.
	 *
	 * @since 1.7.0
	 *
	 * @param int $stackPtr The position of the token.
	 * @return string
	 */
	protected function get_unsafe_expression_as_string( $stackPtr ) {
		if ( in_array( $this->tokens[ $stackPtr ]['code'], array( \T_DOUBLE_QUOTED_STRING, \T_HEREDOC ), true ) ) {
			// It must be a variable within the string that's the unsafe thing.
			foreach ( $this->get_interpolated_variables( $stackPtr ) as $var ) {
				// Does it look like a table name, "SELECT * FROM {$my_table}" or similar?.
				$var_placeholder   = md5( $var );
				$placeholder_query = str_replace( $var, $var_placeholder, $this->tokens[ $stackPtr ]['content'] );
				if ( $this->get_table_from_query( trim( $placeholder_query, '"' ) ) === $var_placeholder ) {
					// Add the table variable name to the list of parameters that will only trigger a warning..
					$this->warn_only_parameters[] = $var;
				}

				// Where are we?
				$context = $this->get_context( $stackPtr );

				// If we've found an unsanitized var then fail early.
				if ( ! $this->is_sanitized_var_internal( $var, $context ) ) {
					return $var;
				}
			}
		} else {
			$where = $this->check_expression( $stackPtr );
			if ( $where ) {
				// If the problem is at a variable, and that variable is not sanitized, then return just the variable name.
				if ( \T_VARIABLE === $this->tokens[ $stackPtr ]['code'] && ! $this->is_sanitized_var( $stackPtr ) ) {
					return $this->get_variable_as_string( $stackPtr );
				}
				return $this->get_expression_as_string( $where );
			}
		}

		return $this->get_expression_as_string( $stackPtr );
	}

	/**
	 * Decide if an expression (that is used as a parameter to $wpdb->query() or similar) is safely escaped.
	 * We'll consider it safe IFF the first variable in the expression has previously been escaped OR the
	 * first function call in the expression is an escaping function.
	 *
	 * @since 1.7.0
	 *
	 * @param int      $stackPtr The position of the token.
	 * @param int|null $endPtr   The end position.
	 * @return bool
	 */
	public function expression_is_safe( $stackPtr, $endPtr = null ) {
		$this->unsafe_expression = null;
		$this->unsafe_ptr        = null;

		$this->unsafe_ptr = $this->check_expression( $stackPtr, $endPtr );

		if ( $this->unsafe_ptr ) {
			$this->unsafe_expression = $this->get_unsafe_expression_as_string( $this->unsafe_ptr );
		}

		return ! $this->unsafe_ptr;
	}

	/**
	 * Find the first unsafe thing in the given expression, if any.
	 * It's considered safe IFF everything is either escaped or constant.
	 *
	 * @since 1.7.0
	 *
	 * @param int      $stackPtr The position of the token.
	 * @param int|null $endPtr   The end position.
	 * @return int|false
	 */
	public function check_expression( $stackPtr, $endPtr = null ) {
		$newPtr         = $stackPtr;
		$tokens_to_find = array(
			\T_VARIABLE  => \T_VARIABLE,
			\T_INT_CAST  => \T_INT_CAST,
			\T_BOOL_CAST => \T_BOOL_CAST,
			\T_CLOSE_TAG => \T_CLOSE_TAG,
		)
			+ Tokens::$functionNameTokens
			+ Tokens::$textStringTokens;

		while ( $newPtr && $this->phpcsFile->findNext( $tokens_to_find, $newPtr, $endPtr, false, null, true ) ) {
			$ternaryPtr = $this->is_ternary_condition( $newPtr );
			if ( $ternaryPtr ) {
				// We're in the first part of a ternary condition. It doesn't matter if the condition is safe or not.
				$newPtr = $this->next_non_empty( $ternaryPtr + 1 );
				continue;
			} elseif ( in_array( $this->tokens[ $newPtr ]['code'], Tokens::$functionNameTokens, true ) ) {
				if ( isset( $this->escapingFunctions[ $this->tokens[ $newPtr ]['content'] ] ) ) {
					// Function call to an escaping function.
					// Skip over the function's parameters and continue checking the remainder of the expression.
					$function_params = PassedParameters::getParameters( $this->phpcsFile, $newPtr );
					$param           = end( $function_params );
					if ( $param ) {
						$newPtr = $this->next_non_empty( $param['end'] + 1 );
						continue;
					}
				} elseif ( isset( $this->implicitSafeFunctions[ $this->tokens[ $newPtr ]['content'] ] ) ) {
					// Function call that always returns implicitly safe output.
					// Skip over the function's parameters and continue checking the remainder of the expression.
					$function_params = PassedParameters::getParameters( $this->phpcsFile, $newPtr );
					$param           = end( $function_params );
					if ( $param ) {
						$newPtr = $this->next_non_empty( $param['end'] + 1 );
						continue;
					}
				} elseif ( isset( $this->neutralFunctions[ $this->tokens[ $newPtr ]['content'] ] ) ) {
					// It's a function like implode(), which is safe if all of the parameters are also safe.
					$function_params = PassedParameters::getParameters( $this->phpcsFile, $newPtr );
					$param           = null;
					foreach ( $function_params as $param ) {
						$innerPtr = $this->check_expression( $param['start'], $param['end'] + 1 );
						if ( $innerPtr ) {
							return $innerPtr;
						}
					}
					// If we got this far, everything in the call is safe, so skip to the next statement.
					if ( $param ) {
						$newPtr = $this->next_non_empty( $param['end'] + 1 );
						continue;
					}
				} elseif ( 'array_map' === $this->tokens[ $newPtr ]['content'] ) {
					// Special handling for array_map() calls that map an escaping function.
					// See also similar array_walk handler in process_token().
					$function_params = PassedParameters::getParameters( $this->phpcsFile, $newPtr );
					$mapped_function = trim( $function_params[1]['clean'], '"\'' );
					// If this is array_map( 'esc_sql', ... ) or similar, then we can move on to the next statement.
					if ( isset( $this->escapingFunctions[ $mapped_function ] ) ) {
						$param  = end( $function_params );
						$newPtr = $this->next_non_empty( $param['end'] + 1 );
						continue;
					}
				} elseif ( 'prepare' === $this->tokens[ $newPtr ]['content'] ) {
					// It's wpdb->prepare(). The first parameter needs to be checked, the remainder are escaped.
					$function_params = PassedParameters::getParameters( $this->phpcsFile, $newPtr );
					$first_param     = reset( $function_params );
					$inner           = $this->check_expression( $first_param['start'], $first_param['end'] + 1 );
					if ( $inner ) {
						return $inner;
					}
					// It's safe, so skip past the prepare().
					$param  = end( $function_params );
					$newPtr = $this->next_non_empty( $param['end'] + 1 );
					continue;
				} elseif ( isset( $this->safe_constants[ $this->tokens[ $newPtr ]['content'] ] ) ) {
					// It's a constant like ARRAY_A, it's safe.
					$newPtr = $this->next_non_empty( $newPtr + 1 );
					continue;
				} elseif ( $this->is_defined_constant( $newPtr ) ) {
					// It looks like some other constant, assume it's safe and skip over it.
					$newPtr = $this->next_non_empty( $newPtr + 1 );
					continue;
				} else {
					// First function call was something else. It should be wrapped in an escape.
					return $newPtr;
				}
			} elseif ( in_array( $this->tokens[ $newPtr ]['code'], array( \T_DOUBLE_QUOTED_STRING, \T_HEREDOC ), true ) ) {
				// It's a string that might have variables.
				foreach ( $this->get_interpolated_variables( $newPtr ) as $var ) {
					// Does it look like a table name, "SELECT * FROM {$my_table}" or similar?.
					$var_placeholder   = md5( $var );
					$placeholder_query = preg_replace( '/[${]*' . preg_quote( ltrim( $var, '$' ), '/' ) . '[}]*/', $var_placeholder, $this->tokens[ $newPtr ]['content'] );
					if ( $this->get_table_from_query( trim( $placeholder_query, '"' ) ) === $var_placeholder ) {
						// Add the table variable name to the list of parameters that will only trigger a warning.
						$this->warn_only_parameters[] = $var;
					}

					// Where are we?
					$context = $this->get_context( $newPtr );

					// If we've found an unsanitized var then fail early.
					if ( ! $this->is_sanitized_var_internal( $var, $context ) ) {
						return $newPtr;
					}
				}
			} elseif ( \T_VARIABLE === $this->tokens[ $newPtr ]['code'] ) {
				// Allow for things like $this->wpdb->prepare().
				if ( '$this' === $this->tokens[ $newPtr ]['content'] || 'this' === $this->tokens[ $newPtr ]['content'] ) {
					if ( 'wpdb' === $this->tokens[ $newPtr + 2 ]['content'] && '->' === $this->tokens[ $newPtr + 3 ]['content'] ) {
						// Continue the loop from the wpdb->prepare() part.
						$newPtr += 4;
						continue;
					}
				}

				// If the expression contains an unsanitized variable and we haven't already found an escaping function, then we can fail at this point.
				if ( ! $this->is_sanitized_var( $newPtr ) ) {
					return $newPtr;
				}
				// Continue from the end of the variable.
				$lookahead = $this->find_end_of_variable( $newPtr );
				if ( $lookahead ) {
					if ( $lookahead > $newPtr ) {
						$newPtr = $lookahead + 1;
						continue;
					}
				}
			} elseif ( in_array( $this->tokens[ $newPtr ]['code'], Tokens::$castTokens, true ) ) {
				// We're safely casting to an int or bool.
				$newPtr = $this->next_non_empty( $this->phpcsFile->findEndOfStatement( $newPtr ) );
				continue;
			} elseif ( \T_CLOSE_TAG === $this->tokens[ $newPtr ]['code'] ) {
				// We hit an end-of-php code without a semicolon before it, like `foo() ? >`.
				return false;
			} elseif ( \T_CONSTANT_ENCAPSED_STRING === $this->tokens[ $newPtr ]['code'] ) {
				// A constant string is ok, but we want to check what's after it.
				// Continue to next token.
				// No action needed for constant strings.
				$newPtr = $this->next_non_empty( $newPtr + 1 );
			}

			if ( ! is_null( $endPtr ) && $newPtr > $endPtr ) {
				// We've run past the end, so exit.
				return false;
			}

			++$newPtr;
		}

		// If we found nothing unsafe, it was probably just a scalar value.
		return false;
	}

	/**
	 * Is $stackPtr a function call or other statement that requires escaped data?
	 * Override this in child classes as needed.
	 *
	 * @since 1.7.0
	 *
	 * @param int $stackPtr The position of the token.
	 * @return int Returns a pointer to the method call that requires escaping, if relevant.
	 */
	public function needs_escaping( $stackPtr ) {

		if ( $this->is_wpdb_method_call( $stackPtr, $this->unsafe_methods ) ) {
			return $this->methodPtr;
		}

		if ( in_array( $this->tokens[ $stackPtr ]['code'], array( \T_ECHO, \T_PRINT, \T_EXIT ), true ) ) {
			return $stackPtr;
		}

		return false;
	}

	/**
	 * Is a variable name one that should only produce a warning when it is used unescaped?
	 *
	 * @since 1.7.0
	 *
	 * @param string $parameter_name The parameter name.
	 * @return bool
	 */
	public function is_warning_parameter( $parameter_name ) {
		foreach ( $this->warn_only_parameters as $warn_param ) {
			if ( preg_match( '/^' . preg_quote( $warn_param, '/' ) . '(?:\b|$)/', $parameter_name ) ) {
				return true;
			}
		}

		// If $error_always_parameters is set, then all other variable names will produce warnings only.
		if ( ! empty( $this->error_always_parameters ) ) {
			foreach ( $this->error_always_parameters as $error_param ) {
				// Note the unanchored regex here. That's on purpose as a bit of a hack, so that strings like this are considered warnings:
				// `foo( $safe_var )`.
				if ( preg_match( '/' . preg_quote( $error_param, '/' ) . '(?:\b|$)/m', $parameter_name ) ) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Is an expression one that should only produce a warning when it is used unescaped?
	 *
	 * @since 1.7.0
	 *
	 * @param string $expression_string The expression string.
	 * @return bool
	 */
	public function is_warning_expression( $expression_string ) {
		// Override this in child class if needed.
		return false;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.7.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		static $line_no = null;
		if ( $this->tokens[ $stackPtr ]['line'] !== $line_no ) {
			$line_no = $this->tokens[ $stackPtr ]['line'];
		}

		if ( $this->is_assignment( $stackPtr ) ) {

			// Work out what we're assigning to the variable at $stackPtr.
			$nextToken = $this->phpcsFile->findNext( Tokens::$assignmentTokens, $stackPtr + 1, null, false, null, true );

			// If the expression being assigned is safe (ie escaped) then mark the variable as sanitized.
			if ( $this->expression_is_safe( $nextToken + 1 ) ) {
				// Don't mark as safe if it's a concat, since that doesn't sanitize the initial part.
				if ( \T_CONCAT_EQUAL !== $this->tokens[ $nextToken ]['code'] ) {
					$this->mark_sanitized_var( $stackPtr, $nextToken + 1 );
				}
			} else {
				$this->mark_unsanitized_var( $stackPtr, $nextToken + 1 );
			}

			return;
		}

		// Handle foreach ( $foo as $bar ), which is similar to assignment.
		$nextToken = $this->next_non_empty( $stackPtr + 1 );
		if ( \T_AS === $this->tokens[ $nextToken ]['code'] ) {
			$as_var    = $this->next_non_empty( $nextToken + 1 );
			$lookahead = $this->next_non_empty( $as_var + 1 );
			if ( \T_DOUBLE_ARROW === $this->tokens[ $lookahead ]['code'] ) {
				// It's foreach ( $foo as $i => $as_var ).
				$as_var = $this->next_non_empty( $lookahead + 1 );
			}
			if ( \T_VARIABLE === $this->tokens[ $as_var ]['code'] ) {
				// $as_var is effectively being assigned to. So if the LHS expression is safe, $as_var is also safe.
				if ( $this->expression_is_safe( $stackPtr, $nextToken ) ) {
					$this->mark_sanitized_var( $as_var );
				} else {
					$this->mark_unsanitized_var( $as_var );
				}
			}
		}

		// Special case for array_walk. Handled here rather than in expression_is_safe() because it's a statement not an expression.
		if ( in_array( $this->tokens[ $stackPtr ]['code'], Tokens::$functionNameTokens, true )
			&& 'array_walk' === $this->tokens[ $stackPtr ]['content'] ) {
			$function_params = PassedParameters::getParameters( $this->phpcsFile, $stackPtr );
			$mapped_function = trim( $function_params[2]['clean'], '"\'' );
			// If it's an escaping function, then mark the referenced variable in the first parameter as sanitized.
			if ( isset( $this->escapingFunctions[ $mapped_function ] ) ) {
				$escaped_var = $this->next_non_empty( $function_params[1]['start'] );
				$this->mark_sanitized_var( $escaped_var );
			}
		}

		// If we're in a call to an unsafe db method like $wpdb->query then check all the parameters for safety.
		$checkPtr = $this->needs_escaping( $stackPtr );
		if ( $checkPtr ) {
			// Function call.
			if ( \T_STRING === $this->tokens[ $checkPtr ]['code'] ) {
				// Only the first parameter needs escaping.
				$parameters  = PassedParameters::getParameters( $this->phpcsFile, $checkPtr );
				$method      = $this->tokens[ $checkPtr ]['content'];
				$methodParam = reset( $parameters );
				// If the expression wasn't escaped safely, then alert.
				if ( $methodParam ) {
					$unsafe_ptr = $this->check_expression( $methodParam['start'], $methodParam['end'] + 1 );
					if ( $unsafe_ptr ) {
						$extra_context     = $this->unwind_unsafe_assignments( $unsafe_ptr );
						$unsafe_expression = $this->get_unsafe_expression_as_string( $unsafe_ptr );

						if ( $this->is_warning_parameter( $unsafe_expression )
							|| $this->is_suppressed_line( $checkPtr, array( 'WordPress.DB.PreparedSQL.NotPrepared', 'WordPress.DB.PreparedSQL.InterpolatedNotPrepared', 'WordPress.DB.DirectDatabaseQuery.DirectQuery', 'DB call', 'unprepared SQL', 'PreparedSQLPlaceholders replacement count' ) )
							|| $this->is_warning_expression( $methodParam['clean'] )
							) {
							$this->phpcsFile->addWarning(
								'Unescaped parameter %s used in $wpdb->%s(%s)%s',
								$checkPtr,
								$this->rule_name,
								array( $unsafe_expression, $method, $methodParam['clean'], rtrim( "\n" . join( "\n", $extra_context ) ) ),
								$this->expression_severity,
								false
							);
						} else {
							$this->phpcsFile->addError(
								'Unescaped parameter %s used in $wpdb->%s(%s)%s',
								$checkPtr,
								$this->rule_name,
								array( $unsafe_expression, $method, $methodParam['clean'], rtrim( "\n" . join( "\n", $extra_context ) ) ),
								$this->expression_severity,
								false
							);
						}
					}
					return; // Only need to error on the first occurrence..
				}
			} else {
				// echo etc; check everything to end of statement.
				$unsafe_ptr = $this->check_expression( $checkPtr + 1 );
				if ( $unsafe_ptr ) {
					$extra_context     = $this->unwind_unsafe_assignments( $unsafe_ptr );
					$unsafe_expression = $this->get_unsafe_expression_as_string( $unsafe_ptr );

					if ( $this->is_warning_parameter( $unsafe_expression ) || $this->is_suppressed_line( $checkPtr, array( 'WordPress.DB.PreparedSQL.NotPrepared', 'WordPress.DB.PreparedSQL.InterpolatedNotPrepared', 'WordPress.DB.DirectDatabaseQuery.DirectQuery', 'DB call', 'unprepared SQL', 'PreparedSQLPlaceholders replacement count' ) ) ) {
						$this->phpcsFile->addWarning(
							'Unescaped parameter %s used in %s%s',
							$checkPtr,
							$this->rule_name,
							array( $unsafe_expression, $this->tokens[ $checkPtr ]['content'], rtrim( "\n" . join( "\n", $extra_context ) ) ),
							$this->expression_severity,
							false
						);
					} else {
						$this->phpcsFile->addError(
							'Unescaped parameter %s used in %s%s',
							$checkPtr,
							$this->rule_name,
							array( $unsafe_expression, $this->tokens[ $checkPtr ]['content'], rtrim( "\n" . join( "\n", $extra_context ) ) ),
							$this->expression_severity,
							false
						);
					}
					return; // Only need to error on the first occurrence.
				}
			}
		}
	}
}
