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
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
	 * AI analysis results for false positives.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	protected $ai_analysis = array();

	/**
	 * AI statistics (tokens spent, false positives count, etc.).
	 *
	 * @since 2.0.0
	 * @var array
	 */
	protected $ai_stats = array();

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
			'code'     => '',
			'file'     => '',
			'line'     => 0,
			'column'   => 0,
			'link'     => '',
			'docs'     => '',
			'severity' => 5,
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
	 * Transforms existing messages.
	 *
	 * The callback receives the message data and location. Return an array with
	 * updated data to keep the message, or false/null to remove it. The returned
	 * array may include `error`, `file`, `line`, or `column` to move the message.
	 *
	 * @since 2.0.0
	 *
	 * @param callable $callback Callback to transform each message.
	 */
	public function transform_messages( callable $callback ) {
		$collections = array(
			true  => $this->errors,
			false => $this->warnings,
		);

		$this->errors        = array();
		$this->warnings      = array();
		$this->error_count   = 0;
		$this->warning_count = 0;

		foreach ( $collections as $is_error => $collection ) {
			foreach ( $collection as $file => $lines ) {
				foreach ( $lines as $line => $columns ) {
					foreach ( $columns as $column => $messages ) {
						foreach ( $messages as $message ) {
							$updated = $callback( $message, (bool) $is_error, $file, $line, $column );
							if ( empty( $updated ) || ! is_array( $updated ) ) {
								continue;
							}

							if ( empty( $updated['message'] ) ) {
								continue;
							}

							$new_error  = array_key_exists( 'error', $updated ) ? (bool) $updated['error'] : (bool) $is_error;
							$new_file   = array_key_exists( 'file', $updated ) ? (string) $updated['file'] : (string) $file;
							$new_line   = array_key_exists( 'line', $updated ) ? (int) $updated['line'] : (int) $line;
							$new_column = array_key_exists( 'column', $updated ) ? (int) $updated['column'] : (int) $column;

							unset( $updated['error'], $updated['file'], $updated['line'], $updated['column'] );
							$updated['file']   = $new_file;
							$updated['line']   = $new_line;
							$updated['column'] = $new_column;

							$this->add_message( $new_error, $updated['message'], $updated );
						}
					}
				}
			}
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

	/**
	 * Sets AI analysis results.
	 *
	 * @since 2.0.0
	 *
	 * @param array $analysis AI analysis results.
	 */
	public function set_ai_analysis( array $analysis ) {
		$this->ai_analysis = $analysis;
	}

	/**
	 * Returns AI analysis results.
	 *
	 * @since 2.0.0
	 *
	 * @return array AI analysis results.
	 */
	public function get_ai_analysis() {
		return $this->ai_analysis;
	}

	/**
	 * Sets AI statistics.
	 *
	 * @since 2.0.0
	 *
	 * @param array $stats AI statistics.
	 */
	public function set_ai_stats( array $stats ) {
		$this->ai_stats = $stats;
	}

	/**
	 * Returns AI statistics.
	 *
	 * @since 2.0.0
	 *
	 * @return array AI statistics.
	 */
	public function get_ai_stats() {
		return $this->ai_stats;
	}
}
