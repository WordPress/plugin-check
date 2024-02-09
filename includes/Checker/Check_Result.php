<?php
/**
 * Class WordPress\Plugin_Check\Checker\Check_Result
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Result for running checks on a plugin.
 *
 * @since 1.0.0
 */
final class Check_Result {

	/**
	 * Context for the plugin to check.
	 *
	 * @since 1.0.0
	 * @var Check_Context
	 */
	protected $check_context;

	/**
	 * List of errors.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $errors = array();

	/**
	 * List of warnings.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $warnings = array();

	/**
	 * Number of errors.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $error_count = 0;

	/**
	 * Number of warnings.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $warning_count = 0;

	/**
	 * Sets the context for the plugin to check.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Context $check_context Check context instance for the plugin.
	 */
	public function __construct( Check_Context $check_context ) {
		$this->check_context = $check_context;
	}

	/**
	 * Returns the context for the plugin to check.
	 *
	 * @since 1.0.0
	 *
	 * @return Check_Context Plugin context instance.
	 */
	public function plugin() {
		return $this->check_context;
	}

	/**
	 * Adds an error or warning to the respective stack.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $error   Whether it is an error message.
	 * @param string $message The message.
	 * @param array  $args    {
	 *     Additional message arguments.
	 *
	 *     @type string $code   Violation code according to the message. Default empty string.
	 *     @type string $file   The file in which the message occurred. Default empty string (unknown file).
	 *     @type int    $line   The line on which the message occurred. Default 0 (unknown line).
	 *     @type int    $column The column on which the message occurred. Default 0 (unknown column).
	 *     @type string $link   View in code editor link. Default empty string.
	 * }
	 */
	public function add_message( $error, $message, $args = array() ) {
		$defaults = array(
			'code'   => '',
			'file'   => '',
			'line'   => 0,
			'column' => 0,
			'link'   => '',
		);

		$data = array_merge(
			array(
				'message' => $message,
			),
			$defaults,
			array_intersect_key( $args, $defaults )
		);

		$file   = str_replace( $this->plugin()->path( '/' ), '', $data['file'] );
		$line   = $data['line'];
		$column = $data['column'];
		unset( $data['line'], $data['column'], $data['file'] );

		if ( $error ) {
			if ( ! isset( $this->errors[ $file ] ) ) {
				$this->errors[ $file ] = array();
			}
			if ( ! isset( $this->errors[ $file ][ $line ] ) ) {
				$this->errors[ $file ][ $line ] = array();
			}
			if ( ! isset( $this->errors[ $file ][ $line ][ $column ] ) ) {
				$this->errors[ $file ][ $line ][ $column ] = array();
			}
			$this->errors[ $file ][ $line ][ $column ][] = $data;
			++$this->error_count;
		} else {
			if ( ! isset( $this->warnings[ $file ] ) ) {
				$this->warnings[ $file ] = array();
			}
			if ( ! isset( $this->warnings[ $file ][ $line ] ) ) {
				$this->warnings[ $file ][ $line ] = array();
			}
			if ( ! isset( $this->warnings[ $file ][ $line ][ $column ] ) ) {
				$this->warnings[ $file ][ $line ][ $column ] = array();
			}
			$this->warnings[ $file ][ $line ][ $column ][] = $data;
			++$this->warning_count;
		}
	}

	/**
	 * Returns all errors.
	 *
	 * @since 1.0.0
	 *
	 * @return array All errors with their data.
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Returns all warnings.
	 *
	 * @since 1.0.0
	 *
	 * @return array All warnings with their data.
	 */
	public function get_warnings() {
		return $this->warnings;
	}

	/**
	 * Returns the number of errors.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of errors found.
	 */
	public function get_error_count() {
		return $this->error_count;
	}

	/**
	 * Returns the number of warnings.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of warnings found.
	 */
	public function get_warning_count() {
		return $this->warning_count;
	}
}
