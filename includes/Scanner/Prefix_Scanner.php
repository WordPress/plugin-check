<?php
/**
 * Class WordPress\Plugin_Check\Scanner\Prefix_Scanner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Scanner;

use PhpParser\Node;
use PhpParser\Node\Const_;

/**
 * Class Prefix_Scanner
 *
 * This class is responsible for analyzing and logging PHP files for specific
 * prefixes used in WordPress plugin and theme development. It identifies potential
 * issues with prefix usage to maintain code consistency and avoid conflicts.
 */
class Prefix_Scanner extends PHP_Parser {

	/**
	 * List of common prefixes that should be avoided to ensure consistent naming conventions and avoid conflicts.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $not_valid_prefixes;

	/**
	 * Possible prefixes.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $possible_prefixes = array();

	/**
	 * Processes items.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $already_processed_items = array();

	/**
	 * Number of namespaces encountered.
	 *
	 * @since 1.7.0
	 * @var int
	 */
	private int $namespaces_count = 0;

	/**
	 * Final potential prefixes.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	public array $final_prefixes = array();

	/**
	 * Array of actions.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $wp_actions;

	/**
	 * Array of filters.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $wp_filters;

	/**
	 * Array of constants.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $wp_constants;

	/**
	 * Array of global variables.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $wp_globals;

	/**
	 * Array of options.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $wp_options;

	/**
	 * Array of transients.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $wp_transients;

	/**
	 * Array of site transients.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $wp_site_transients;

	/**
	 * Constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		parent::__construct();
		$this->not_valid_prefixes = include dirname( __DIR__ ) . '/Vars/prefix-not-valid.php';

		$wp_actions       = include dirname( __DIR__ ) . '/Vars/wp-actions.php';
		$common_actions   = include dirname( __DIR__ ) . '/Vars/common-actions.php';
		$this->wp_actions = array_merge( $wp_actions, $common_actions );

		$wp_filters       = include dirname( __DIR__ ) . '/Vars/wp-filters.php';
		$common_filters   = include dirname( __DIR__ ) . '/Vars/common-filters.php';
		$this->wp_filters = array_merge( $wp_filters, $common_filters );

		$wp_globals       = include dirname( __DIR__ ) . '/Vars/wp-globals.php';
		$common_globals   = include dirname( __DIR__ ) . '/Vars/common-globals.php';
		$this->wp_globals = array_merge( $wp_globals, $common_globals );

		$this->wp_constants       = include dirname( __DIR__ ) . '/Vars/wp-constants.php';
		$this->wp_options         = include dirname( __DIR__ ) . '/Vars/wp-options.php';
		$this->wp_transients      = include dirname( __DIR__ ) . '/Vars/wp-transients.php';
		$this->wp_site_transients = include dirname( __DIR__ ) . '/Vars/wp-site-transients.php';
	}

	/**
	 * Loads files.
	 *
	 * @since 1.7.0
	 *
	 * @param array $files Array of file paths.
	 * @return void
	 */
	public function load_files( $files ) {
		parent::load_files( $files );

		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				$this->load( $file );
			}

			$this->check_prefixes( 'prefixes_code' );
		}
	}

	/**
	 * Loads file.
	 *
	 * @since 1.7.0
	 *
	 * @param string $file File path.
	 * @return null
	 */
	public function load( $file ) {
		parent::load( $file );

		return null;
	}

	/**
	 * Executes a series of logging operations including tracking function calls, method calls, global declarations, and abstraction declarations.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function find() {
		$this->add_function_calls_to_log();
		$this->add_method_calls_to_log();
		$this->add_globals_declarations_to_log();
		$this->add_abstractions_declarations_to_log();
	}

	/**
	 * Detects calls to specific functions (specially a set of WordPress functions) and adds them to the log with the argument that is of interest regarding prefixes.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	private function add_function_calls_to_log() {
		// Find some calls to specific functions.
		$function_calls = $this->node_finder->findInstanceOf( $this->stmts, Node\Expr\FuncCall::class );
		if ( ! empty( $function_calls ) ) {
			foreach ( $function_calls as $function_call ) {
				if ( $this->has_function_name( $function_call ) ) {
					switch ( $this->get_call_name( $function_call ) ) {
						case 'define':
							if ( isset( $function_call->args[0] ) ) {
								$this->add_call_expression_to_log( $function_call, $function_call->args[0], $this->wp_constants, 0 );
							}
							break;
						case 'add_option':
						case 'add_site_option':
						case 'update_option':
						case 'update_site_option':
							if ( isset( $function_call->args[0] ) ) {
								$this->add_call_expression_to_log( $function_call, $function_call->args[0], $this->wp_options, 0 );
							}
							break;
						case 'add_network_option':
						case 'update_network_option':
						case 'register_setting':
							if ( isset( $function_call->args[1] ) ) {
								$this->add_call_expression_to_log( $function_call, $function_call->args[1], $this->wp_options, 1 );
							}
							break;
						case 'set_transient':
							if ( isset( $function_call->args[0] ) ) {
								$this->add_call_expression_to_log( $function_call, $function_call->args[0], $this->wp_transients, 0 );
							}
							break;
						case 'set_site_transient':
							if ( isset( $function_call->args[0] ) ) {
								$this->add_call_expression_to_log( $function_call, $function_call->args[0], $this->wp_site_transients, 0 );
							}
							break;
						case 'add_shortcode':
						case 'register_post_type':
						case 'register_taxonomy':
							if ( isset( $function_call->args[0] ) ) {
								$this->add_call_expression_to_log( $function_call, $function_call->args[0], array(), 0 );
							}
							break;
						case 'do_action':
							if ( isset( $function_call->args[0] ) ) {
								$this->add_call_expression_to_log( $function_call, $function_call->args[0], $this->wp_actions, 0 );
							}
							break;
						case 'apply_filters':
							if ( isset( $function_call->args[0] ) ) {
								$this->add_call_expression_to_log( $function_call, $function_call->args[0], $this->wp_filters, 0 );
							}
							break;
						case 'wp_schedule_event':
							if ( isset( $function_call->args[2] ) ) {
								$this->add_call_expression_to_log( $function_call, $function_call->args[2], array(), 2 );
							}
							break;
						case 'add_menu_page':
						case 'add_management_page':
						case 'add_options_page':
						case 'add_theme_page':
						case 'add_plugins_page':
						case 'add_users_page':
						case 'add_dashboard_page':
						case 'add_posts_page':
						case 'add_media_page':
						case 'add_links_page':
						case 'add_pages_page':
						case 'add_comments_page':
							if ( isset( $function_call->args[3] ) ) {
								$possible_string = $this->get_possible_string_for_element( $function_call->args[3] );
								if ( ! empty( $possible_string ) && strlen( $possible_string ) > 8 && preg_match( '/[?\/&]/m', $possible_string ) ) {
									break; // Ignore it if this is enough long url-looking string.
								}
								$this->add_call_expression_to_log( $function_call, $function_call->args[3], array(), 3 );
							}
							break;
						case 'add_submenu_page':
							if ( isset( $function_call->args[4] ) ) {
								$possible_string = $this->get_possible_string_for_element( $function_call->args[4] );
								if ( ! empty( $possible_string ) && strlen( $possible_string ) > 8 && preg_match( '/[?\/&]/m', $possible_string ) ) {
									break; // Ignore it if this is enough long url-looking string.
								}
								$this->add_call_expression_to_log( $function_call, $function_call->args[4], array(), 4 );
							}
							break;
						case 'wp_enqueue_script':
						case 'wp_register_script':
						case 'wp_enqueue_style':
						case 'wp_register_style':
							if ( isset( $function_call->args[0] ) ) {
								// Check only obvious cases where this is not the name of a library.
								$possible_string = $this->get_possible_string_for_element( $function_call->args[0] );
								if ( ! empty( $possible_string ) ) {
									$re1 = '/^(my|custom|style|script|css|file)(?!lint|ize)/m'; // Obvious names.
									$re2 = '/^(js)(?!lint|on|ize|hint|api|olor|-cookie)[-_]/m'; // Obvious names, but more careful with js.
									$re3 = '/^(admin|wp)(?!-components|-categories|-tags|-custom-fields|-custom-header|-comments|-users|-forms|-gallery|-widgets|-mediaelement|-tinymce|-ajax-response|-lists|-codemirror|-color-picker|-api|-embed|-jquery-ui-dialog|-auth-check|-pointer|-embed-template-ie|-admin|-colopicker|-editor-font|-reusable-blocks|-blocks|-block-directory|-format-library|-block-editor-content|-editor-classic-layout-styles|-reset-editor-styles|-patterns|-block-library-theme|-theme-plugin-editor|-i18n|-a11y|-backbone|-sanitize|-date|-util|-hooks|-api-request|-plupload)[-_]?/m'; // Beginning by admin or wp but ignoring core names.
									if ( preg_match( $re1, $possible_string ) || preg_match( $re2, $possible_string ) || preg_match( $re3, $possible_string ) ) {
										$this->add_call_expression_to_log( $function_call, $function_call->args[0], array(), 0 );
									}
								}
							}
							break;
					}
				} else {
					$this->log()->add_call_expr( $function_call, 0, 'prefixes_code', true );
				}
			}
		}
	}

	/**
	 * Detects calls to specific methods and adds them to the log.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	private function add_method_calls_to_log() {
		$method_calls = $this->node_finder->findInstanceOf( $this->stmts, Node\Expr\MethodCall::class );
		if ( ! empty( $method_calls ) ) {
			foreach ( $method_calls as $method_call ) {
				if ( ! empty( $method_call->name ) && ! empty( $method_call->var ) ) {
					$methodname = $this->get_possible_string_for_element( $method_call->name );
					$varname    = $this->get_variable_name( $method_call->var );

					// WooCommerce add/update metadata (Heuristic since it checks if the variable is called product).
					if ( str_contains( $varname, 'product' ) && in_array( $methodname, array( 'update_meta_data', 'add_meta_data' ), true ) ) {
						if ( isset( $method_call->args[0] ) ) {
							$this->add_call_expression_to_log( $method_call, $method_call->args[0], array(), 0 );
						}
					}
				}
			}
		}
	}

	/**
	 * Detects calls to globals variables and adds them to the log.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	private function add_globals_declarations_to_log() {
		// Find global variables definitions.

		// Eg: global $example variable.
		$globals = $this->node_finder->findInstanceOf( $this->stmts, Node\Stmt\Global_::class );
		if ( ! empty( $globals ) ) {
			foreach ( $globals as $global ) {
				if ( ! empty( $global->vars ) ) {
					foreach ( $global->vars as $global_variable ) {
						if ( ! empty( $global_variable->name ) && is_string( $global_variable->name ) ) {
							if ( ! in_array( $global_variable->name, $this->wp_globals, true ) ) { // Ignore known core global variables.
								$this->log()->add_var_expr( $global_variable, 'prefixes_code', true );
							}
						}
					}
				}
			}
		}

		// Eg: $GLOBALS['example'] variable.
		$assigns = $this->node_finder->findInstanceOf( $this->stmts, Node\Expr\Assign::class );
		if ( ! empty( $assigns ) ) {
			foreach ( $assigns as $assign ) {
				if ( ! empty( $assign->var ) && is_a( $assign->var, 'PhpParser\Node\Expr\ArrayDimFetch' ) ) {
					$dimfetch = $assign->var;
					if ( 'GLOBALS' === $this->get_variable_name( $dimfetch ) ) {
						$dims = $this->extract_dims_objects( $dimfetch );
						// Ignores all cases where we can't find the dim elements.
						if ( isset( $dims[0] ) ) {
							$this->add_call_expression_to_log( $dimfetch, $dims[0], $this->wp_globals );
						}
					}
				}
			}
		}

		// Find all consts: const EXAMPLE = 'example'.
		$namespaces = $this->node_finder->findInstanceOf( $this->stmts, Node\Stmt\Namespace_::class );
		if ( empty( $namespaces ) ) {
			$consts = $this->node_finder->findInstanceOf( $this->stmts, Const_::class );
			if ( ! empty( $consts ) ) {
				foreach ( $consts as $const ) {
					$is_inside_element_type = null;
					$contextual_stmts       = $this->get_contextual_stmts_for_element( $const, $is_inside_element_type )['context'];
					if ( ! empty( $contextual_stmts ) && ! in_array( $is_inside_element_type, array( 'PhpParser\Node\Stmt\Class_', 'PhpParser\Node\Stmt\Interface_' ), true ) ) { // Ignore const inside a class.
						if ( isset( $const->name ) && method_exists( $const->name, '__toString' ) && ! empty( $const->name->__toString() ) ) {
							$this->log()->add_var_expr( $const, 'prefixes_code', true );
						}
					}
				}
			}
		}
	}

	/**
	 * Logs abstraction declarations including namespaces, classes, functions (excluding anonymous and nested ones),
	 * interfaces, and traits found in the provided statements. If a namespace is defined, only namespace declarations
	 * are logged and the process stops early. Otherwise, all abstraction types are processed and logged accordingly.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	private function add_abstractions_declarations_to_log() {
		// Find namespace (if namespace is defined there is no need to continue).
		$namespaces = $this->node_finder->findInstanceOf( $this->stmts, Node\Stmt\Namespace_::class );
		if ( ! empty( $namespaces ) ) {
			foreach ( $namespaces as $namespace ) {
				$this->log()->add_namespace( $namespace, 'prefixes_code', true );
			}
		} else {
			// Find all class nodes.
			$classes = $this->node_finder->findInstanceOf( $this->stmts, Node\Stmt\Class_::class );
			$this->log()->add_abstraction_declarations( $classes, 'prefixes_code', true );

			// Find all functions (ignores anonymous, and inside abstractions).
			$functions = $this->node_finder->findInstanceOf( $this->stmts, Node\Stmt\Function_::class );
			$this->log()->add_abstraction_declarations( $functions, 'prefixes_code', true );

			// Find all interfaces.
			$interfaces = $this->node_finder->findInstanceOf( $this->stmts, Node\Stmt\Interface_::class );
			$this->log()->add_abstraction_declarations( $interfaces, 'prefixes_code', true );

			// Find all traits.
			$traits = $this->node_finder->findInstanceOf( $this->stmts, Node\Stmt\Trait_::class );
			$this->log()->add_abstraction_declarations( $traits, 'prefixes_code', true );
		}
	}

	/**
	 * Adds a call expression or variable expression to the log based on the given parameters.
	 * Handles various cases depending on the type of the call and the potential string element provided.
	 * Also manages additional context logging if the expression is found on a different line.
	 *
	 * @since 1.7.0
	 *
	 * @param object $call         The call expression node to be logged.
	 * @param mixed  $expr         The expression associated with the call.
	 * @param array  $filter       An optional array of strings to exclude from logging.
	 * @param int    $arg_position The argument position to be used in logging, default is 0.
	 * @return void
	 */
	private function add_call_expression_to_log( $call, $expr, $filter = array(), $arg_position = 0 ) {
		if ( ! isset( $expr ) ) {
			return;
		}
		$possible_string_accurate = false;
		$found_in_same_line       = true;
		$possible_string          = $this->get_possible_string_for_element( $expr, $found_in_same_line, $possible_string_accurate );

		if ( empty( $possible_string ) ) {
			$this->log()->add_call_expr( $call, 0, 'prefixes_code', true );
			return;
		}

		if ( ! in_array( $possible_string, $filter, true ) ) { // Ignore some strings.
			$call_class = get_class( $call );
			if ( in_array( $call_class, array( 'PhpParser\Node\Expr\FuncCall', 'PhpParser\Node\Expr\MethodCall' ), true ) ) {
				$this->log()->add_call_expr( $call, $arg_position, 'prefixes_code', true, $possible_string_accurate );
			} elseif ( in_array( $call_class, array( 'PhpParser\Node\Expr\ArrayDimFetch' ), true ) ) {
				$this->log()->add_var_expr( $call, 'prefixes_code', true, $possible_string_accurate );
			}
		}
	}

	/**
	 * Analyzes a log of items to check naming prefixes, identifying and normalizing
	 * common prefixes, and logging any potential issues or observations regarding
	 * prefix usage.
	 *
	 * @since 1.7.0
	 *
	 * @param string $logid The identifier of the log to analyze.
	 * @return void
	 */
	private function check_prefixes( string $logid ) {
		$log = $this->log()->get( $logid );

		// Removes exceptions and logs items whose prefix cannot be read.
		$log = $this->check_prefixes_process_exceptions_from_log( $log );

		// Lowercase all the names.
		$log = $this->lowercase_item_names( $log );

		$this->possible_prefixes       = array();
		$this->already_processed_items = array();
		$this->namespaces_count        = 0;

		// Check anything having a name. Extract possible prefixes.
		foreach ( $log as $key => $log_item ) {
			$item_identifier = $log_item['name'] . ':' . $log_item['type'];
			if ( isset( $log_item['name'] ) && ! in_array( $item_identifier, $this->already_processed_items, true ) ) {
				$this->already_processed_items[] = $item_identifier;

				$should_check_other_prefixes = $this->check_prefixes_add_special_prefixes( $log_item, $key );

				if ( $should_check_other_prefixes ) {
					$this->check_prefixes_add_possible_prefixes( $log_item, $key, '_' );
					$this->check_prefixes_add_possible_prefixes( $log_item, $key, '-' );
					$this->check_prefixes_add_possible_prefixes( $log_item, $key, '\\' );
				}
			}
		}

		$this->already_processed_items = array_unique( $this->already_processed_items );

		// Repetitions of each possible prefix.
		if ( ! empty( $this->possible_prefixes ) ) {
			$logsize = count( $log ) - ( count( $log ) - count( $this->already_processed_items ) ) - $this->namespaces_count;

			$return                                  = $this->normalize_prefixes_array( $this->possible_prefixes, $log );
			$common_prefixes                         = $return['commonprefixes'];
			$minimum_quantity_for_considering_prefix = $this->calculate_minimum_prefix_quantity( $logsize );

			// Checking for prefixed used commonly.
			if ( ! empty( $common_prefixes ) ) {
				// In some situations they use similar prefixes like "PRTPR" and "PRT_PR" we process those cases together as they were a similar prefix regarding the prefixes_analysis_prefixed log.
				$similar_prefixes           = $this->get_too_similar_prefixes_array( $common_prefixes );
				$prefixes_already_processed = array();
				foreach ( $common_prefixes as $prefix => $incidences ) {
					$incidences_count = count( $incidences );
					if ( ! empty( $similar_prefixes[ $prefix ] ) ) {
						$incidences_count = $similar_prefixes[ $prefix ]['numberincidences'];
					}
					if ( $incidences_count >= $minimum_quantity_for_considering_prefix || reset( $incidences ) === 'namespace' ) {
						if ( ! in_array( $prefix, $prefixes_already_processed, true ) ) {
							if ( ! empty( $similar_prefixes[ $prefix ] ) ) {
								$this->final_prefixes = array_merge( $this->final_prefixes, $similar_prefixes[ $prefix ]['prefixes'] );
							} else {
								$this->final_prefixes[] = $prefix;
							}
						}
					}
					if ( ! empty( $similar_prefixes[ $prefix ] ) ) {
						$prefixes_already_processed = array_merge( $prefixes_already_processed, $similar_prefixes[ $prefix ]['prefixes'] );
					} else {
						$prefixes_already_processed[] = $prefix;
					}
				}
			}

			// Discard prefixes that are less than 4 characters long.
			$this->final_prefixes = array_filter(
				$this->final_prefixes,
				function ( $item ) {
					return strlen( $item ) >= 4;
				}
			);

			// Discard not valid prefixes.
			$this->final_prefixes = array_diff( $this->final_prefixes, $this->not_valid_prefixes );
		}
	}

	/**
	 * Checks if the given log item's name has a specific special prefix and updates the possible prefixes list accordingly.
	 *
	 * @since 1.7.0
	 *
	 * @param array $log_item The log item array containing information such as 'name' and 'type'.
	 * @param mixed $key      The key used to associate the log item's type with the special prefix in the internal structure.
	 * @return bool Returns false if a matching special prefix is found and processed, otherwise true.
	 */
	private function check_prefixes_add_special_prefixes( array $log_item, $key ) {
		$special_prefixes = array( '__', '_', '-' );

		foreach ( $special_prefixes as $special_prefix ) {
			if ( str_starts_with( $log_item['name'], $special_prefix ) &&
				( 'abstraction' === $log_item['type'] || $special_prefix === $log_item['name'] )
			) {
				$this->possible_prefixes[ $special_prefix ][ $key ] = $log_item['type'];
				return false;
			}
		}

		return true;
	}

	/**
	 * Processes and filters a log array to detect and handle items without names,
	 * and exceptions based on specific conditions. Alters the $log array by
	 * removing items that meet exclusion criteria while maintaining a record
	 * of unidentified prefix items.
	 *
	 * @since 1.7.0
	 *
	 * @param array $log The log array to process, containing items with keys such as 'name', 'type', and 'text'.
	 * @return array The filtered log array after processing and exclusion of specific items.
	 */
	private function check_prefixes_process_exceptions_from_log( array $log ) {
		$log_without_name = array();
		foreach ( $log as $key => $item ) {
			// Log anything that doesn't has name.
			// No name or is a function defined by a variable.
			if ( ! isset( $item['name'] ) || 'function_call-PhpParser\Node\Expr\Variable' === $item['type'] || false === $item['name'] ) {
				$log_without_name[ $key ] = $item;
				unset( $log[ $key ] );
			}

			// Exceptions.
			if ( ! empty( $item['name'] ) && 'abstraction' === $item['type'] && str_starts_with( $item['text'], 'class ' ) ) {
				if ( preg_match( '/^wc_gateway_/i', $item['name'] ) ) {
					unset( $log[ $key ] ); // Ignoring classes that begins with WC_Gateway as that's recommended in the WC documentation.
				}
			}
		}

		if ( ! empty( $log_without_name ) ) {
			$this->log()->add( '0', '# ██ ⚠ There are some elements we are not able to detect their prefixes.', 'prefixes_analysis_noname' );
			$this->log()->merge( $log_without_name, 'prefixes_analysis_noname' );
		}

		return $log;
	}

	/**
	 * Converts the 'name' value of each item in the provided array to lowercase if it exists.
	 *
	 * @since 1.7.0
	 *
	 * @param array $log An array of items, where each item may contain a 'name' key.
	 * @return array The modified array with the 'name' values converted to lowercase.
	 */
	private function lowercase_item_names( array $log ) {
		foreach ( $log as $key => $item ) {
			if ( isset( $item['name'] ) ) {
				$log[ $key ]['name'] = strtolower( $item['name'] );
			}
		}
		return $log;
	}

	/**
	 * Determines the minimum prefix quantity based on the provided log count,
	 * using varying thresholds and calculations depending on the value of the input.
	 *
	 * @since 1.7.0
	 *
	 * @param int $log_count The total number of logs for which the minimum prefix quantity is to be calculated.
	 * @return int The calculated minimum prefix quantity.
	 */
	private function calculate_minimum_prefix_quantity( int $log_count ) {
		if ( $log_count <= 2 ) {
			return 1;
		} elseif ( $log_count <= 4 ) {
			return 2;
		} elseif ( $log_count <= 8 ) {
			return 3;
		} elseif ( $log_count < 20 ) {
			return intval( $log_count / 4 );
		}
		return (int) ( log( $log_count ) * 2 );
	}

	/**
	 * Processes the input item to identify potential prefixes and add them to the list of possible prefixes.
	 * Handles namespace counting, formats complete names, splits prefixes using a specified separator,
	 * and checks for multi-part prefixes.
	 *
	 * @since 1.7.0
	 *
	 * @param array  $item Array containing details, including 'name' and 'type', used to process and identify prefixes.
	 * @param string $key Key associated with the prefix being processed.
	 * @param string $separator Optional separator used to split the item name for extracting prefixes. Default is '_'.
	 * @return void
	 */
	private function check_prefixes_add_possible_prefixes( $item, $key, $separator = '_' ) {
		$complete_name_formatted = str_replace( array( '-', '\\' ), '_', $item['name'] );

		if ( 'namespace' === $item['type'] ) {
			++$this->namespaces_count;
		}

		$this->possible_prefixes[ $complete_name_formatted ][ $key ] = $item['type'];

		$extract_prefixes = explode( $separator, $complete_name_formatted );

		// Remove special characters at the beginning.
		if ( empty( $extract_prefixes[0] ) ) {
			array_shift( $extract_prefixes );
		}

		$prefix_parts_count = count( $extract_prefixes );

		if ( $prefix_parts_count > 1 ) {
			$this->check_prefixes_add_possible_prefix( $item, $key, $extract_prefixes[0] );
			if ( $prefix_parts_count > 2 ) {
				$this->check_prefixes_add_possible_prefix( $item, $key, $extract_prefixes[0] . '_' . $extract_prefixes[1] );
			}
		}
	}

	/**
	 * Updates the possible prefixes by adding the type of the provided item
	 * under the specified prefix and key.
	 *
	 * @since 1.7.0
	 *
	 * @param array  $item   The item containing data, including its type, to be added.
	 * @param string $key    The key under which the item type is stored for the prefix.
	 * @param string $prefix The prefix to which the item type is associated.
	 * @return void
	 */
	private function check_prefixes_add_possible_prefix( $item, $key, $prefix ) {
		$this->possible_prefixes[ $prefix ][ $key ] = $item['type'];
	}

	/**
	 * Normalizes the given array of common prefixes by performing several tasks including:
	 * filtering invalid or warning prefixes, removing redundant incidences,
	 * ordering prefixes by length, and removing prefixes that are contained within others.
	 *
	 * @since 1.7.0
	 *
	 * @param array $common_prefixes An associative array where keys are prefixes and values are arrays of incidences.
	 * @param array $log A log array that tracks incidences and messages related to prefixes.
	 * @return array An associative array containing the updated 'commonprefixes' and 'log' after processing.
	 */
	private function normalize_prefixes_array( $common_prefixes, $log ) {
		if ( ! empty( $common_prefixes ) ) {

			// Remove incidences for just one element, unless it's a namespace, or there is just really one element.
			if ( sizeof( $log ) > 1 ) {
				foreach ( $common_prefixes as $prefix => $array ) {
					if ( sizeof( $array ) <= 1 ) {
						if ( reset( $array ) !== 'namespace' ) {
							unset( $common_prefixes[ $prefix ] );
						}
					}
				}
			}

			// Order by bigger prefixes.
			$keys = array_map( 'strlen', array_keys( $common_prefixes ) );
			array_multisort( $keys, SORT_DESC, $common_prefixes );

			// Remove prefixes that are inside other prefixes.
			foreach ( $common_prefixes as $prefix => $incidences ) {
				foreach ( $common_prefixes as $prefix_2 => $incidences_2 ) {
					if ( $prefix !== $prefix_2 ) {
						if ( str_starts_with( $prefix, $prefix_2 ) ) { // If $prefix starts with $prefix2.
							// Remove short prefixes incidences that are within longer prefixes.
							$within = ! array_diff_assoc( $incidences_2, $incidences );
							if ( $within ) {
								unset( $common_prefixes[ $prefix_2 ] );
							} else {
								// Remove longer prefixes incidences that are within shorter prefixes.
								$within = ! array_diff_assoc( $incidences, $incidences_2 );
								if ( $within ) {
									unset( $common_prefixes[ $prefix ] );
								}
							}
						}
					}
				}
			}
		}

		return array(
			'commonprefixes' => $common_prefixes,
			'log'            => $log,
		);
	}

	/**
	 * Identifies and groups prefixes from the provided list that are too similar
	 * based on normalization criteria. It returns an associative array containing
	 * the group of similar prefixes along with their total incidences.
	 *
	 * @since 1.7.0
	 *
	 * @param array $common_prefixes An associative array where keys are prefixes and values are arrays of incidences for each prefix.
	 * @return array An associative array where each key is a prefix and the corresponding value is an array containing similar prefixes and the total number of incidences.
	 */
	private function get_too_similar_prefixes_array( $common_prefixes ) {
		$similar_prefixes = array();
		if ( ! empty( $common_prefixes ) ) {
			foreach ( $common_prefixes as $prefix => $incidences ) {
				foreach ( $common_prefixes as $prefix_search => $incidences_search ) {
					if ( $prefix !== $prefix_search && $this->get_prefix_name_normalized( $prefix ) === $this->get_prefix_name_normalized( $prefix_search ) ) {
						if ( empty( $similar_prefixes[ $prefix ] ) ) {
							$similar_prefixes[ $prefix ] = array(
								'prefixes'         => array( $prefix ),
								'numberincidences' => count( $incidences ),
							);
						}
						$similar_prefixes[ $prefix ]['prefixes'][]        = $prefix_search;
						$similar_prefixes[ $prefix ]['numberincidences'] += count( $incidences_search );
					}
				}
			}
		}
		return $similar_prefixes;
	}

	/**
	 * Normalizes a given prefix by removing underscores and hyphens.
	 *
	 * @since 1.7.0
	 *
	 * @param string $prefix The original prefix to be normalized.
	 * @return string The normalized prefix.
	 */
	private function get_prefix_name_normalized( $prefix ) {
		$normalized = preg_replace( '/[\-_]/', '', $prefix );

		return empty( $normalized ) ? $prefix : $normalized;
	}
}
