<?php
/**
 * Class WordPress\Plugin_Check\Scanner\PHP_Parser
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Scanner;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

/**
 * Abstract class responsible for parsing files, logging, and processing Abstract Syntax Tree (AST) data.
 *
 * @since 1.7.0
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class PHP_Parser {

	/**
	 * A collection of all file paths to be processed.
	 *
	 * @since 1.7.0
	 * @var   string[]
	 */
	public array $files = array();

	/**
	 * A collection of PHP file paths to be processed.
	 *
	 * @since 1.7.0
	 * @var   string[]
	 */
	public array $files_php = array();

	/**
	 * The full path of the file currently being processed.
	 *
	 * @since 1.7.0
	 * @var   string
	 */
	public string $file = '';

	/**
	 * The relative path of the file currently being processed.
	 *
	 * @since 1.7.0
	 * @var   string
	 */
	public string $file_relative = '';

	/**
	 * Flag to indicate if parent nodes need to be fetched during AST traversal.
	 *
	 * @since 1.7.0
	 * @var   bool
	 */
	public bool $needs_get_parents = false;

	/**
	 * Flag to indicate if sibling nodes need to be fetched during AST traversal.
	 *
	 * @since 1.7.0
	 * @var   bool
	 */
	public bool $needs_get_siblings = false;

	/**
	 * Flag to indicate if the service is initialized and ready.
	 *
	 * @since 1.7.0
	 * @var   bool
	 */
	private bool $ready = false;

	/**
	 * The PHP-Parser NodeFinder instance.
	 *
	 * @since 1.7.0
	 * @var   \PhpParser\NodeFinder
	 */
	public $node_finder;

	/**
	 * The Abstract Syntax Tree (AST) of the current file.
	 *
	 * @since 1.7.0
	 * @var \PhpParser\Node[]|null
	 */
	public $stmts;

	/**
	 * The logging object instance.
	 *
	 * @since 1.7.0
	 * @var   Log
	 */
	private Log $log_object;

	/**
	 * The PHP-Parser PrettyPrinter instance.
	 *
	 * @since 1.7.0
	 * @var   \PhpParser\PrettyPrinter\Standard
	 */
	public $pretty_printer;

	/**
	 * List of known sanitization functions.
	 *
	 * @since 1.7.0
	 * @var   string[]
	 */
	public array $sanitize_functions;

	/**
	 * List of known escaping functions.
	 *
	 * @since 1.7.0
	 * @var   string[]
	 */
	public array $escaping_functions;

	/**
	 * Cache for `define()` statement objects found during parsing.
	 *
	 * @since 1.7.0
	 * @var   array
	 */
	private array $defines_objects = array();

	/**
	 * Flag to indicate if the `define()` statement objects have been loaded.
	 *
	 * @since 1.7.0
	 * @var   bool
	 */
	private bool $defines_objects_loaded = false;

	/**
	 * Cache for variable assignment expressions to avoid re-parsing.
	 *
	 * @since 1.7.0
	 * @var   array
	 */
	private array $cache_assignments_expressions_for_variable = array();

	/**
	 * Constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		$this->log_object         = new Log( $this );
		$this->sanitize_functions = include dirname( __DIR__ ) . '/Vars/sanitize-functions.php';
		$this->escaping_functions = include dirname( __DIR__ ) . '/Vars/escaping-functions.php';
	}

	/**
	 * Loads files.
	 *
	 * @since 1.7.0
	 *
	 * @param array $files Array of files.
	 * @return void
	 */
	public function load_files( $files ) {
		$this->files = $files;

		$this->files_php = array_filter(
			$files,
			function ( $file ) {
				return pathinfo( $file, PATHINFO_EXTENSION ) === 'php';
			}
		);
	}

	/**
	 * Returns relative path.
	 *
	 * @since 1.7.0
	 *
	 * @param string $file File path.
	 * @return string Relative path.
	 */
	public function get_relative_path( $file ) {
		$relative = explode( 'current_plugin/', $file );
		$relative = end( $relative );
		$relative = explode( 'prt_phpunit/', $relative );
		return end( $relative );
	}

	/**
	 * Abstract method to process each file.
	 *
	 * @return mixed The return type and value are determined by the concrete implementation of this method.
	 */
	abstract public function find();

	/**
	 * Loads a file, initializes it, parses its content, and processes further operations if the file is ready.
	 *
	 * @param string $file The path to the file that needs to be loaded.
	 *
	 * @return null Always returns null after attempting to load and process the file.
	 */
	public function load( $file ) {
		if ( $this->init_file( $file ) ) {
			$this->parse_file( $this->file );
			$this->pretty_printer = new Standard();
			if ( $this->is_ready() ) {
				$this->find();
			}
		}

		return null;
	}

	/**
	 * Retrieves the log object.
	 *
	 * @return mixed Returns the log object associated with the instance.
	 */
	public function log() {
		return $this->log_object;
	}

	/**
	 * Initializes a file, setting the file's path and its relative path.
	 * Checks if the file exists before proceeding.
	 *
	 * @param string $file The path to the file to be initialized.
	 *
	 * @return bool Returns true if the file exists and is successfully initialized, otherwise false.
	 */
	public function init_file( $file ) {
		$this->stmts = null;
		if ( ! file_exists( $file ) ) {
			return false;
		}
		$this->file          = $file;
		$this->file_relative = $this->get_relative_path( $this->file );
		return true;
	}

	/**
	 * Initializes the node finder instance for searching specific nodes in the parsed Abstract Syntax Tree (AST).
	 *
	 * @return void
	 */
	public function initialize_node_finder() {
		if ( null === $this->node_finder ) {
			$this->node_finder = new NodeFinder();
		}
	}

	/**
	 * Parses a PHP file and processes its abstract syntax tree (AST).
	 * The method can enhance the AST with additional attributes such as parent and sibling relationships if requested.
	 *
	 * @param string $file The path to the PHP file to be parsed.
	 *
	 * @return void This method does not return a value, but it processes the file and initializes necessary attributes for further usage.
	 */
	private function parse_file( $file ) {
		// Check if this is a PHP file.
		$ext = pathinfo( $file, PATHINFO_EXTENSION );
		if ( in_array( $ext, array( 'php' ), true ) ) {
			// Options.
			// Activate ability to get parents. Performance will be degraded.
			// Get parents using $node->getAttribute('parent').
			$traverser = null;
			if ( $this->needs_get_parents ) {
				$traverser = new NodeTraverser();
				$traverser->addVisitor( new ParentConnectingVisitor() );
			}
			if ( $this->needs_get_siblings ) {
				if ( null === $traverser ) {
					$traverser = new NodeTraverser();
				}
				$traverser->addVisitor( new NodeConnectingVisitor() );
			}

			// Parse file.
			$parser = ( new ParserFactory() )->create( ParserFactory::PREFER_PHP7 );
			try {
				$code        = file_get_contents( $file );
				$this->stmts = $parser->parse( $code );
				if ( ( $this->needs_get_parents || $this->needs_get_siblings ) && null !== $traverser && is_array( $this->stmts ) ) {
					$this->stmts = $traverser->traverse( $this->stmts );
				}
			} catch ( Error $error ) {
				return;
			}
		}
		$this->initialize_node_finder();
		$this->ready = true;
	}

	/**
	 * Parses the provided PHP code and optionally applies traversal for attaching
	 * parent or sibling node relations, based on configuration flags.
	 *
	 * @param string $code The PHP code to be parsed.
	 *
	 * @return array|null Returns an array of statements parsed from the PHP code,
	 *                    or null if an error occurs or the code is empty.
	 */
	public function parse_code( $code ) {
		$stmts = null;
		if ( ! empty( $code ) ) {
			// Activate ability to get parents. Performance will be degraded.
			// Get parents using $node->getAttribute('parent').
			$traverser = null;
			if ( $this->needs_get_parents ) {
				$traverser = new NodeTraverser();
				$traverser->addVisitor( new ParentConnectingVisitor() );
			}

			if ( $this->needs_get_siblings ) {
				if ( null === $traverser ) {
					$traverser = new NodeTraverser();
				}
				$traverser->addVisitor( new NodeConnectingVisitor() );
			}

			$parser = ( new ParserFactory() )->create( ParserFactory::PREFER_PHP7 );
			try {
				$stmts = $parser->parse( $code );
				if ( ( $this->needs_get_parents || $this->needs_get_siblings ) && null !== $traverser && is_array( $stmts ) ) {
					$stmts = $traverser->traverse( $stmts );
				}
			} catch ( Error $error ) {
				return null;
			}
		}
		return $stmts;
	}

	/**
	 * Checks the readiness state of the current instance.
	 *
	 * @return bool Returns true if the instance is ready, otherwise false.
	 */
	public function is_ready() {
		return $this->ready;
	}

	/**
	 * Checks if the given object is of one of the specified classes.
	 *
	 * @since 1.7.0
	 *
	 * @param object $object_name The object to check.
	 * @param array  $classes An array of class names to check against.
	 *
	 * @return bool Returns true if the object's class is in the given array of classes, false otherwise.
	 */
	public function is_object_of_type( $object_name, array $classes ) {
		return in_array( get_class( $object_name ), $classes, true );
	}

	/**
	 * Retrieves the call name from the provided expression.
	 *
	 * This method examines an expression and attempts to extract the associated
	 * call name, handling static calls, fully qualified names, and other
	 * cases based on the given expression type.
	 *
	 * @param mixed $expr The expression to evaluate, typically an instance of a
	 *                    `PhpParser\Node` type like `StaticCall` or `New_`.
	 * @param bool  &$found_in_same_line A reference parameter indicating whether the
	 *                                   call name is found on the same line as the expression.
	 *                                   Defaults to true.
	 *
	 * @return string The extracted call name, or an empty string if no name can
	 *                be determined.
	 */
	public function get_call_name( $expr, &$found_in_same_line = true ) {
		$name = '';

		// Determine the object to evaluate.
		$name_object = null;

		if ( $this->is_object_of_type( $expr, array( 'PhpParser\Node\Expr\StaticCall', 'PhpParser\Node\Expr\New_' ) ) ) {
			$name_object = $expr->class;
		} elseif ( isset( $expr->name ) ) {
			$name_object = $expr->name;
		}

		// Return early if no name object is found.
		if ( empty( $name_object ) ) {
			return $name;
		}

		// Handle PhpParser\Node\Name class.
		if ( $this->is_object_of_type( $name_object, array( 'PhpParser\Node\Name' ) ) ) {
			$name = $name_object->__toString();
		} elseif ( $this->is_object_of_type( $name_object, array( 'PhpParser\Node\Name\FullyQualified' ) ) ) { // Handle PhpParser\Node\Name\FullyQualified class.
			if ( ! empty( $expr->name->parts ) ) {
				$name = implode( '\\', $expr->name->parts );
			} elseif ( ! empty( $expr->class ) && ! empty( $expr->class->parts ) ) {
				$name = implode( '\\', $expr->class->parts );
			}
		} else { // Fallback case for other objects.
			$name = $this->get_possible_string_for_element( $name_object, $found_in_same_line );

			if ( empty( $name ) ) {
				$name = get_class( $name_object );
			}
		}

		return $name;
	}

	/**
	 * Extracts concatenated elements.
	 *
	 * @param mixed $expr The concatenated expression to process.
	 * @param array $elements An array to accumulate the extracted elements (optional).
	 *
	 * @return array An array containing the extracted and concatenated elements.
	 */
	public function extract_concat_elements( $expr, $elements = array() ) {
		if ( $this->is_object_of_type( $expr, array( 'PhpParser\Node\Expr\BinaryOp\Concat' ) ) ) {
			$elements = $this->extract_concat_elements( $expr->left, $elements );
			if ( ! empty( $expr->right ) ) {
				$elements[] = $expr->right;
			}
		} elseif ( $this->is_object_of_type( $expr, array( 'PhpParser\Node\Scalar\Encapsed' ) ) ) {
			if ( ! empty( $expr->parts ) ) {
				$parts = $expr->parts;
				foreach ( $parts as $part ) {
					$elements = $this->extract_concat_elements( $part, $elements );
				}
			}
		} else {
			$elements[] = $expr;
		}

		return $elements;
	}

	/**
	 * Determines if the given expression is a name.
	 *
	 * @param mixed $name_expr The expression to check, potentially representing a name.
	 *
	 * @return bool Returns true if the expression is of the type 'PhpParser\Node\Name' or 'PhpParser\Node\Name\FullyQualified', false otherwise.
	 */
	private function has_name( $name_expr ) {
		if ( empty( $name_expr ) ) {
			return false;
		}
		return $this->is_object_of_type( $name_expr, array( 'PhpParser\Node\Name', 'PhpParser\Node\Name\FullyQualified' ) );
	}

	/**
	 * Determines if the given function call has a recognized name.
	 *
	 * NOTE: $use_context false prevents infinite loop on init_defines, ideally this wouldn't be needed.
	 *
	 * @param object $func_call The function call object to check.
	 * @param bool   $use_context Optional. Whether to use context to resolve the function name. Defaults to true.
	 *
	 * @return bool Returns true if the function call has a recognized name, false otherwise.
	 */
	public function has_function_name( $func_call, $use_context = true ) {
		if ( $this->has_name( $func_call->name ) ) {
			return true;
		}
		if ( $use_context ) {
			$find_name = $this->get_call_name( $func_call );
			if ( ! empty( $find_name ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieve the name of a variable from a node object.
	 *
	 * @param object $node The node object from which to retrieve the variable name.
	 *
	 * @return string The name of the variable.
	 */
	public function get_variable_name( $node ) {
		$name = '';
		if ( 'PhpParser\Node\Arg' === get_class( $node ) ) {
			$name = $this->get_variable_name( $node->value );
		} elseif ( 'PhpParser\Node\Scalar\String_' === get_class( $node ) ) {
			$name = $node->value;
		}
		if ( isset( $node->var ) && ( 'PhpParser\Node\Expr\Variable' === get_class( $node->var ) || 'PhpParser\Node\Expr\ArrayDimFetch' === get_class( $node->var ) ) ) {
			$name = $this->get_variable_name( $node->var );
		}
		if ( isset( $node->name ) ) {
			if ( 'PhpParser\Node\Expr\Variable' === get_class( $node ) ) {
				$name = $node->name;
			} elseif ( 'PhpParser\Node\Scalar\String_' === get_class( $node->name ) ) {
				$name = $node->name->value;
			} elseif ( 'PhpParser\Node\Identifier' === get_class( $node->name ) ) {
				$name = $node->name->name;
			} elseif ( 'PhpParser\Node\VarLikeIdentifier' === get_class( $node->name ) ) {
				$name = $node->name->name;
			} elseif ( 'PhpParser\Node\Name' === get_class( $node->name ) ) {
				$name = $node->name->__toString();
			}
		}
		if ( is_object( $name ) ) {
			$name = $this->get_variable_name( $name );
		}
		return $name;
	}

	/**
	 * Retrieve the dim of a ArrayDimFetch variable from a node object.
	 *
	 * @param object $node The node object from which to retrieve the variable dimension.
	 *
	 * @return array<int, string> The dimensions of the variable.
	 */
	public function extract_dims_values( $node ) {
		$dims = array();
		if ( ! empty( $node->var->dim ) ) {
			$dims = array_merge( $dims, (array) $this->extract_dims_values( $node->var ) );
		}
		if ( ! empty( $node->dim ) ) {
			if ( 'PhpParser\Node\Scalar\String_' === get_class( $node->dim ) ) {
				$dims[] = $node->dim->value;
			}
		}
		return $dims;
	}

	/**
	 * Extracts dimension objects from the given node.
	 *
	 * @param mixed $node The node from which to extract dimension objects.
	 *
	 * @return array An array of dimension objects extracted from the node.
	 */
	public function extract_dims_objects( $node ) {
		$dims = array();
		if ( ! empty( $node->var->dim ) ) {
			$dims = array_merge( $dims, (array) $this->extract_dims_objects( $node->var ) );
		}
		if ( ! empty( $node->dim ) ) {
			$dims[] = $node->dim;
		}
		return $dims;
	}

	/**
	 * Retrieves a STMTS limited to the context (scope) of the given element. As for example, the function where the element is.
	 *
	 * @param object      $element The element (such as a node) for which the context is being retrieved.
	 * @param string|null $is_inside_element_type Will be set to the type of element found, such as `Node\Stmt\Class_`, `Node\Stmt\ClassMethod`, etc., or null.
	 * @param string      $file The file in which the element resides. If provided and differs from the current file, the method will parse the specified file.
	 *
	 * @return array An associative array containing:
	 *               - 'context': The relevant statements (if found) for the element.
	 *               - 'file': The file being analyzed, which may differ if a specific file is passed as an argument.
	 *               - 'class': The class statements (if applicable) for the element.
	 *               - 'contextWrapper': The wrapper node for the context (if available).
	 */
	public function get_contextual_stmts_for_element( $element, &$is_inside_element_type = null, $file = '' ) {
		$return = array(
			'context' => '',
			'file'    => $this->file,
			'class'   => '',
		);

		$element_start_line = method_exists( $element, 'getStartLine' ) ? $element->getStartLine() : 0;
		$element_end_line   = method_exists( $element, 'getEndLine' ) ? $element->getEndLine() : 0;

		$classes = array(
			Node\Stmt\ClassMethod::class,
			Node\Stmt\Class_::class,
			Node\Stmt\Function_::class,
			Node\Stmt\Interface_::class,
		);

		if ( ! empty( $file ) && $file !== $this->file ) {
			$original_file  = $this->file;
			$return['file'] = $file;
			$this->parse_file( $file );
		}

		foreach ( $classes as $class ) {
			$functions = $this->node_finder->findInstanceOf( $this->stmts, $class );
			if ( ! empty( $functions ) ) {
				foreach ( $functions as $function ) {
					if (
						method_exists( $function, 'getStartLine' ) &&
						method_exists( $function, 'getEndLine' ) &&
						$function->getStartLine() <= $element_start_line &&
						$function->getEndLine() >= $element_end_line
					) {
						if ( empty( $return['context'] ) ) {
							$is_inside_element_type = $class;
							if ( property_exists( $function, 'stmts' ) ) {
								$return['context'] = $function->stmts;
							}
							$return['contextWrapper'] = $function;
						}
						if ( empty( $return['class'] ) && Node\Stmt\Class_::class === $class ) {
							if ( property_exists( $function, 'stmts' ) ) {
								$return['class'] = $function->stmts;
							}
						}
					}
				}
			}
		}

		if ( empty( $return['context'] ) ) {
			$return['context'] = $this->stmts;
		}

		if ( ! empty( $original_file ) ) {
			$this->parse_file( $original_file );
		}

		return $return;
	}

	/**
	 * Retrieves the assignment expressions that can affect the value of a given variable.
	 *
	 * @param mixed  $element The variable element to process for finding assignments.
	 * @param string $file The file path to analyze, or an empty string to use the default file context.
	 *
	 * @return array|false An array containing details of the identified assignments, or false if the element is a skippable constant.
	 */
	public function get_assignments_expressions_for_variable( $element, $file = '' ) {
		// Skip known PHP constants. Constant known PHP elements that does not worth the while.
		if ( $this->is_skippable_constant_for_variable_assignments( $element ) ) {
			return false;
		}

		$file = empty( $file ) ? $this->file : $file;

		$cached = $this->get_cache_assignments_expressions_for_variable( $element, $file );

		if ( -1 !== $cached ) {
			return $cached;
		}

		$this->init_defines();

		$final_assigns         = array();
		$possible_assigns      = array();
		$concat_assigns        = array();
		$define_assigns        = array();
		$define_consts         = array();
		$define_class_property = array();
		$define_class_consts   = array();

		$assignments = array(
			'standard'      => array(),
			'concat'        => array(),
			'const'         => array(),
			'classProperty' => array(),
			'classConst'    => array(),
		);

		$context     = $this->get_contextual_stmts_for_element( $element, $is_inside_element_type, $file );
		$stmts       = $context['context'];
		$stmts_class = $context['class'];
		if ( ! empty( $stmts ) ) {
			$assignments['standard']      = $this->node_finder->findInstanceOf( $stmts, Node\Expr\Assign::class );
			$assignments['concat']        = $this->node_finder->findInstanceOf( $stmts, Node\Expr\AssignOp\Concat::class );
			$assignments['const']         = $this->node_finder->findInstanceOf( $stmts, Const_::class );
			$assignments['classProperty'] = $this->node_finder->findInstanceOf( $stmts_class, PropertyProperty::class );
			$assignments['classConst']    = $this->node_finder->findInstanceOf( $stmts_class, ClassConst::class );
		}

		// Process all found assignments.
		$assigns = array_merge(
			$this->defines_objects,
			$assignments['standard'],
			$assignments['concat'],
			$assignments['const'],
			$assignments['classProperty'],
			$assignments['classConst']
		);

		if ( ! empty( $assigns ) ) {
			foreach ( $assigns as $assign ) {
				if ( $this->is_a_define_call( $assign ) ) { // Defines aren't limited by context.
					if ( is_a( $element, 'PhpParser\Node\Expr\ConstFetch' ) ) {
						$element_name = $this->get_variable_name( $element );
						$assign_name  = $this->get_variable_name( $assign->args[0] );
						if ( $element_name === $assign_name ) {
							$define_assigns[] = $assign;
						}
					}
				} elseif ( method_exists( $assign, 'getEndLine' ) && method_exists( $element, 'getEndLine' ) && $assign->getEndLine() < $element->getEndLine() ) { // Only assigns before the $element.
					if ( is_a( $assign, Const_::class ) ) {
						if ( is_a( $element, 'PhpParser\Node\Expr\ConstFetch' ) ) {
							$element_name = $this->get_variable_name( $element );
							$assign_name  = $this->get_variable_name( $assign );
							if ( $element_name === $assign_name ) {
								$define_consts[] = $assign;
							}
						}
					} elseif ( is_a( $assign, PropertyProperty::class ) ) {
						if ( is_a( $element, 'PhpParser\Node\Expr\PropertyFetch' ) ) {
							$element_name = $this->get_variable_name( $element );
							$assign_name  = $this->get_variable_name( $assign );
							if ( $element_name === $assign_name ) {
								$define_class_property[] = $assign;
							}
						}
					} elseif ( is_a( $assign, ClassConst::class ) ) {
						// For now is only able to find ClassConsts that are in the same class.
						if ( isset( $element->class ) ) {
							if ( 'PhpParser\Node\Name' === get_class( $element->class ) ) {
								if ( 'self' === $element->class->parts[0] ) {
									$element_name = $this->get_variable_name( $element );
									$consts       = $assign->consts;
									foreach ( $consts as $const ) {
										$assign_name = $this->get_variable_name( $const );
										if ( $element_name === $assign_name ) {
											$define_class_consts[] = $const;
										}
									}
								}
							}
						}
					} elseif ( is_object( $assign ) && isset( $assign->var ) && get_class( $assign->var ) === get_class( $element ) ) {
						$element_name = $this->get_variable_name( $element );
						$assign_name  = $this->get_variable_name( $assign->var );

						if ( ! empty( $element_name ) ) {
							if ( 'PhpParser\Node\Expr\Variable' === get_class( $element ) ) {
								if ( $element_name === $assign_name ) {
									if ( 'PhpParser\Node\Expr\AssignOp\Concat' === get_class( $assign ) ) {
										$concat_assigns[] = $assign;
									} else {
										$possible_assigns[] = $assign;
									}
								}
							}
							if ( 'PhpParser\Node\Expr\PropertyFetch' === get_class( $element ) ) {
								if ( $element_name === $assign_name ) {
									if ( 'PhpParser\Node\Expr\AssignOp\Concat' === get_class( $assign ) ) {
										$concat_assigns[] = $assign;
									} else {
										$possible_assigns[] = $assign;
									}
								}
							}
							if ( 'PhpParser\Node\Expr\ArrayDimFetch' === get_class( $element ) ) {
								if ( $element_name === $assign_name ) {
									if ( $this->extract_dims_values( $element ) === $this->extract_dims_values( is_object( $assign ) && isset( $assign->var ) ? $assign->var : null ) ) {
										if ( 'PhpParser\Node\Expr\AssignOp\Concat' === get_class( $assign ) ) {
											$concat_assigns[] = $assign;
										} else {
											$possible_assigns[] = $assign;
										}
									}
								}
							}
						}
					}
				}
			}
		}

		if ( ! empty( $define_assigns ) ) {
			foreach ( $define_assigns as $define_assign ) {
				$final_assigns[] = array(
					'expr'        => $define_assign,
					'value'       => $define_assign->args[1],
					'sameContext' => true,
					'type'        => 'define',
					'file'        => $define_assign->getAttribute( 'file' ),
				);
			}
		}

		if ( ! empty( $define_consts ) ) {
			foreach ( $define_consts as $define_const ) {
				$final_assigns[] = array(
					'expr'        => $define_const,
					'value'       => $define_const->value,
					'sameContext' => true,
					'type'        => 'const',
					'file'        => '',
				);
			}
		}

		if ( ! empty( $define_class_consts ) ) {
			foreach ( $define_class_consts as $define_class_const ) {
				$final_assigns[] = array(
					'expr'        => $define_class_const,
					'value'       => $define_class_const->value,
					'sameContext' => true,
					'type'        => 'const',
					'file'        => '',
				);
			}
		}

		// Incorporate class properties but only if there are not already assigns in the function.
		if ( ! empty( $define_class_property ) ) {
			foreach ( $define_class_property as $define_class_property ) {
				if ( ! empty( $define_class_property->default ) ) {
					$skip = false;
					if ( ! empty( $possible_assigns ) ) {
						foreach ( $possible_assigns as $possible_assign ) {
							if ( ! empty( $possible_assign->var ) && is_object( $possible_assign ) && is_object( $possible_assign->var ) && 'PhpParser\Node\Expr\PropertyFetch' === get_class( $possible_assign->var ) ) {
								if ( $this->get_variable_name( $define_class_property ) && $this->get_variable_name( $possible_assign->var ) ) {
									$skip = true;
									break;
								}
							}
						}
					}
					if ( ! $skip ) {
						$final_assigns[] = array(
							'expr'        => $define_class_property,
							'value'       => $define_class_property->default,
							'sameContext' => '',
							'type'        => 'assign',
							'file'        => '',
						);
					}
				}
			}
		}

		if ( ! empty( $possible_assigns ) ) {
			$last_assign_same_execution_context = '';
			$assign_others                      = array();

			// Find assigns in the same execution context and remove all concats that are before them.
			$same_execution_context_lines = $this->get_same_execution_context_lines( $stmts, $element );

			foreach ( $possible_assigns as $possible_assign ) {
				$same_execution_context = false;
				if ( ! empty( $same_execution_context_lines ) ) {
					foreach ( $same_execution_context_lines as $same_execution_context_line ) {
						if ( method_exists( $possible_assign, 'getStartLine' ) && method_exists( $possible_assign, 'getEndLine' ) && $same_execution_context_line['startLine'] === $possible_assign->getStartLine() && $same_execution_context_line['endLine'] === $possible_assign->getEndLine() ) {
							$same_execution_context = true;
							$concat_assigns         = array_filter(
								$concat_assigns,
								function ( $assign ) use ( $possible_assign ) {
									return method_exists( $assign, 'getEndLine' ) && method_exists( $possible_assign, 'getEndLine' ) ? $assign->getEndLine() > $possible_assign->getEndLine() : false;
								}
							);
						}
					}
				}
				if ( $same_execution_context ) {
					$last_assign_same_execution_context = $possible_assign;
					$assign_others                      = array();
				} else {
					$assign_others[] = $possible_assign;
				}
			}

			if ( ! empty( $concat_assigns ) ) {
				foreach ( $concat_assigns as $concat_assign ) {
					$final_assigns[] = array(
						'expr'        => $concat_assign,
						'value'       => ( is_object( $concat_assign ) && $concat_assign instanceof AssignOp ) ? $concat_assign->expr : null,
						'sameContext' => '',
						'type'        => 'concat',
						'file'        => '',
					);
				}
			}

			// Return the closer to the $element.
			if ( ! empty( $assign_others ) ) {
				foreach ( $assign_others as $assign_other ) {
					$final_assigns[] = array(
						'expr'        => $assign_other,
						'value'       => ( is_object( $assign_other ) && $assign_other instanceof AssignOp ) ? $assign_other->expr : null,
						'sameContext' => false,
						'type'        => 'assign',
						'file'        => '',
					);
				}
			}

			if ( ! empty( $last_assign_same_execution_context ) ) {
				$final_assigns[] = array(
					'expr'        => $last_assign_same_execution_context,
					'value'       => ( is_object( $last_assign_same_execution_context ) && $last_assign_same_execution_context instanceof AssignOp ) ? $last_assign_same_execution_context->expr : null,
					'sameContext' => true,
					'type'        => 'assign',
					'file'        => '',
				);
			}
		} elseif ( ! empty( $concat_assigns ) ) {
			foreach ( $concat_assigns as $concat_assign ) {
				$final_assigns[] = array(
					'expr'        => $concat_assign,
					'value'       => ( is_object( $concat_assign ) && $concat_assign instanceof AssignOp ) ? $concat_assign->expr : null,
					'sameContext' => '',
					'type'        => 'concat',
					'file'        => '',
				);
			}
		}

		if ( ! empty( $final_assigns ) ) {
			$this->set_cache_assignments_expressions_for_variable( $element, $file, $final_assigns );
			return $final_assigns;
		}

		$this->set_cache_assignments_expressions_for_variable( $element, $file, false );
		return false;
	}

	/**
	 * Determines if the given element is a skippable constant for variable assignments.
	 * For example, it makes no sense to further check a true value.
	 *
	 * @param mixed $element The element to inspect.
	 *
	 * @return bool Returns true if the element is a constant and matches one of the predefined skippable constants, otherwise false.
	 */
	private function is_skippable_constant_for_variable_assignments( $element ) {
		if ( 'PhpParser\Node\Expr\ConstFetch' !== get_class( $element ) ) {
			return false;
		}

		$skip_constants = array(
			'true',
			'false',
			'null',
			'php_eol',
			'day_in_seconds',
			'hour_in_seconds',
			'minute_in_seconds',
			'doing_ajax',
			'doing_cron',
		);

		$name = strtolower( $this->get_variable_name( $element ) );
		return in_array( $name, $skip_constants, true );
	}


	/**
	 * Retrieves cached assignment expressions for a specific variable.
	 *
	 * @param mixed $element The element representing the variable to look up.
	 * @param mixed $file The file context in which the lookup is performed.
	 *
	 * @return mixed Returns the cached assignment expressions for the variable if available,
	 *               or -1 if no cached data is found.
	 */
	private function get_cache_assignments_expressions_for_variable( $element, $file ) {
		$element_id = $this->get_cache_element_id( $element, $file );
		if ( isset( $this->cache_assignments_expressions_for_variable[ $element_id ] ) ) {
			return $this->cache_assignments_expressions_for_variable[ $element_id ];
		}
		return -1;
	}

	/**
	 * Sets the cache for assignments and expressions associated with a variable.
	 *
	 * @param mixed $element The variable or element to process.
	 * @param mixed $file The file context for the variable or element.
	 * @param mixed $data The data to be cached for the variable or element.
	 *
	 * @return void
	 */
	private function set_cache_assignments_expressions_for_variable( $element, $file, $data ) {
		$element_id = $this->get_cache_element_id( $element, $file );
		$this->cache_assignments_expressions_for_variable[ $element_id ] = $data;
	}

	/**
	 * Generates a cache element ID based on the provided element and file.
	 *
	 * @param mixed  $element The element object to derive properties from.
	 * @param string $file The filename associated with the element.
	 *
	 * @return string Returns a hashed string (MD5) representing the cache element ID.
	 */
	private function get_cache_element_id( $element, $file ) {
		$line_id = $file . '_' . ( method_exists( $element, 'getStartLine' ) ? $element->getStartLine() : 0 ) . '_' . ( method_exists( $element, 'getEndLine' ) ? $element->getEndLine() : 0 ) . '_' . $this->get_variable_name( $element );
		return md5( $line_id );
	}

	/**
	 * Look for a string for that element having in mind the context.
	 *
	 * NOTE: If is not able to reconstruct the string in a reliable way, and is set to $accurate, will return false.
	 *
	 * @param object $element The PHP Parser element to analyze.
	 * @param bool   &$found_in_same_line Reference variable indicating if the string was found
	 *                                    in the same line of context. Defaults to true.
	 * @param bool   $accurate Whether to use accurate context checking. Defaults to true.
	 * @param string $file The file path being analyzed, if applicable. Defaults to an empty string.
	 *
	 * @return string|bool Returns the resolved string if possible, false if accurate context checking fails,
	 *                     or an empty string for non-accurate processing when no string is found.
	 */
	public function get_possible_string_for_element( $element, &$found_in_same_line = true, $accurate = true, $file = '' ) {
		if ( ! is_object( $element ) ) {
			if ( $accurate ) {
				return false;
			} else {
				return '';
			}
		}

		$class = get_class( $element );

		switch ( $class ) {
			case 'PhpParser\Node\Arg':
				if ( isset( $element->value ) ) {
					return $this->get_possible_string_for_element( $element->value, $found_in_same_line, $accurate, $file );
				}
				break;

			case 'PhpParser\Node\Expr\FuncCall':
				if ( $this->has_function_name( $element ) ) {
					$function_name = $this->get_call_name( $element );
					// Check inside a escaping function.
					$functions = array_merge( array( 'trailingslashit', 'untrailingslashit' ), $this->escaping_functions );
					if ( in_array( $function_name, $functions, true ) ) {
						if ( ! empty( $element->args ) && ! empty( $element->args[0] ) && ! empty( $element->args[0]->value ) ) {
							return $this->get_possible_string_for_element( $element->args[0], $found_in_same_line, $accurate, $file );
						}
					}
				}
				break;

			case 'PhpParser\Node\Scalar\String_':
			case 'PhpParser\Node\Scalar\EncapsedStringPart':
				if ( ! empty( $element->value ) ) {
					return $element->value;
				}
				break;

			case 'PhpParser\Node\Identifier':
				if ( ! empty( $element->name ) ) {
					return $element->name;
				}
				break;

			case 'PhpParser\Node\Expr\BinaryOp\Concat':
			case 'PhpParser\Node\Scalar\Encapsed':
				$concat = $this->extract_concat_elements( $element );
				if ( ! empty( $concat ) ) {
					$concat_string = '';
					foreach ( $concat as $c ) {
						$string = $this->get_possible_string_for_element( $c, $found_in_same_line, $accurate, $file );
						if ( false === $string ) {
							return false;
						} else {
							$concat_string .= $string;
						}
					}
					return $concat_string;
				}
				break;
			case 'PhpParser\Node\Expr\Variable':
			case 'PhpParser\Node\Expr\ArrayDimFetch':
			case 'PhpParser\Node\Expr\PropertyFetch':
			case 'PhpParser\Node\Expr\ConstFetch':
			case 'PhpParser\Node\Expr\ClassConstFetch':
				$assigns = $this->get_assignments_expressions_for_variable( $element, $file );
				if ( ! empty( $assigns ) ) {
					$concat_string = '';
					foreach ( $assigns as $assign ) {
						if ( ! $accurate || $assign['sameContext'] ) {
							$string = $this->get_possible_string_for_element( $assign['value'], $found_in_same_line, $accurate, $assign['file'] );
							if ( ! empty( $string ) ) {
								$found_in_same_line = false;
							}
							if ( false === $string ) {
								return false;
							} else {
								$concat_string .= $string;
							}
						}
					}
					return $concat_string;
				}
				break;
		}
		if ( $accurate ) {
			return false;
		} else {
			return '';
		}
	}

	/**
	 * Retrieves the lines of code that share the same execution context as the specified element.
	 *
	 * @param mixed $stmts The statements to process.
	 * @param mixed $element The element to find the matching execution context lines for.
	 *
	 * @return array An array of lines sharing the same execution context as the specified element.
	 */
	private function get_same_execution_context_lines( $stmts, $element ) {
		$same_execution_context_lines = array();
		$lines_array                  = array();
		if ( $this->process_same_execution_context_lines( $stmts, $element, $lines_array ) ) {
			$same_execution_context_lines = $lines_array;
		}

		return $same_execution_context_lines;
	}

	/**
	 * Processes statements to determine if they share the same execution context
	 * lines with a given element and populates an array with their line ranges.
	 *
	 * @param array  $stmts The list of statements to process.
	 * @param object $element The element to compare the statements against.
	 * @param array  &$lines_array The array to store lines that match within the same execution context.
	 *
	 * @return bool Returns true if the element's line range is completely within the range of any processed statement,
	 *              otherwise false.
	 */
	private function process_same_execution_context_lines( $stmts, $element, &$lines_array ) {
		foreach ( $stmts as $stmt ) {
			$class = get_class( $stmt );

			switch ( $class ) :
				case 'PhpParser\Node\Stmt\If_':
				case 'PhpParser\Node\Stmt\Else_':
				case 'PhpParser\Node\Stmt\ElseIf_':
				case 'PhpParser\Node\Stmt\Foreach_':
				case 'PhpParser\Node\Stmt\For_':
				case 'PhpParser\Node\Stmt\While_':
				case 'PhpParser\Node\Stmt\Do_':
				case 'PhpParser\Node\Stmt\Switch_':
				case 'PhpParser\Node\Stmt\TryCatch':
					$available_stmts = array();
					if ( ! empty( $stmt->stmts ) ) {
						$available_stmts[] = $stmt->stmts;
					}
					if ( ! empty( $stmt->elseifs ) ) {
						$elseifs = $stmt->elseifs;
						foreach ( $elseifs as $elseif ) {
							if ( ! empty( $elseif->stmts ) ) {
								$available_stmts[] = $elseif->stmts;
							}
						}
					}
					if ( ! empty( $stmt->else ) ) {
						if ( ! empty( $stmt->else->stmts ) ) {
							$available_stmts[] = $stmt->else->stmts;
						}
					}
					if ( ! empty( $stmt->cases ) ) {
						$cases = $stmt->cases;
						foreach ( $cases as $case ) {
							if ( ! empty( $case->stmts ) ) {
								$available_stmts[] = $case->stmts;
							}
						}
					}

					foreach ( $available_stmts as $check_stmts ) {
						$possible_array = array();
						if ( $this->process_same_execution_context_lines( $check_stmts, $element, $possible_array ) ) {
							$lines_array = array_merge( $lines_array, $possible_array );
							return true;
						}
					}

					break;

				default:
					if ( method_exists( $stmt, 'getStartLine' ) && method_exists( $element, 'getStartLine' ) && method_exists( $stmt, 'getEndLine' ) && method_exists( $element, 'getEndLine' ) && $stmt->getStartLine() <= $element->getStartLine() && $stmt->getEndLine() >= $element->getEndLine() ) {
						return true;
					}
					$lines_array[] = array(
						'startLine' => method_exists( $stmt, 'getStartLine' ) ? $stmt->getStartLine() : 0,
						'endLine'   => method_exists( $stmt, 'getEndLine' ) ? $stmt->getEndLine() : 0,
					);

			endswitch;
		}

		return false;
	}

	/**
	 * Determines whether a specific line number is being logged.
	 *
	 * @param int $line_number The line number to check.
	 *
	 * @return bool
	 */
	public function is_logged_line( $line_number ) {
		// Intended to be extended by the specific class.
		return false;
	}

	/**
	 * Initializes the defines by processing PHP files within a specified folder.
	 * This method ensures that defines are only initialized once per instance.
	 *
	 * @return void
	 */
	private function init_defines() {
		if ( $this->defines_objects_loaded ) {
			return;
		}
		$this->defines_objects_loaded = true;

		$files = $this->files_php;
		if ( empty( $files ) ) {
			return;
		}
		$this->initialize_node_finder();

		foreach ( $files as $file ) {
			$this->init_defines_for_file( $file );
		}
	}

	/**
	 * Initializes constants defined within a specific file.
	 *
	 * @param string $file The file path to analyze for constants.
	 *
	 * @return void
	 */
	private function init_defines_for_file( string $file ) {
		$code  = file_get_contents( $file );
		$stmts = $this->parse_code( $code );

		if ( empty( $stmts ) ) {
			return;
		}

		$function_calls = $this->node_finder->findInstanceOf( $stmts, Node\Expr\FuncCall::class );

		foreach ( $function_calls as $function_call ) {
			$this->init_define_for_function( $function_call, $file );
		}
	}

	/**
	 * Processes a function call and initializes it as a define call if valid.
	 *
	 * @param mixed  $function_call The function call to be processed.
	 * @param string $file The file where the function call is located.
	 *
	 * @return void
	 */
	private function init_define_for_function( $function_call, string $file ) {
		if ( ! $this->is_a_define_call( $function_call ) || ! $this->init_define_is_valid_define_call( $function_call, $file ) ) {
			return;
		}

		$function_call->setAttribute( 'file', $file );
		$this->defines_objects[] = $function_call;
	}

	/**
	 * Validates whether a given function call can be initialized as a define call.
	 *
	 * @param mixed  $function_call The function call to validate.
	 * @param string $file The file where the function call resides, used for error reporting.
	 *
	 * @return bool True if the function call is a valid define call, false otherwise.
	 */
	private function init_define_is_valid_define_call( $function_call, $file ) {
		if ( ! isset( $function_call->args[0], $function_call->args[1] ) ) {
			return false;
		}

		$define_name = $this->get_define_name( $function_call );
		if ( null === $define_name ) {
			return false;
		}

		// I know this is weird, but some people define a define using the value of the same define they are defining and that creates an infinite loop when trying to get the value.
		$elements = $this->extract_concat_elements( $function_call->args[1]->value );
		if ( ! empty( $elements ) && is_array( $elements ) ) {
			foreach ( $elements as $element ) {
				if ( get_class( $element ) === 'PhpParser\Node\Expr\ConstFetch' ) {
					$included_const_fetch_name = $element->name->__toString();
					if ( $define_name === $included_const_fetch_name ) {
						var_dump( 'IS ERROR: Infinite loop detected. Define ' . $define_name . ' at ' . $file . ':' . ( method_exists( $function_call, 'getStartLine' ) ? $function_call->getStartLine() : 0 ) . ' is defined using the value of the same define. Ignoring this define.' );
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Determines if the given element represents a call to the `define` function.
	 *
	 * @param mixed $element The element to inspect, expected to be a function call node.
	 *
	 * @return bool Returns true if the element is a function call to `define`, otherwise false.
	 */
	private function is_a_define_call( $element ) {
		if ( is_a( $element, 'PhpParser\Node\Expr\FuncCall' ) && $this->has_function_name( $element ) && 'define' === $this->get_call_name( $element ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Retrieves the name defined within a function call, specifically when the argument is a string.
	 *
	 * @param object $function_call The function call object, which is expected to contain arguments to be evaluated.
	 *
	 * @return string|null Returns the string value of the define name if the argument is a string, otherwise null.
	 */
	private function get_define_name( $function_call ) {
		if ( get_class( $function_call->args[0]->value ) === 'PhpParser\Node\Scalar\String_' ) {
			return $function_call->args[0]->value->value;
		}
		return null;
	}
}
