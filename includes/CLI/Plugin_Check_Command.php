<?php
/**
 * Class WordPress\Plugin_Check\CLI\Plugin_Check_Command
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\CLI;

use Exception;
use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Plugin_Context;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
use WP_CLI;

/**
 * Plugin check command.
 */
final class Plugin_Check_Command {

	/**
	 * Plugin context.
	 *
	 * @since n.e.x.t
	 * @var Plugin_Context
	 */
	protected $plugin_context;

	/**
	 * Output format type.
	 *
	 * @since n.e.x.t
	 * @var string[]
	 */
	protected $output_formats = array(
		'table',
		'csv',
		'json',
	);

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param Plugin_Context $plugin_context Plugin context.
	 */
	public function __construct( Plugin_Context $plugin_context ) {
		$this->plugin_context = $plugin_context;
	}

	/**
	 * Runs plugin check.
	 *
	 * ## OPTIONS
	 *
	 * <plugin>
	 * : The plugin to check. Plugin name.
	 *
	 * [--checks=<checks>]
	 * : Only runs checks provided as an argument in comma-separated values, e.g. enqueued-scripts, escaping. Otherwise runs all checks.
	 *
	 * [--format=<format>]
	 * : Format to display the results. Options are table, csv, and json. The default will be a table.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 * ---
	 *
	 * [--fields=<fields>]
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
	 *   wp plugin check akismet --checks=escaping
	 *   wp plugin check akismet --format=json
	 *
	 * @subcommand check
	 *
	 * @since n.e.x.t
	 *
	 * @param array $args       List of the positional arguments.
	 * @param array $assoc_args List of the associative arguments.
	 *
	 * @throws Exception Throws exception.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function check( $args, $assoc_args ) {
		// Get options based on the CLI arguments.
		$options = $this->get_options( $assoc_args );

		// Create the plugin and checks array from CLI arguments.
		$plugin = isset( $args[0] ) ? $args[0] : '';
		$checks = wp_parse_list( $options['checks'] );

		// Get the CLI Runner.
		$runner = Plugin_Request_Utility::get_runner();

		// Create the runner if not already initialized.
		if ( is_null( $runner ) ) {
			$runner = new CLI_Runner();
		}

		// Make sure we are using the correct runner instance.
		if ( ! ( $runner instanceof CLI_Runner ) ) {
			WP_CLI::error(
				__( 'CLI Runner was not initialized correctly.', 'plugin-check' )
			);
		}

		$checks_to_run = array();
		try {
			$runner->set_check_slugs( $checks );
			$runner->set_plugin( $plugin );

			$checks_to_run = $runner->get_checks_to_run();
		} catch ( Exception $error ) {
			WP_CLI::error( $error->getMessage() );
		}

		if ( $this->has_runtime_check( $checks_to_run ) ) {
			WP_CLI::line( __( 'Setting up runtime environment.', 'plugin-check' ) );
			$runtime_setup = new Runtime_Environment_Setup();
			$runtime_setup->setup();
		}

		$result = false;
		// Run checks against the plugin.
		try {
			$result = $runner->run();
		} catch ( Exception $error ) {
			Plugin_Request_Utility::destroy_runner();

			if ( isset( $runtime_setup ) ) {
				$runtime_setup->cleanup();
				WP_CLI::line( __( 'Cleaning up runtime environment.', 'plugin-check' ) );
			}

			WP_CLI::error( $error->getMessage() );
		}

		Plugin_Request_Utility::destroy_runner();

		if ( isset( $runtime_setup ) ) {
			$runtime_setup->cleanup();
			WP_CLI::line( __( 'Cleaning up runtime environment.', 'plugin-check' ) );
		}

		// Get errors and warnings from the results.
		$errors = array();
		if ( $result && empty( $assoc_args['ignore-errors'] ) ) {
			$errors = $result->get_errors();
		}
		$warnings = array();
		if ( $result && empty( $assoc_args['ignore-warnings'] ) ) {
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
	 * Validates the associative arguments.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $assoc_args List of the associative arguments.
	 * @return array List of the associative arguments.
	 *
	 * @throws WP_CLI\ExitException Show error if plugin not found.
	 */
	private function get_options( $assoc_args ) {
		$defaults = array(
			'checks'          => '',
			'format'          => 'table',
			'ignore-warnings' => false,
			'ignore-errors'   => false,
		);

		$options = wp_parse_args( $assoc_args, $defaults );

		if ( ! in_array( $options['format'], $this->output_formats, true ) ) {
			WP_CLI::error(
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
	 * @return WP_CLI\Formatter The formatter instance.
	 */
	private function get_formatter( $assoc_args ) {
		$default_fields = array(
			'line',
			'column',
			'code',
			'message',
		);

		if ( isset( $assoc_args['fields'] ) ) {
			$default_fields = wp_parse_args( $assoc_args['fields'], $default_fields );
		}

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

		return new WP_CLI\Formatter(
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
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	private function flatten_file_results( $file_errors, $file_warnings ) {
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
			static function( $a, $b ) {
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
	 * Displays the results.
	 *
	 * @since n.e.x.t
	 *
	 * @param WP_CLI\Formatter $formatter    Formatter class.
	 * @param string           $file_name    File name.
	 * @param array            $file_results Results.
	 */
	private function display_results( $formatter, $file_name, $file_results ) {
		WP_CLI::line(
			sprintf(
				'FILE: %s',
				$file_name
			)
		);

		$formatter->display_items( $file_results );

		WP_CLI::line();
		WP_CLI::line();
	}

	/**
	 * Checks for a Runtime_Check in a list of checks.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks An array of Check instances.
	 * @return bool True if a Runtime_Check exists in the array, false if not.
	 */
	private function has_runtime_check( array $checks ) {
		foreach ( $checks as $check ) {
			if ( $check instanceof Runtime_Check ) {
				return true;
			}
		}

		return false;
	}
}
