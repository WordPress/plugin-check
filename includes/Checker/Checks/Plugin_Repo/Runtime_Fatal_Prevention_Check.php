<?php
/**
 * Class Runtime_Fatal_Prevention_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Detects high-risk runtime patterns that can lead to fatal errors.
 *
 * @since 2.2.0
 */
class Runtime_Fatal_Prevention_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Function prefixes commonly used by optional plugin/theme integrations.
	 *
	 * @since 2.2.0
	 * @var array
	 */
	private const INTEGRATION_FUNCTION_PREFIXES = array(
		'wc_',
		'woocommerce_',
		'elementor_',
		'edd_',
		'acf_',
		'wpml_',
		'yoast_',
		'tribe_',
		'learndash_',
		'buddypress_',
		'bbp_',
	);

	/**
	 * Class prefixes commonly used by optional plugin/theme integrations.
	 *
	 * @since 2.2.0
	 * @var array
	 */
	private const INTEGRATION_CLASS_PREFIXES = array(
		'wc_',
		'woocommerce',
		'automattic\\woocommerce\\',
		'elementor\\',
		'edd\\',
		'acf',
		'wpseo\\',
		'tribe\\',
		'learndash',
		'buddypress\\',
		'bbpress\\',
	);

	/**
	 * Functions where one argument is a callback.
	 *
	 * @since 2.2.0
	 * @var array<string, array<int>>
	 */
	private const CALLBACK_FUNCTION_ARGS = array(
		'call_user_func'             => array( 0 ),
		'call_user_func_array'       => array( 0 ),
		'array_map'                  => array( 0 ),
		'array_filter'               => array( 1 ),
		'array_reduce'               => array( 2 ),
		'array_walk'                 => array( 1 ),
		'array_walk_recursive'       => array( 1 ),
		'usort'                      => array( 1 ),
		'uasort'                     => array( 1 ),
		'uksort'                     => array( 1 ),
		'set_error_handler'          => array( 0 ),
		'set_exception_handler'      => array( 0 ),
		'register_shutdown_function' => array( 0 ),
	);

	/**
	 * Hook registration functions and callback argument position.
	 *
	 * @since 2.2.0
	 * @var array<string, int>
	 */
	private const HOOK_CALLBACK_ARGS = array(
		'add_action'                 => 1,
		'add_filter'                 => 1,
		'add_shortcode'              => 1,
		'register_activation_hook'   => 1,
		'register_deactivation_hook' => 1,
		'register_uninstall_hook'    => 1,
	);

	/**
	 * Known plugin-defined functions.
	 *
	 * @since 2.2.0
	 * @var array<string, true>
	 */
	private $defined_functions = array();

	/**
	 * Known plugin-defined classes.
	 *
	 * @since 2.2.0
	 * @var array<string, true>
	 */
	private $defined_classes = array();

	/**
	 * Known plugin-defined methods by class.
	 *
	 * @since 2.2.0
	 * @var array<string, array<string, true>>
	 */
	private $defined_class_methods = array();

	/**
	 * Pretty printer instance used to normalize expressions.
	 *
	 * @since 2.2.0
	 * @var Standard
	 */
	private $pretty_printer;

	/**
	 * Gets the categories for the check.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Gets the check description.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Detects high-risk runtime patterns that commonly lead to fatal errors, such as unguarded requires, optional integrations without existence checks, and fragile callback usage.', 'plugin-check' );
	}

	/**
	 * Gets the check documentation URL.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/plugin-basics/best-practices/', 'plugin-check' );
	}

	/**
	 * Runs the check against plugin files.
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result $result The check result.
	 * @param array        $files  Plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );
		if ( empty( $php_files ) ) {
			return;
		}

		$this->pretty_printer = new Standard();

		$asts = $this->parse_files( $php_files );
		if ( empty( $asts ) ) {
			return;
		}

		$this->collect_defined_symbols( $asts );

		foreach ( $asts as $file => $stmts ) {
			$this->analyze_statement_list(
				$result,
				$file,
				$stmts,
				$this->new_guard_context(),
				''
			);
		}
	}

	/**
	 * Parses all provided files to AST.
	 *
	 * @since 2.2.0
	 *
	 * @param array $files Files to parse.
	 * @return array<string, array>
	 */
	private function parse_files( array $files ): array {
		$parser = ( new ParserFactory() )->create( ParserFactory::PREFER_PHP7 );
		$asts   = array();

		foreach ( $files as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $file );
			if ( false === $contents || '' === $contents ) {
				continue;
			}

			try {
				$ast = $parser->parse( $contents );
				if ( is_array( $ast ) ) {
					$asts[ $file ] = $ast;
				}
			} catch ( Error $e ) {
				// Ignore parse failures; parse errors are handled by a dedicated check.
			}
		}

		return $asts;
	}

	/**
	 * Collects plugin-defined functions/classes/methods.
	 *
	 * @since 2.2.0
	 *
	 * @param array<string, array> $asts AST map by file.
	 */
	private function collect_defined_symbols( array $asts ) {
		foreach ( $asts as $stmts ) {
			$this->collect_defined_symbols_from_statements( $stmts, '' );
		}
	}

	/**
	 * Collects plugin-defined symbols from statements recursively.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $stmts          Statements.
	 * @param string $namespace_name Current namespace.
	 */
	private function collect_defined_symbols_from_statements( array $stmts, string $namespace_name ) {
		foreach ( $stmts as $stmt ) {
			if ( ! $stmt instanceof Node ) {
				continue;
			}

			if ( $stmt instanceof Stmt\Namespace_ ) {
				$new_namespace = $stmt->name instanceof Name ? $stmt->name->toString() : '';
				$this->collect_defined_symbols_from_statements( $stmt->stmts, $new_namespace );
				continue;
			}

			if ( $stmt instanceof Stmt\Function_ ) {
				$name                             = $this->normalize_symbol_name( $this->qualify_name( $stmt->name->toString(), $namespace_name ) );
				$this->defined_functions[ $name ] = true;
				$this->defined_functions[ $this->normalize_symbol_name( $stmt->name->toString() ) ] = true;
				continue;
			}

			if ( $stmt instanceof Stmt\Class_ && $stmt->name instanceof Node\Identifier ) {
				$class_name                                 = $stmt->name->toString();
				$qualified_class                            = $this->normalize_symbol_name( $this->qualify_name( $class_name, $namespace_name ) );
				$short_class_name                           = $this->normalize_symbol_name( $class_name );
				$this->defined_classes[ $qualified_class ]  = true;
				$this->defined_classes[ $short_class_name ] = true;

				if ( ! isset( $this->defined_class_methods[ $qualified_class ] ) ) {
					$this->defined_class_methods[ $qualified_class ] = array();
				}
				if ( ! isset( $this->defined_class_methods[ $short_class_name ] ) ) {
					$this->defined_class_methods[ $short_class_name ] = array();
				}

				foreach ( $stmt->getMethods() as $method ) {
					$method_name = strtolower( $method->name->toString() );
					$this->defined_class_methods[ $qualified_class ][ $method_name ]  = true;
					$this->defined_class_methods[ $short_class_name ][ $method_name ] = true;
				}
			}
		}
	}

	/**
	 * Analyzes a statement list recursively with the given guard context.
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result $result        Check result.
	 * @param string       $file          Absolute file path.
	 * @param array        $stmts         Statement list.
	 * @param array        $guard_context Active guard context.
	 * @param string       $current_class Current class name if inside class scope.
	 */
	private function analyze_statement_list(
		Check_Result $result,
		string $file,
		array $stmts,
		array $guard_context,
		string $current_class
	) {
		foreach ( $stmts as $stmt ) {
			if ( ! $stmt instanceof Node ) {
				continue;
			}

			if ( $stmt instanceof Stmt\Namespace_ ) {
				$this->analyze_statement_list( $result, $file, $stmt->stmts, $guard_context, '' );
				continue;
			}

			if ( $stmt instanceof Stmt\Class_ ) {
				$class_name = '';
				if ( $stmt->name instanceof Node\Identifier ) {
					$class_name = $this->normalize_symbol_name( $stmt->name->toString() );
				}
				$this->analyze_statement_list( $result, $file, $stmt->stmts, $guard_context, $class_name );
				continue;
			}

			if ( $stmt instanceof Stmt\Function_ || $stmt instanceof Stmt\ClassMethod ) {
				if ( is_array( $stmt->stmts ) ) {
					$this->analyze_statement_list( $result, $file, $stmt->stmts, $guard_context, $current_class );
				}
				continue;
			}

			if ( $stmt instanceof Stmt\If_ ) {
				$truthy_guards = $this->extract_guard_context_from_condition( $stmt->cond, true );
				$falsy_guards  = $this->extract_guard_context_from_condition( $stmt->cond, false );

				$this->analyze_statement_list(
					$result,
					$file,
					$stmt->stmts,
					$this->merge_guard_context( $guard_context, $truthy_guards ),
					$current_class
				);

				foreach ( $stmt->elseifs as $elseif ) {
					$elseif_guards = $this->extract_guard_context_from_condition( $elseif->cond, true );
					$this->analyze_statement_list(
						$result,
						$file,
						$elseif->stmts,
						$this->merge_guard_context( $guard_context, $elseif_guards ),
						$current_class
					);
				}

				if ( $stmt->else instanceof Stmt\Else_ ) {
					$this->analyze_statement_list(
						$result,
						$file,
						$stmt->else->stmts,
						$this->merge_guard_context( $guard_context, $falsy_guards ),
						$current_class
					);
				}
				continue;
			}

			if ( $stmt instanceof Stmt\TryCatch ) {
				$this->analyze_statement_list( $result, $file, $stmt->stmts, $guard_context, $current_class );
				foreach ( $stmt->catches as $catch ) {
					$this->analyze_statement_list( $result, $file, $catch->stmts, $guard_context, $current_class );
				}
				if ( $stmt->finally instanceof Stmt\Finally_ ) {
					$this->analyze_statement_list( $result, $file, $stmt->finally->stmts, $guard_context, $current_class );
				}
				continue;
			}

			$this->walk_node(
				$stmt,
				function ( Node $node ) use ( $result, $file, $guard_context, $current_class ) {
					$this->analyze_node( $result, $file, $node, $guard_context, $current_class );
				}
			);
		}
	}

	/**
	 * Analyzes a node for risky runtime-fatal patterns.
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result $result        Check result.
	 * @param string       $file          Absolute file path.
	 * @param Node         $node          Current AST node.
	 * @param array        $guard_context Active guard context.
	 * @param string       $current_class Current class name if inside class scope.
	 */
	private function analyze_node(
		Check_Result $result,
		string $file,
		Node $node,
		array $guard_context,
		string $current_class
	) {
		if ( $node instanceof Expr\Include_
			&& in_array( $node->type, array( Expr\Include_::TYPE_REQUIRE, Expr\Include_::TYPE_REQUIRE_ONCE ), true )
			&& ! $this->is_static_require_path( $node->expr )
			&& ! $this->has_file_guard_for_expression( $guard_context, $node->expr )
		) {
			$this->add_result_error_for_file(
				$result,
				__( 'Dynamic require/require_once should be guarded. Check the path with file_exists() or is_readable() before requiring it to avoid runtime fatals.', 'plugin-check' ),
				'unguarded_dynamic_require',
				$file,
				$node->getStartLine(),
				0,
				'https://www.php.net/manual/en/function.require.php',
				6
			);
		}

		if ( $node instanceof Expr\FuncCall ) {
			$this->analyze_function_call_for_integration_guard( $result, $file, $node, $guard_context );
			$this->analyze_function_call_for_dynamic_callback_guard( $result, $file, $node, $guard_context );
			$this->analyze_function_call_for_hook_callback_guard( $result, $file, $node, $guard_context, $current_class );
		}

		if ( $node instanceof Expr\New_ || $node instanceof Expr\StaticCall ) {
			$this->analyze_optional_class_usage( $result, $file, $node, $guard_context );
		}
	}

	/**
	 * Analyzes integration function calls that should be guarded with function_exists().
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result  $result        Check result.
	 * @param string        $file          Absolute file path.
	 * @param Expr\FuncCall $call          Function call node.
	 * @param array         $guard_context Active guard context.
	 */
	private function analyze_function_call_for_integration_guard(
		Check_Result $result,
		string $file,
		Expr\FuncCall $call,
		array $guard_context
	) {
		$function_name = $this->get_function_name( $call );
		if ( '' === $function_name ) {
			return;
		}

		if ( ! $this->looks_like_optional_integration_function( $function_name ) ) {
			return;
		}

		if ( isset( $this->defined_functions[ $function_name ] ) ) {
			return;
		}

		if ( isset( $guard_context['functions'][ $function_name ] ) ) {
			return;
		}

		$this->add_result_error_for_file(
			$result,
			sprintf(
				/* translators: %s: Function name. */
				__( 'Potential optional integration function call to %s() without a function_exists() guard. Add a guard before calling it to avoid fatal errors when the integration is inactive.', 'plugin-check' ),
				esc_html( ltrim( $function_name, '\\' ) )
			),
			'missing_function_exists_guard_for_integration_call',
			$file,
			$call->getStartLine(),
			0,
			'https://www.php.net/manual/en/function.function-exists.php',
			6
		);
	}

	/**
	 * Analyzes optional class usage that should be guarded with class_exists().
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result $result        Check result.
	 * @param string       $file          Absolute file path.
	 * @param Expr         $expr          New_/StaticCall expression.
	 * @param array        $guard_context Active guard context.
	 */
	private function analyze_optional_class_usage(
		Check_Result $result,
		string $file,
		Expr $expr,
		array $guard_context
	) {
		$class_name = $this->get_expr_class_name( $expr );
		if ( '' === $class_name ) {
			return;
		}

		if ( in_array( $class_name, array( 'self', 'static', 'parent' ), true ) ) {
			return;
		}

		if ( ! $this->looks_like_optional_integration_class( $class_name ) ) {
			return;
		}

		if ( isset( $this->defined_classes[ $class_name ] ) ) {
			return;
		}

		if ( isset( $guard_context['classes'][ $class_name ] ) ) {
			return;
		}

		$this->add_result_error_for_file(
			$result,
			sprintf(
				/* translators: %s: Class name. */
				__( 'Optional integration class %s is used without class_exists() guard. Verify availability before instantiation/static usage to prevent runtime fatals.', 'plugin-check' ),
				esc_html( ltrim( $class_name, '\\' ) )
			),
			'missing_class_exists_guard_for_optional_class',
			$file,
			$expr->getStartLine(),
			0,
			'https://www.php.net/manual/en/function.class-exists.php',
			6
		);
	}

	/**
	 * Analyzes dynamic callback invocations for missing is_callable() guards.
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result  $result        Check result.
	 * @param string        $file          Absolute file path.
	 * @param Expr\FuncCall $call          Function call.
	 * @param array         $guard_context Active guard context.
	 */
	private function analyze_function_call_for_dynamic_callback_guard(
		Check_Result $result,
		string $file,
		Expr\FuncCall $call,
		array $guard_context
	) {
		$function_name = $this->get_function_name( $call );
		if ( '' === $function_name || ! isset( self::CALLBACK_FUNCTION_ARGS[ $function_name ] ) ) {
			return;
		}

		foreach ( self::CALLBACK_FUNCTION_ARGS[ $function_name ] as $position ) {
			if ( ! isset( $call->args[ $position ]->value ) ) {
				continue;
			}

			$callback_expr = $call->args[ $position ]->value;
			if ( ! $this->is_dynamic_callback_expression( $callback_expr ) ) {
				continue;
			}

			$callback_key = $this->normalize_expression_key( $callback_expr );
			if ( isset( $guard_context['callables'][ $callback_key ] ) ) {
				continue;
			}

			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: Function name. */
					__( 'Dynamic callback passed to %s() should be validated with is_callable() before invocation to avoid runtime fatals.', 'plugin-check' ),
					esc_html( ltrim( $function_name, '\\' ) )
				),
				'missing_is_callable_guard_for_dynamic_callback',
				$file,
				$call->getStartLine(),
				0,
				'https://www.php.net/manual/en/function.is-callable.php',
				6
			);
		}
	}

	/**
	 * Analyzes hook callbacks for possible missing method/class guards.
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result  $result        Check result.
	 * @param string        $file          Absolute file path.
	 * @param Expr\FuncCall $call          Function call.
	 * @param array         $guard_context Active guard context.
	 * @param string        $current_class Current class name.
	 */
	private function analyze_function_call_for_hook_callback_guard(
		Check_Result $result,
		string $file,
		Expr\FuncCall $call,
		array $guard_context,
		string $current_class
	) {
		$function_name = $this->get_function_name( $call );
		if ( '' === $function_name || ! isset( self::HOOK_CALLBACK_ARGS[ $function_name ] ) ) {
			return;
		}

		$callback_arg_index = self::HOOK_CALLBACK_ARGS[ $function_name ];
		if ( ! isset( $call->args[ $callback_arg_index ]->value ) ) {
			return;
		}

		$callback = $this->resolve_hook_callback_target( $call->args[ $callback_arg_index ]->value, $current_class );
		if ( empty( $callback['class_or_target'] ) || empty( $callback['method'] ) ) {
			return;
		}

		$target_key = $callback['class_or_target'];
		$method_key = strtolower( $callback['method'] );

		if ( $this->is_known_plugin_method_callback( $target_key, $method_key ) ) {
			return;
		}

		$guard_key = $target_key . '::' . $method_key;
		if ( isset( $guard_context['methods'][ $guard_key ] ) || isset( $guard_context['classes'][ $target_key ] ) ) {
			return;
		}

		$this->add_result_error_for_file(
			$result,
			sprintf(
				/* translators: 1: Function name, 2: Callback target, 3: Method name. */
				__( 'Hook callback passed to %1$s() references %2$s::%3$s without class_exists()/method_exists() guard. Validate callback availability to avoid runtime fatals.', 'plugin-check' ),
				esc_html( ltrim( $function_name, '\\' ) ),
				esc_html( ltrim( $target_key, '\\' ) ),
				esc_html( $method_key )
			),
			'unguarded_hook_callback_method',
			$file,
			$call->getStartLine(),
			0,
			'https://developer.wordpress.org/reference/functions/add_action/',
			6
		);
	}

	/**
	 * Resolves callback target and method from hook callback argument.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr   $expr          Callback expression.
	 * @param string $current_class Current class name.
	 * @return array{class_or_target:string, method:string}
	 */
	private function resolve_hook_callback_target( Expr $expr, string $current_class ): array {
		if ( $expr instanceof Scalar\String_ ) {
			$value = $expr->value;
			if ( false !== strpos( $value, '::' ) ) {
				list( $class, $method ) = explode( '::', $value, 2 );
				return array(
					'class_or_target' => $this->normalize_symbol_name( $class ),
					'method'          => strtolower( $method ),
				);
			}
			return array(
				'class_or_target' => '',
				'method'          => '',
			);
		}

		if ( $expr instanceof Expr\Array_
			&& isset( $expr->items[0]->value, $expr->items[1]->value )
		) {
			$method = $this->extract_literal_string( $expr->items[1]->value );
			if ( '' === $method ) {
				return array(
					'class_or_target' => '',
					'method'          => '',
				);
			}

			$target = $this->resolve_callback_target( $expr->items[0]->value, $current_class );
			if ( '' === $target ) {
				return array(
					'class_or_target' => '',
					'method'          => '',
				);
			}

			return array(
				'class_or_target' => $target,
				'method'          => strtolower( $method ),
			);
		}

		return array(
			'class_or_target' => '',
			'method'          => '',
		);
	}

	/**
	 * Resolves callback target expression to a comparable key.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr   $expr          Target expression.
	 * @param string $current_class Current class name.
	 * @return string
	 */
	private function resolve_callback_target( Expr $expr, string $current_class ): string {
		if ( $expr instanceof Scalar\String_ ) {
			return $this->normalize_symbol_name( $expr->value );
		}

		if ( $expr instanceof Expr\ClassConstFetch
			&& $expr->name instanceof Node\Identifier
			&& 'class' === strtolower( $expr->name->toString() )
			&& $expr->class instanceof Name
		) {
			return $this->normalize_symbol_name( $expr->class->toString() );
		}

		if ( $expr instanceof Expr\Variable && 'this' === $this->extract_variable_name( $expr ) ) {
			return '' !== $current_class ? $current_class : '$this';
		}

		if ( $expr instanceof Expr\New_ && $expr->class instanceof Name ) {
			return $this->normalize_symbol_name( $expr->class->toString() );
		}

		return $this->normalize_expression_key( $expr );
	}

	/**
	 * Checks whether callback target+method are known in plugin-defined classes.
	 *
	 * @since 2.2.0
	 *
	 * @param string $target Target class key.
	 * @param string $method Method name.
	 * @return bool
	 */
	private function is_known_plugin_method_callback( string $target, string $method ): bool {
		if ( isset( $this->defined_class_methods[ $target ][ $method ] ) ) {
			return true;
		}

		$short_target = $this->normalize_symbol_name( basename( str_replace( '\\', '/', $target ) ) );
		return isset( $this->defined_class_methods[ $short_target ][ $method ] );
	}

	/**
	 * Extracts positive guards from a condition expression.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr $expr   Condition expression.
	 * @param bool $truthy Whether analyzing the truthy branch of the condition.
	 * @return array Guard context.
	 */
	private function extract_guard_context_from_condition( Expr $expr, bool $truthy ): array {
		$guards = $this->new_guard_context();

		if ( $expr instanceof Expr\BooleanNot ) {
			return $this->extract_guard_context_from_condition( $expr->expr, ! $truthy );
		}

		if ( $expr instanceof Expr\BinaryOp\BooleanAnd ) {
			if ( $truthy ) {
				$left  = $this->extract_guard_context_from_condition( $expr->left, true );
				$right = $this->extract_guard_context_from_condition( $expr->right, true );
				return $this->merge_guard_context( $left, $right );
			}
			return $guards;
		}

		if ( $expr instanceof Expr\BinaryOp\BooleanOr ) {
			if ( ! $truthy ) {
				$left  = $this->extract_guard_context_from_condition( $expr->left, false );
				$right = $this->extract_guard_context_from_condition( $expr->right, false );
				return $this->merge_guard_context( $left, $right );
			}
			return $guards;
		}

		if ( ! $truthy || ! $expr instanceof Expr\FuncCall ) {
			return $guards;
		}

		$function_name = $this->get_function_name( $expr );
		if ( '' === $function_name ) {
			return $guards;
		}

		if ( 'function_exists' === $function_name && isset( $expr->args[0]->value ) ) {
			$function = $this->extract_literal_string_or_class_const( $expr->args[0]->value );
			if ( '' !== $function ) {
				$guards['functions'][ $this->normalize_symbol_name( $function ) ] = true;
			}
		}

		if ( 'class_exists' === $function_name && isset( $expr->args[0]->value ) ) {
			$class = $this->extract_literal_string_or_class_const( $expr->args[0]->value );
			if ( '' !== $class ) {
				$guards['classes'][ $this->normalize_symbol_name( $class ) ] = true;
			}
		}

		if ( 'method_exists' === $function_name && isset( $expr->args[0]->value, $expr->args[1]->value ) ) {
			$method = $this->extract_literal_string( $expr->args[1]->value );
			if ( '' !== $method ) {
				$target_key = $this->normalize_expression_key( $expr->args[0]->value );
				$guards['methods'][ $target_key . '::' . strtolower( $method ) ] = true;

				$class_target = $this->extract_literal_string_or_class_const( $expr->args[0]->value );
				if ( '' !== $class_target ) {
					$guards['methods'][ $this->normalize_symbol_name( $class_target ) . '::' . strtolower( $method ) ] = true;
				}
			}
		}

		if ( 'is_callable' === $function_name && isset( $expr->args[0]->value ) ) {
			$guards['callables'][ $this->normalize_expression_key( $expr->args[0]->value ) ] = true;
		}

		if ( in_array( $function_name, array( 'file_exists', 'is_readable' ), true ) && isset( $expr->args[0]->value ) ) {
			$guards['files'][ $this->normalize_expression_key( $expr->args[0]->value ) ] = true;
		}

		return $guards;
	}

	/**
	 * Creates a new, empty guard context.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	private function new_guard_context(): array {
		return array(
			'functions' => array(),
			'classes'   => array(),
			'methods'   => array(),
			'callables' => array(),
			'files'     => array(),
		);
	}

	/**
	 * Merges two guard contexts.
	 *
	 * @since 2.2.0
	 *
	 * @param array $first  First context.
	 * @param array $second Second context.
	 * @return array
	 */
	private function merge_guard_context( array $first, array $second ): array {
		$merged = $this->new_guard_context();
		foreach ( array_keys( $merged ) as $key ) {
			$merged[ $key ] = $first[ $key ] + $second[ $key ];
		}
		return $merged;
	}

	/**
	 * Checks whether a dynamic path has a matching file_exists/is_readable guard.
	 *
	 * @since 2.2.0
	 *
	 * @param array $guard_context Guard context.
	 * @param Expr  $expr          Path expression.
	 * @return bool
	 */
	private function has_file_guard_for_expression( array $guard_context, Expr $expr ): bool {
		$key = $this->normalize_expression_key( $expr );
		if ( isset( $guard_context['files'][ $key ] ) ) {
			return true;
		}

		foreach ( $guard_context['files'] as $guarded_key => $value ) {
			if ( str_contains( $key, $guarded_key ) || str_contains( $guarded_key, $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines whether a require/include path is clearly static.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr $expr Path expression.
	 * @return bool
	 */
	private function is_static_require_path( Expr $expr ): bool {
		if ( $expr instanceof Scalar\String_ ) {
			return true;
		}

		if ( $expr instanceof Expr\BinaryOp\Concat ) {
			return $this->is_static_require_path_part( $expr->left ) && $this->is_static_require_path_part( $expr->right );
		}

		if ( $expr instanceof Scalar\Encapsed ) {
			foreach ( $expr->parts as $part ) {
				if ( ! $this->is_static_require_path_part( $part ) ) {
					return false;
				}
			}
			return true;
		}

		return false;
	}

	/**
	 * Determines whether part of a path expression is clearly static.
	 *
	 * @since 2.2.0
	 *
	 * @param Node $part Path part.
	 * @return bool
	 */
	private function is_static_require_path_part( Node $part ): bool {
		if ( $part instanceof Scalar\String_
			|| $part instanceof Scalar\EncapsedStringPart
			|| $part instanceof Scalar\MagicConst\Dir
			|| $part instanceof Scalar\MagicConst\File
		) {
			return true;
		}

		if ( $part instanceof Expr\BinaryOp\Concat ) {
			return $this->is_static_require_path_part( $part->left ) && $this->is_static_require_path_part( $part->right );
		}

		if ( $part instanceof Expr\FuncCall ) {
			$name = $this->get_function_name( $part );
			if ( in_array( $name, array( 'dirname', 'trailingslashit' ), true ) && isset( $part->args[0]->value ) ) {
				return $this->is_static_require_path_part( $part->args[0]->value );
			}
		}

		return false;
	}

	/**
	 * Whether callback expression is dynamic (variable/expression-based).
	 *
	 * @since 2.2.0
	 *
	 * @param Expr $expr Callback expression.
	 * @return bool
	 */
	private function is_dynamic_callback_expression( Expr $expr ): bool {
		if ( $expr instanceof Expr\Closure || $expr instanceof Expr\ArrowFunction || $expr instanceof Scalar\String_ ) {
			return false;
		}

		if ( $expr instanceof Expr\Array_ ) {
			return false;
		}

		if ( $expr instanceof Expr\Variable
			|| $expr instanceof Expr\ArrayDimFetch
			|| $expr instanceof Expr\PropertyFetch
			|| $expr instanceof Expr\StaticPropertyFetch
			|| $expr instanceof Expr\FuncCall
			|| $expr instanceof Expr\MethodCall
			|| $expr instanceof Expr\StaticCall
			|| $expr instanceof Expr\Ternary
			|| $expr instanceof Expr\BinaryOp
			|| $expr instanceof Expr\Coalesce
		) {
			return true;
		}

		return true;
	}

	/**
	 * Returns function name for a function call.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr\FuncCall $call Function call.
	 * @return string
	 */
	private function get_function_name( Expr\FuncCall $call ): string {
		if ( ! $call->name instanceof Name ) {
			return '';
		}
		return $this->normalize_symbol_name( $call->name->toString() );
	}

	/**
	 * Returns class name from New_/StaticCall expression.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr $expr Expression.
	 * @return string
	 */
	private function get_expr_class_name( Expr $expr ): string {
		$class_node = null;
		if ( $expr instanceof Expr\New_ ) {
			$class_node = $expr->class;
		} elseif ( $expr instanceof Expr\StaticCall ) {
			$class_node = $expr->class;
		}

		if ( $class_node instanceof Name ) {
			return $this->normalize_symbol_name( $class_node->toString() );
		}

		return '';
	}

	/**
	 * Checks whether function name looks like an optional integration function.
	 *
	 * @since 2.2.0
	 *
	 * @param string $function_name Canonical function name.
	 * @return bool
	 */
	private function looks_like_optional_integration_function( string $function_name ): bool {
		foreach ( self::INTEGRATION_FUNCTION_PREFIXES as $prefix ) {
			if ( str_starts_with( $function_name, $prefix ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks whether class name looks like an optional integration class.
	 *
	 * @since 2.2.0
	 *
	 * @param string $class_name Canonical class name.
	 * @return bool
	 */
	private function looks_like_optional_integration_class( string $class_name ): bool {
		foreach ( self::INTEGRATION_CLASS_PREFIXES as $prefix ) {
			if ( str_starts_with( $class_name, $prefix ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Extracts literal string from supported expression types.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr $expr Expression.
	 * @return string
	 */
	private function extract_literal_string( Expr $expr ): string {
		if ( $expr instanceof Scalar\String_ ) {
			return $expr->value;
		}
		return '';
	}

	/**
	 * Extracts literal string or class const name from expression.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr $expr Expression.
	 * @return string
	 */
	private function extract_literal_string_or_class_const( Expr $expr ): string {
		if ( $expr instanceof Scalar\String_ ) {
			return $expr->value;
		}

		if ( $expr instanceof Expr\ClassConstFetch
			&& $expr->name instanceof Node\Identifier
			&& 'class' === strtolower( $expr->name->toString() )
			&& $expr->class instanceof Name
		) {
			return $expr->class->toString();
		}

		return '';
	}

	/**
	 * Extracts variable name when available.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr\Variable $expr Variable expression.
	 * @return string
	 */
	private function extract_variable_name( Expr\Variable $expr ): string {
		return is_string( $expr->name ) ? $expr->name : '';
	}

	/**
	 * Normalizes symbol names for comparisons.
	 *
	 * @since 2.2.0
	 *
	 * @param string $name Symbol name.
	 * @return string
	 */
	private function normalize_symbol_name( string $name ): string {
		return strtolower( ltrim( trim( $name ), '\\' ) );
	}

	/**
	 * Normalizes an expression to a stable key.
	 *
	 * @since 2.2.0
	 *
	 * @param Expr $expr Expression.
	 * @return string
	 */
	private function normalize_expression_key( Expr $expr ): string {
		if ( $expr instanceof Expr\Variable ) {
			$name = $this->extract_variable_name( $expr );
			if ( '' !== $name ) {
				return '$' . strtolower( $name );
			}
		}

		$printed = $this->pretty_printer->prettyPrintExpr( $expr );
		$printed = preg_replace( '/\s+/', '', strtolower( $printed ) );

		return '' !== $printed ? $printed : md5( serialize( $expr ) );
	}

	/**
	 * Qualifies a name with namespace.
	 *
	 * @since 2.2.0
	 *
	 * @param string $name           Name.
	 * @param string $namespace_name Namespace.
	 * @return string
	 */
	private function qualify_name( string $name, string $namespace_name ): string {
		if ( '' === $namespace_name ) {
			return $name;
		}
		return $namespace_name . '\\' . $name;
	}

	/**
	 * Walks an AST node recursively.
	 *
	 * @since 2.2.0
	 *
	 * @param Node     $node     Node.
	 * @param callable $callback Callback receiving each node.
	 */
	private function walk_node( Node $node, callable $callback ) {
		$callback( $node );

		foreach ( $node->getSubNodeNames() as $sub_node_name ) {
			$child = $node->{$sub_node_name};

			if ( $child instanceof Node ) {
				$this->walk_node( $child, $callback );
				continue;
			}

			if ( is_array( $child ) ) {
				foreach ( $child as $item ) {
					if ( $item instanceof Node ) {
						$this->walk_node( $item, $callback );
					}
				}
			}
		}
	}
}
