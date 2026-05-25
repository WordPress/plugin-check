<?php
/**
 * RuntimeFatalErrorPreventionSniff
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use WordPressCS\WordPress\Sniff;

/**
 * Detects high-risk patterns that commonly cause runtime fatal errors in plugins.
 *
 * @since 2.1.0
 */
final class RuntimeFatalErrorPreventionSniff extends Sniff {

	/**
	 * List of popular optional third-party integration functions.
	 *
	 * @var array<string>
	 */
	private $integration_functions = array(
		// WooCommerce.
		'is_woocommerce',
		'wc_get_product',
		'wc_price',
		'wc_get_cart_url',
		'wc_get_checkout_url',
		'wc_get_page_id',
		'wc_get_template',
		'wc_add_notice',
		'wc_print_notices',
		'wc_get_cart',
		'wc_get_container',
		'is_cart',
		'is_checkout',
		// ACF.
		'get_field',
		'the_field',
		'have_rows',
		'the_row',
		'get_sub_field',
		'update_field',
		'delete_field',
		'acf_register_block_type',
		'get_fields',
		// Elementor.
		'elementor_pro_load_plugin',
		'elementor_load_plugin_textdomain',
		// WPML.
		'wpml_object_id',
		'wpml_get_active_languages',
		// Polylang.
		'pll_the_languages',
		'pll_current_language',
		'pll_default_language',
		'pll_get_post',
		'pll_get_term',
		'pll_translate_string',
		// Contact Form 7.
		'wpcf7_contact_form',
		'wpcf7_add_form_tag',
		// Yoast.
		'yoast_breadcrumb',
		// Carbon Fields.
		'carbon_get_post_meta',
		'carbon_get_theme_option',
		'carbon_get_user_meta',
	);

	/**
	 * List of popular optional third-party classes.
	 *
	 * @var array<string>
	 */
	private $optional_classes = array(
		// WooCommerce.
		'wc_product',
		'wc_order',
		'wc_cart',
		'wc_customer',
		'wc_coupon',
		'wc_gateway_paypal',
		'wc_payment_gateway',
		// Elementor.
		'elementor\widget_base',
		'elementor\controls_manager',
		'elementor\plugin',
		// ACF.
		'acf_field',
		// Carbon Fields.
		'carbon_fields\container',
		'carbon_fields\field',
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 2.1.0
	 *
	 * @return array<int>
	 */
	public function register() {
		return array(
			\T_REQUIRE,
			\T_REQUIRE_ONCE,
			\T_INCLUDE,
			\T_INCLUDE_ONCE,
			\T_NEW,
			\T_VARIABLE,
			\T_STRING,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 2.1.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		$token_code = $this->tokens[ $stackPtr ]['code'];

		// Case 1: require / require_once / include / include_once on dynamic or unguarded paths.
		if ( in_array( $token_code, array( \T_REQUIRE, \T_REQUIRE_ONCE, \T_INCLUDE, \T_INCLUDE_ONCE ), true ) ) {
			$this->process_import( $stackPtr );
			return;
		}

		// Case 3: Instantiating optional classes without class_exists guard.
		if ( \T_NEW === $token_code ) {
			$this->process_new( $stackPtr );
			return;
		}

		// Case 4: Invoking dynamic callbacks directly without is_callable.
		if ( \T_VARIABLE === $token_code ) {
			$this->process_variable( $stackPtr );
			return;
		}

		// Case 2 & 5: Function calls and Hook callback verification.
		if ( \T_STRING === $token_code ) {
			$this->process_string( $stackPtr );
			return;
		}
	}

	/**
	 * Process require/include statements.
	 *
	 * @param int $stackPtr Token pointer.
	 */
	private function process_import( $stackPtr ) {
		$end = $this->phpcsFile->findNext( \T_SEMICOLON, $stackPtr + 1 );
		if ( false === $end ) {
			return;
		}

		// Find any variables inside the require/include expression.
		$has_dynamic = false;
		$dynamic_var = '';
		for ( $i = $stackPtr + 1; $i < $end; $i++ ) {
			if ( \T_VARIABLE === $this->tokens[ $i ]['code'] ) {
				$var_name = $this->tokens[ $i ]['content'];
				// Ignore superglobals or clearly static environment variables if any, but standard variables are dynamic.
				if ( ! in_array( $var_name, array( '$GLOBALS', '$_SERVER', '$_GET', '$_POST', '$_FILES', '$_COOKIE', '$_SESSION', '$_REQUEST', '$_ENV' ), true ) ) {
					$has_dynamic = true;
					$dynamic_var = $var_name;
					break;
				}
			}
		}

		if ( $has_dynamic ) {
			// Check if guarded by file_exists or is_readable.
			if ( ! $this->is_guarded_by_condition( $stackPtr, array( 'file_exists', 'is_readable' ), $dynamic_var ) ) {
				$this->phpcsFile->addWarning(
					'Dynamic include/require path detected without file_exists() or is_readable() guard. Found: %s',
					$stackPtr,
					'DynamicImportUnguarded',
					array( trim( $this->phpcsFile->getTokensAsString( $stackPtr, $end - $stackPtr ) ) )
				);
			}
		}
	}

	/**
	 * Process T_NEW class instantiation.
	 *
	 * @param int $stackPtr Token pointer.
	 */
	private function process_new( $stackPtr ) {
		$next = $this->phpcsFile->findNext( \PHP_CodeSniffer\Util\Tokens::$emptyTokens, $stackPtr + 1, null, true );
		if ( false === $next ) {
			return;
		}

		$class_name = '';
		// Build fully namespaced class name.
		while ( false !== $next && in_array( $this->tokens[ $next ]['code'], array( \T_STRING, \T_NS_SEPARATOR, \T_NAME_QUALIFIED, \T_NAME_FULLY_QUALIFIED ), true ) ) {
			$class_name .= $this->tokens[ $next ]['content'];
			$next = $this->phpcsFile->findNext( \PHP_CodeSniffer\Util\Tokens::$emptyTokens, $next + 1, null, true );
		}

		if ( '' !== $class_name && in_array( strtolower( $class_name ), $this->optional_classes, true ) ) {
			if ( ! $this->is_guarded_by_condition( $stackPtr, array( 'class_exists' ), $class_name ) ) {
				$this->phpcsFile->addError(
					'Instantiating optional class "%s" without a class_exists() guard is risky and could cause a runtime fatal error.',
					$stackPtr,
					'OptionalClassInstantiationUnguarded',
					array( $class_name )
				);
			}
		}
	}

	/**
	 * Process dynamic variable function calls.
	 *
	 * @param int $stackPtr Token pointer.
	 */
	private function process_variable( $stackPtr ) {
		$next = $this->phpcsFile->findNext( \PHP_CodeSniffer\Util\Tokens::$emptyTokens, $stackPtr + 1, null, true );
		if ( false !== $next && \T_OPEN_PARENTHESIS === $this->tokens[ $next ]['code'] ) {
			$var_name = $this->tokens[ $stackPtr ]['content'];
			if ( ! $this->is_guarded_by_condition( $stackPtr, array( 'is_callable', 'function_exists' ), $var_name ) ) {
				$this->phpcsFile->addError(
					'Invoking dynamic callback "%s()" without an is_callable() or function_exists() guard is risky and could cause a runtime fatal error.',
					$stackPtr,
					'DynamicCallbackInvocationUnguarded',
					array( $var_name )
				);
			}
		}
	}

	/**
	 * Helper to get all top-level parameters of a function call.
	 *
	 * @param int $opener Parenthesis opener.
	 * @param int $closer Parenthesis closer.
	 * @return array<array<int>> List of parameter token pointers.
	 */
	private function get_function_parameters( $opener, $closer ) {
		$params = array();
		$current_param = array();
		$depth = 0;

		for ( $i = $opener + 1; $i < $closer; $i++ ) {
			$token = $this->tokens[ $i ];

			if ( \T_OPEN_PARENTHESIS === $token['code'] || \T_OPEN_SQUARE_BRACKET === $token['code'] || \T_OPEN_SHORT_ARRAY === $token['code'] ) {
				$depth++;
			} elseif ( \T_CLOSE_PARENTHESIS === $token['code'] || \T_CLOSE_SQUARE_BRACKET === $token['code'] || \T_CLOSE_SHORT_ARRAY === $token['code'] ) {
				$depth--;
			}

			if ( 0 === $depth && \T_COMMA === $token['code'] ) {
				if ( ! empty( $current_param ) ) {
					$params[] = $current_param;
					$current_param = array();
				}
			} else {
				if ( ! in_array( $token['code'], \PHP_CodeSniffer\Util\Tokens::$emptyTokens, true ) ) {
					$current_param[] = $i;
				}
			}
		}

		if ( ! empty( $current_param ) ) {
			$params[] = $current_param;
		}

		return $params;
	}

	/**
	 * Process function calls and hooks.
	 *
	 * @param int $stackPtr Token pointer.
	 */
	private function process_string( $stackPtr ) {
		// Verify if this is a function call.
		$prev = $this->phpcsFile->findPrevious( \PHP_CodeSniffer\Util\Tokens::$emptyTokens, $stackPtr - 1, null, true );
		if ( false !== $prev && in_array( $this->tokens[ $prev ]['code'], array( \T_OBJECT_OPERATOR, \T_DOUBLE_COLON, \T_FUNCTION, \T_NEW ), true ) ) {
			return;
		}

		$next = $this->phpcsFile->findNext( \PHP_CodeSniffer\Util\Tokens::$emptyTokens, $stackPtr + 1, null, true );
		if ( false === $next || \T_OPEN_PARENTHESIS !== $this->tokens[ $next ]['code'] ) {
			return;
		}

		$opener = $next;
		$closer = $this->tokens[ $opener ]['parenthesis_closer'];
		$function_name = strtolower( $this->tokens[ $stackPtr ]['content'] );

		// Case 2: Calling plugin/theme integration functions without function_exists guard.
		if ( in_array( $function_name, $this->integration_functions, true ) ) {
			if ( ! $this->is_guarded_by_condition( $stackPtr, array( 'function_exists' ), $this->tokens[ $stackPtr ]['content'] ) ) {
				$this->phpcsFile->addError(
					'Calling optional integration function "%s()" without a function_exists() guard is risky and could cause a runtime fatal error.',
					$stackPtr,
					'OptionalFunctionCallUnguarded',
					array( $this->tokens[ $stackPtr ]['content'] )
				);
			}
			return;
		}

		// Case 4 extra: call_user_func / call_user_func_array with dynamic variables.
		if ( in_array( $function_name, array( 'call_user_func', 'call_user_func_array' ), true ) ) {
			$params = $this->get_function_parameters( $opener, $closer );
			if ( isset( $params[0] ) && ! empty( $params[0] ) ) {
				$first_param_ptr = $params[0][0];
				if ( \T_VARIABLE === $this->tokens[ $first_param_ptr ]['code'] ) {
					$var_name = $this->tokens[ $first_param_ptr ]['content'];
					if ( ! $this->is_guarded_by_condition( $stackPtr, array( 'is_callable', 'function_exists' ), $var_name ) ) {
						$this->phpcsFile->addError(
							'Invoking dynamic callback "%s" via %s() without an is_callable() or function_exists() guard is risky and could cause a runtime fatal error.',
							$stackPtr,
							'DynamicCallbackCallUserFuncUnguarded',
							array( $var_name, $this->tokens[ $stackPtr ]['content'] )
						);
					}
				}
			}
			return;
		}

		// Case 5: Hooking callbacks that may not exist (array/class method) without method_exists / class guard.
		if ( in_array( $function_name, array( 'add_action', 'add_filter' ), true ) ) {
			$params = $this->get_function_parameters( $opener, $closer );
			if ( isset( $params[1] ) && ! empty( $params[1] ) ) {
				$callback_param_tokens = $params[1];
				$first_token_ptr = $callback_param_tokens[0];
				$start_token = $this->tokens[ $first_token_ptr ];

				// Check if the callback parameter is an array definition.
				$is_array = ( \T_ARRAY === $start_token['code'] || \T_OPEN_SHORT_ARRAY === $start_token['code'] );
				if ( $is_array ) {
					$elements = array();
					// Extract significant tokens inside the array.
					foreach ( $callback_param_tokens as $t_ptr ) {
						if ( in_array( $this->tokens[ $t_ptr ]['code'], array( \T_VARIABLE, \T_CONSTANT_ENCAPSED_STRING, \T_STRING ), true ) ) {
							$elements[] = $this->tokens[ $t_ptr ];
						}
					}

					// A valid class/method hook array callback has exactly two elements.
					if ( 2 === count( $elements ) ) {
						$class_element  = $elements[0];
						$method_element = $elements[1];

						// If the method is a string literal.
						if ( \T_CONSTANT_ENCAPSED_STRING === $method_element['code'] ) {
							$method_name = trim( $method_element['content'], '\'"' );
							$class_var   = $class_element['content'];

							// If hooking onto $this.
							if ( '$this' === $class_var ) {
								$class_ptr = $this->phpcsFile->getCondition( $stackPtr, \T_CLASS );
								if ( false !== $class_ptr ) {
									$class_methods = $this->get_class_methods( $class_ptr );
									if ( ! in_array( strtolower( $method_name ), $class_methods, true ) ) {
										// If it's not guarded.
										if ( ! $this->is_guarded_by_condition( $stackPtr, array( 'method_exists', 'is_callable' ), $method_name ) ) {
											// Check if the class extends a parent class.
											$extends = $this->class_extends( $class_ptr );
											if ( $extends ) {
												// Warning if it could be defined in a parent class.
												$this->phpcsFile->addWarning(
													'Hooked callback method "%s" is not defined in this class (but the class extends a parent class). Ensure it exists or is guarded.',
													$stackPtr,
													'HookedCallbackMethodNotFoundWarning',
													array( $method_name )
												);
											} else {
												// Error if the class definitely does not define the method.
												$this->phpcsFile->addError(
													'Hooked callback method "%s" is not defined in this class. Hooking non-existent methods will cause runtime fatal errors or notices.',
													$stackPtr,
													'HookedCallbackMethodNotFound',
													array( $method_name )
												);
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Helper to get all methods defined in the class.
	 *
	 * @param int $class_ptr The class token pointer.
	 * @return array<string> Lowercase method names.
	 */
	private function get_class_methods( $class_ptr ) {
		$methods = array();
		if ( ! isset( $this->tokens[ $class_ptr ]['scope_closer'] ) ) {
			return $methods;
		}

		$closer = $this->tokens[ $class_ptr ]['scope_closer'];
		$next = $class_ptr + 1;

		while ( $next < $closer ) {
			$next = $this->phpcsFile->findNext( \T_FUNCTION, $next, $closer );
			if ( false === $next ) {
				break;
			}
			$name = $this->phpcsFile->getDeclarationName( $next );
			if ( $name ) {
				$methods[] = strtolower( $name );
			}
			$next++;
		}

		return $methods;
	}

	/**
	 * Helper to check if a class extends a parent class.
	 *
	 * @param int $class_ptr Class token pointer.
	 * @return bool True if class extends another class.
	 */
	private function class_extends( $class_ptr ) {
		if ( ! isset( $this->tokens[ $class_ptr ]['scope_opener'] ) ) {
			return false;
		}
		$opener = $this->tokens[ $class_ptr ]['scope_opener'];
		$extends = $this->phpcsFile->findNext( \T_EXTENDS, $class_ptr + 1, $opener );
		return ( false !== $extends );
	}

	/**
	 * Verify if the dynamic action is nested inside a conditional guard.
	 *
	 * @param int           $stackPtr   Token pointer.
	 * @param array<string> $functions  Allowed guard functions (e.g. file_exists, class_exists).
	 * @param string        $identifier Target variable/class/function name.
	 * @return bool True if guarded.
	 */
	private function is_guarded_by_condition( $stackPtr, array $functions, $identifier ) {
		if ( empty( $this->tokens[ $stackPtr ]['conditions'] ) ) {
			return false;
		}

		// Normalize identifier for comparison (e.g. remove backslashes, quotes, or dollar signs).
		$clean_id = trim( str_replace( array( '\\', '$', '\'', '"' ), '', $identifier ) );
		if ( '' === $clean_id ) {
			return false;
		}

		foreach ( array_keys( $this->tokens[ $stackPtr ]['conditions'] ) as $condPtr ) {
			if ( in_array( $this->tokens[ $condPtr ]['code'], array( \T_IF, \T_ELSEIF ), true ) ) {
				if ( ! isset( $this->tokens[ $condPtr ]['parenthesis_opener'] ) || ! isset( $this->tokens[ $condPtr ]['parenthesis_closer'] ) ) {
					continue;
				}
				$opener = $this->tokens[ $condPtr ]['parenthesis_opener'];
				$closer = $this->tokens[ $condPtr ]['parenthesis_closer'];

				$condText = $this->phpcsFile->getTokensAsString( $opener, $closer - $opener + 1 );

				foreach ( $functions as $func ) {
					if ( false !== stripos( $condText, $func ) ) {
						// Ensure the condition matches our identifier as well.
						if ( false !== stripos( str_replace( array( '\\', '$', '\'', '"' ), '', $condText ), $clean_id ) ) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}
}
