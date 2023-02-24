<?php
/**
 * Class WordPress\Plugin_Check\CLI\Plugin_Check_Command
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\CLI;

use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Plugin_Context;
use Exception;
use WP_CLI\ExitException;

/**
 * Plugin check command.
 */
class Plugin_Check_Command {

	/**
	 * Plugin context.
	 *
	 * @var Plugin_Context
	 */
	protected $plugin_context;

	/**
	 * Output format type.
	 *
	 * @var string[]
	 */
	protected $output_formats = array(
		'table',
		'csv',
		'json',
	);

	/**
	 * Check flags.
	 *
	 * @var string[]
	 */
	protected $check_flags = array(
		'stable',
		'beta',
		'experimental',
	);

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param Plugin_Context $plugin_context Plugin context.
	 */
	public function __construct( $plugin_context ) {
		$this->plugin_context = $plugin_context;
	}

	/**
	 * Run plugin check.
	 *
	 * ## OPTIONS
	 *
	 * [<plugin>]
	 * : The plugin to check. Plugin name.
	 *
	 * [--checks]
	 * : Only runs checks provided as an argument in comma-separated values, e.g. enqueued-scripts, escaping. Otherwise runs all checks.
	 *
	 * [--flag]
	 * : Limit the checks being executed according to their flags, e.g. stable, beta or experimental. Default is stable.
	 * ---
	 * default: stable
	 * options:
	 *   - stable
	 *   - beta
	 *   - experimental
	 * ---
	 *
	 * [--format]
	 * : Format to display the results. Options are table, csv, and json. The default will be a table.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 * ---
	 *
	 * [--fields]
	 * : Limit displayed results to a subset of fields provided.
	 *
	 * [--ignore-warnings]
	 * : Limit displayed results to exclude warnings.
	 *
	 * [--ignore-errors]
	 * : Limit displayed results to exclude errors.
	 *
	 *
	 * ## EXAMPLES
	 *
	 *   wp plugin check akismet
	 *   wp plugin check akismet --check=escaping
	 *   wp plugin check akismet --format=json
	 *
	 * @subcommand check
	 *
	 * @since n.e.x.t
	 *
	 * @param array $args List of the positional arguments.
	 * @param array $assoc_args List of the associative arguments.
	 *
	 * @throws Exception Throws exception.
	 */
	public function check( $args, $assoc_args ) {

		$assoc_args = $this->get_options( $assoc_args );

		$this->get_plugin_from_args( $args );

		$cli_runner = new CLI_Runner();

		try {

			$result = $cli_runner->run();

		} catch ( Exception $error ) {

			\WP_CLI::error( $error->getMessage() );
		}

		// Get errors and warnings from the results.
		$errors = array();
		if ( empty( $assoc_args['ignore-errors'] ) ) {
			$errors = $result->get_errors();
		}
		$warnings = array();
		if ( empty( $assoc_args['ignore-warnings'] ) ) {
			$warnings = $result->get_warnings();
		}

		// Get formatter.
		$formatter = $this->get_formatter( $assoc_args );

		// Print the formatted results.
		// Go over all files with errors first and print them, combined with any warnings in the same file.
		foreach ( $errors as $file_name => $file_errors ) {
			$file_warnings = array();
			if ( isset( $warnings[ $file_name ] ) ) {
				$file_warnings = $warnings[ $file_name ];
				unset( $warnings[ $file_name ] );
			}
			$file_results = $this->flatten_file_results( $file_errors, $file_warnings );
			$this->display_results( $formatter, $file_name, $file_results );
		}

		// If there are any files left with only warnings, print those next.
		foreach ( $warnings as $file_name => $file_warnings ) {
			$file_results = $this->flatten_file_results( array(), $file_warnings );
			$this->display_results( $formatter, $file_name, $file_results );
		}
	}

	/**
	 * Get plugin main file.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $args List of the positional arguments.
	 * @return string Relative path of the plugin main file.
	 *
	 * @throws \WP_CLI\ExitException Show error if plugin not found.
	 */
	protected function get_plugin_from_args( $args ) {
		$plugin_slug = $args[0];

		$available_plugins = $this->get_all_plugins();

		$plugin_base_file = '';

		if ( ! empty( $available_plugins ) ) {
			foreach ( $available_plugins as $available_plugin_base_file => $available_plugin ) {
				if ( $this->get_plugin_name( $available_plugin_base_file ) === $plugin_slug ) {

					$plugin_base_file = $available_plugin_base_file;

					break;
				}
			}
		}

		if ( empty( $plugin_base_file ) ) {

			\WP_CLI::error(
				sprintf(
				/* translators: 1: plugin basename */
					__( '"%1$s" plugin not exists.', 'plugin-check' ),
					$plugin_slug
				)
			);
		}

		$plugin_valid = validate_plugin( $plugin_base_file );

		if ( is_wp_error( $plugin_valid ) ) {

			\WP_CLI::error(
				sprintf(
				/* translators: 1: plugin basename, 2: error message */
					__( 'Invalid plugin "%1$s": %2$s', 'plugin-check' ),
					$plugin_slug,
					$plugin_valid->get_error_message()
				)
			);
		}

		return $plugin_base_file;
	}

	/**
	 * Validate associative arguments.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $assoc_args List of the associative arguments.
	 * @return array List of the associative arguments.
	 *
	 * @throws \WP_CLI\ExitException Show error if plugin not found.
	 */
	protected function get_options( $assoc_args ) {

		$options = array(
			'checks'          => 'all',
			'flag'            => 'stable',
			'format'          => 'table',
			'ignore-warnings' => false,
			'ignore-errors'   => false,
		);
		$options = wp_parse_args( $assoc_args, $options );

		if ( ! in_array( $options['flag'], $this->check_flags, true ) ) {

			\WP_CLI::error(
				sprintf(
					// translators: 1. Check flags.
					__( 'Invalid flag argument, valid value will be one of [%1$s]', 'plugin-check' ),
					implode( ', ', $this->check_flags )
				)
			);
		}

		if ( ! in_array( $options['format'], $this->output_formats, true ) ) {

			\WP_CLI::error(
				sprintf(
					// translators: 1. Output formats.
					__( 'Invalid format argument, valid value will be one of [%1$s]', 'plugin-check' ),
					implode( ', ', $this->output_formats )
				)
			);
		}

		return $options;
	}

	/**
	 * Gets the formatter instance to format check results.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return \WP_CLI\Formatter The formatter instance.
	 */
	protected function get_formatter( $assoc_args ) {

		$default_fields = array(
			'line',
			'column',
			'code',
			'message',
		);
		$default_fields = wp_parse_args( $assoc_args['fields'], $default_fields );

		// If both errors and warnings are included, display the type of each result too.
		if ( empty( $assoc_args['ignore_errors'] ) && empty( $assoc_args['ignore_warnings'] ) ) {
			$default_fields = array(
				'line',
				'column',
				'type',
				'code',
				'message',
			);
		}

		return new \WP_CLI\Formatter(
			$assoc_args,
			$default_fields
		);
	}

	/**
	 * Flattens and combines the given associative array of file errors and file warnings into a two-dimensional array.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $file_errors   Errors from a Check_Result, for a specific file.
	 * @param array $file_warnings Warnings from a Check_Result, for a specific file.
	 * @return array Combined file results.
	 */
	protected function flatten_file_results( $file_errors, $file_warnings ) {
		$file_results = array();

		foreach ( $file_errors as $line => $line_errors ) {
			foreach ( $line_errors as $column => $column_errors ) {
				foreach ( $column_errors as $column_error ) {

					$file_results[] = array_merge(
						$column_error,
						array(
							'type'   => 'ERROR',
							'line'   => $line,
							'column' => $column,
						)
					);
				}
			}
		}

		foreach ( $file_warnings as $line => $line_warnings ) {
			foreach ( $line_warnings as $column => $column_warnings ) {
				foreach ( $column_warnings as $column_warning ) {

					$file_results[] = array_merge(
						$column_warning,
						array(
							'type'   => 'WARNING',
							'line'   => $line,
							'column' => $column,
						)
					);
				}
			}
		}

		usort(
			$file_results,
			function( $a, $b ) {
				if ( $a['line'] < $b['line'] ) {
					return -1;
				}
				if ( $a['line'] > $b['line'] ) {
					return 1;
				}
				if ( $a['column'] < $b['column'] ) {
					return -1;
				}
				if ( $a['column'] > $b['column'] ) {
					return 1;
				}
				return 0;
			}
		);

		return $file_results;
	}

	/**
	 * Display results.
	 *
	 * @since n.e.x.t
	 *
	 * @param \WP_CLI\Formatter $formatter    Formatter class.
	 * @param string            $file_name    File name.
	 * @param array             $file_results Results.
	 */
	protected function display_results( $formatter, $file_name, $file_results ) {
		\WP_CLI::line(
			sprintf(
				'FILE: %s',
				$file_name
			)
		);

		$formatter->display_items( $file_results );

		\WP_CLI::line();
		\WP_CLI::line();
	}

	/**
	 * Converts a plugin basename back into a friendly slug.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $basename Plugin basename.
	 * @return string Plugin slug.
	 */
	public function get_plugin_name( $basename ) {
		if ( false === strpos( $basename, '/' ) ) {
			$name = basename( $basename, '.php' );
		} else {
			$name = dirname( $basename );
		}

		return $name;
	}

	/**
	 * Gets all available plugins.
	 *
	 * @since n.e.x.t
	 *
	 * Uses the same filter core uses in plugins.php to determine which plugins
	 * should be available to manage through the WP_Plugins_List_Table class.
	 *
	 * @return array
	 */
	private function get_all_plugins() {

		return apply_filters( 'all_plugins', get_plugins() );
	}
}
