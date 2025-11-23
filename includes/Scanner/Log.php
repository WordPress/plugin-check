<?php
/**
 * Class WordPress\Plugin_Check\Scanner\Log
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Scanner;

/**
 * Log class.
 *
 * @since 1.7.0
 */
class Log {

	/**
	 * An array of logs.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $log = array();

	/**
	 * An array of logs with longer location.
	 *
	 * @since 1.7.0
	 * @var array
	 */
	private array $log_longer_location = array();

	/**
	 * An instance of the PHP_Parser class.
	 *
	 * @since 1.7.0
	 * @var PHP_Parser
	 */
	public PHP_Parser $parser_object;

	/**
	 * An array of strings to be removed from the log.
	 *
	 * @since 1.7.0
	 * @var string[]
	 */
	private array $remove_strings_from_log = array();

	/**
	 * Constructor.
	 *
	 * @since 1.7.0
	 *
	 * @param PHP_Parser $parser An instance of the PHP_Parser class.
	 */
	public function __construct( $parser ) {
		$this->parser_object = $parser;
	}

	/**
	 * Adds a log entry with the specified details to the log storage.
	 *
	 * @since 1.7.0
	 *
	 * @param int    $line_number The line number where the log entry originates from.
	 * @param string $text        The text content of the log entry.
	 * @param string $logid       Optional. The identifier for the log group. Defaults to 'default'.
	 * @param bool   $unique      Optional. Whether the log entry should be unique within the group. Defaults to false.
	 * @return void
	 */
	public function add( $line_number, $text, $logid = 'default', $unique = false ) {
		if ( ! empty( $this->remove_strings_from_log ) ) {
			foreach ( $this->remove_strings_from_log as $remove_string ) {
				$text = str_replace( $remove_string, '', $text );
			}
		}

		$log_line = array(
			'location' => '',
			'line'     => $line_number,
			'text'     => $text,
		);

		if ( ! empty( $line_number ) ) {
			$log_line['location'] = $this->get_location_string_for_current_file_and_line( $line_number, $this->parser_object->file_relative );
		}

		if ( ! isset( $this->log_longer_location[ $logid ] ) ) {
			$this->log_longer_location[ $logid ] = 0;
		}

		if ( strlen( $log_line['location'] ) > $this->log_longer_location[ $logid ] ) {
			$this->log_longer_location[ $logid ] = strlen( $log_line['location'] );
		}

		if ( $unique ) {
			$line_id = $this->get_line_id( $line_number );

			$this->log[ $logid ][ $line_id ] = $log_line;
		} else {
			$this->log[ $logid ][] = $log_line;
		}
	}

	/**
	 * Generates a unique identifier for a specific line in a file.
	 *
	 * @since 1.7.0
	 *
	 * @param int $line_number The line number for which the identifier is generated.
	 * @return string The MD5 hash representing the unique identifier for the line.
	 */
	public function get_line_id( $line_number ) {
		return md5( $this->parser_object->file_relative . '_' . $line_number );
	}

	/**
	 * Retrieves the log data associated with the provided log identifier.
	 *
	 * @param string $logid The identifier of the log to retrieve. Defaults to 'default'.
	 *
	 * @return array The log data associated with the provided identifier, or an empty array if no data exists.
	 */
	public function get( $logid = 'default' ) {
		return $this->log[ $logid ] ?? array();
	}

	/**
	 * Merges the provided log data with the existing log data for the specified identifier.
	 *
	 * @since 1.7.0
	 *
	 * @param array  $log   The log data to merge.
	 * @param string $logid The identifier of the log to merge into. Defaults to 'default'.
	 * @return void
	 */
	public function merge( $log, $logid = 'default' ) {
		if ( empty( $this->log[ $logid ] ) ) {
			$this->log[ $logid ] = array();
		}

		$this->log[ $logid ] = array_merge( $this->log[ $logid ], $log );
	}

	/**
	 * Adds a namespace to the log for a given identifier.
	 *
	 * @since 1.7.0
	 *
	 * @param object $namespace_obj The namespace object to add, which should include a name property.
	 * @param string $logid         The identifier of the log where the namespace information should be added.
	 * @param bool   $unique        Optional. Determines whether the log entry should be unique. Defaults to false.
	 * @return void
	 */
	public function add_namespace( $namespace_obj, $logid, $unique = false ) {
		if ( ! empty( $namespace_obj->name ) ) {
			$line_text = 'namespace ' . $namespace_obj->name->toCodeString();

			$line_number = method_exists( $namespace_obj, 'getStartLine' ) ? $namespace_obj->getStartLine() : 0;
			$this->add( $line_number, $line_text, $logid, $unique );

			$log_key = array_key_last( $this->log[ $logid ] );

			$this->log[ $logid ][ $log_key ]['type'] = 'namespace';
			$this->log[ $logid ][ $log_key ]['name'] = $namespace_obj->name->__toString();
		}
	}

	/**
	 * Adds a function call expression to the log and assigns metadata for processing.
	 *
	 * @since 1.7.0
	 *
	 * @param object $func_call   The function call expression object to be logged.
	 * @param int    $argposition The position of the argument in the function call to extract metadata from.
	 * @param string $logid       The identifier for the log where the function call should be added.
	 * @param bool   $unique      Optional. Whether the function call entry should be unique in the log. Defaults to false.
	 * @param bool   $accurate    Optional. Whether to enable accurate parsing when extracting the argument's metadata. Defaults to true.
	 * @return void
	 */
	public function add_call_expr( $func_call, $argposition, $logid, $unique = false, $accurate = true ) {
		$func_call->setAttribute( 'comments', null );

		$line_number = method_exists( $func_call, 'getStartLine' ) ? $func_call->getStartLine() : 0;
		$this->add( $line_number, $this->parser_object->pretty_printer->prettyPrint( array( $func_call ) ) . ';', $logid, $unique );

		$log_key   = array_key_last( $this->log[ $logid ] );
		$func_name = $this->parser_object->get_call_name( $func_call );

		$this->log[ $logid ][ $log_key ]['type'] = 'function_call-' . $func_name;

		if ( isset( $func_call->args[ $argposition ] ) ) {
			$arg = $func_call->args[ $argposition ];

			$found_in_same_line = true; // Default.

			$this->log[ $logid ][ $log_key ]['name'] = $this->parser_object->get_possible_string_for_element( $arg, $found_in_same_line, $accurate );
		}
	}

	/**
	 * Adds a variable expression to the log and records its metadata.
	 *
	 * @since 1.7.0
	 *
	 * @param object $var_call The variable expression object to be logged. It is expected to be an instance of specific PhpParser classes.
	 * @param string $logid    The identifier of the log where the variable expression should be added.
	 * @param bool   $unique   Optional. Whether the log entry should be unique. Defaults to false.
	 * @param bool   $accurate Optional. Determines if processing should consider accuracy when extracting the variable name. Defaults to true.
	 * @return void
	 */
	public function add_var_expr( $var_call, $logid, $unique = false, $accurate = true ) {
		$var_call->setAttribute( 'comments', null );

		$line_number = method_exists( $var_call, 'getStartLine' ) ? $var_call->getStartLine() : 0;
		$this->add( $line_number, $this->parser_object->pretty_printer->prettyPrint( array( $var_call ) ) . ';', $logid, $unique );

		$log_key  = array_key_last( $this->log[ $logid ] );
		$var_name = '';

		if ( is_a( $var_call, 'PhpParser\Node\Expr\ArrayDimFetch' ) ) {
			$dims = $this->parser_object->extract_dims_objects( $var_call );

			if ( isset( $dims[0] ) ) {
				$var_name = $this->parser_object->get_possible_string_for_element( $dims[0], $found_in_same_line, $accurate );
			}
		} elseif ( is_a( $var_call, 'PhpParser\Node\Const_' ) ) {
			$var_name = $var_call->name->__toString();
		} else {
			$var_name = $var_call->name;
		}

		$this->log[ $logid ][ $log_key ]['type'] = 'variable';
		$this->log[ $logid ][ $log_key ]['name'] = $var_name;
	}

	/**
	 * Adds abstraction declarations like classes, functions, interfaces, or traits to the log with specific context and type.
	 *
	 * @since 1.7.0
	 *
	 * @param array  $abstractions An array of abstraction objects to process and add to the log. Each object must have a type and name.
	 * @param string $logid The identifier for the log to which the abstraction declarations will be added.
	 * @param bool   $unique Whether to ensure unique entries in the log. Defaults to false.
	 * @return void
	 */
	public function add_abstraction_declarations( $abstractions, $logid, $unique = false ) {
		if ( ! empty( $abstractions ) ) {
			foreach ( $abstractions as $abstract ) {
				$continue = true;
				$type     = 'unknown';

				switch ( $abstract->getType() ) {
					case 'Stmt_Class':
						$type = 'class';
						break;
					case 'Stmt_Function':
						$contextual_stmts = $this->parser_object->get_contextual_stmts_for_element( $abstract )['context'];
						if ( $contextual_stmts !== $abstract->stmts && ! empty( $abstract->stmts ) ) {
							$continue = false; // Function declared inside another function.
						}
						$type = 'function';
						break;
					case 'Stmt_Interface':
						$type = 'interface';
						break;
					case 'Stmt_Trait':
						$type = 'trait';
						break;
				}

				if ( empty( $abstract->name ) ) { // Ignore anonymous declarations.
					$continue = false;
				}

				if ( $continue ) { // Ignore anonymous declarations.
					$line_text = $type . ' ' . $abstract->name->toString();

					$line_number = method_exists( $abstract, 'getStartLine' ) ? $abstract->getStartLine() : 0;
					$this->add( $line_number, $line_text, $logid, $unique );

					$log_key = array_key_last( $this->log[ $logid ] );

					$this->log[ $logid ][ $log_key ]['type'] = 'abstraction';
					$this->log[ $logid ][ $log_key ]['name'] = $abstract->name->__toString();
				}
			}
		}
	}

	/**
	 * Checks whether log data exists for the specified log identifier.
	 *
	 * @since 1.7.0
	 *
	 * @param string $logid The identifier of the log to check for existence. Defaults to 'default'.
	 * @return bool True if log data exists for the provided identifier, otherwise false.
	 */
	public function exists( $logid = 'default' ) {
		return ! empty( $this->log[ $logid ] );
	}

	/**
	 * Constructs a location string consisting of the relative file path and line number.
	 *
	 * @since 1.7.0
	 *
	 * @param int    $line_number   The line number in the file.
	 * @param string $file_relative The file path relative to the root directory.
	 * @return string The formatted location string in the format "file_path:line_number ".
	 */
	public function get_location_string_for_current_file_and_line( $line_number, $file_relative ) {
		$parts = explode( '/', $file_relative, 2 );

		if ( ! empty( $parts[1] ) ) {
			$file_relative = $parts[1];
		}

		return $file_relative . ':' . $line_number . ' ';
	}
}
