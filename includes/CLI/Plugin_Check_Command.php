<?php
/**
 * Class WordPress\Plugin_Check\CLI\Plugin_Check_Command
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\CLI;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Repository;
use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Checker\Default_Check_Repository;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Plugin_Context;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
use WP_CLI;
use function WP_CLI\Utils\get_flag_value;

/**
 * Plugin check command.
 */
final class Plugin_Check_Command {

	/**
	 * Plugin context.
	 *
	 * @since 1.0.0
	 * @var Plugin_Context
	 */
	protected $plugin_context;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
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
	 * : Only runs checks provided as an argument in comma-separated values, e.g. i18n_usage, late_escaping. Otherwise runs all checks.
	 *
	 * [--exclude-checks=<checks>]
	 * : Exclude checks provided as an argument in comma-separated values, e.g. i18n_usage, late_escaping.
	 * Applies after evaluating `--checks`.
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
	 * [--categories]
	 * : Limit displayed results to include only specific categories Checks.
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
	 * [--include-experimental]
	 * : Include experimental checks.
	 *
	 * [--exclude-directories=<directories>]
	 * : Additional directories to exclude from checks.
	 * By default, `.git`, `vendor` and `node_modules` directories are excluded.
	 *
	 * [--exclude-files=<files>]
	 * : Additional files to exclude from checks.
	 *
	 * ## EXAMPLES
	 *
	 *   wp plugin check akismet
	 *   wp plugin check akismet --checks=late_escaping
	 *   wp plugin check akismet --format=json
	 *
	 * @subcommand check
	 *
	 * @since 1.0.0
	 *
	 * @param array $args       List of the positional arguments.
	 * @param array $assoc_args List of the associative arguments.
	 *
	 * @throws Exception Throws exception.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function check( $args, $assoc_args ) {
		$options = array(
			'checks'               => wp_parse_list( get_flag_value( $assoc_args, 'checks', '' ) ),
			'exclude-checks'       => wp_parse_list( get_flag_value( $assoc_args, 'exclude-checks', '' ) ),
			'exclude-files'        => wp_parse_list( get_flag_value( $assoc_args, 'exclude-files', '' ) ),
			'exclude-directories'  => wp_parse_list( get_flag_value( $assoc_args, 'exclude-directories', '' ) ),
			'categories'           => wp_parse_list( get_flag_value( $assoc_args, 'categories', '' ) ),
			'format'               => get_flag_value( $assoc_args, 'format', 'table' ),
			'ignore-warnings'      => (bool) get_flag_value( $assoc_args, 'ignore-warnings', false ),
			'ignore-errors'        => (bool) get_flag_value( $assoc_args, 'ignore-errors', false ),
			'include-experimental' => (bool) get_flag_value( $assoc_args, 'include-experimental', false ),
		);

		add_filter(
			'wp_plugin_check_ignore_directories',
			static function ( $dirs ) use ( $options ) {
				return array_unique( array_merge( $dirs, $options['exclude-directories'] ) );
			}
		);

		add_filter(
			'wp_plugin_check_ignore_files',
			static function ( $dirs ) use ( $options ) {
				return array_unique( array_merge( $dirs, $options['exclude-files'] ) );
			}
		);

		$runner = new CLI_Runner();

		$checks_to_run = array();

		try {
			$runner->set_experimental_flag( $options['include-experimental'] );
			$runner->set_check_slugs( $options['checks'] );
			$runner->set_check_exclude_slugs( $options['exclude-checks'] );
			$runner->set_categories( $options['categories'] );
			$runner->set_categories( $options['categories'] );
			$runner->set_plugin( $args[0] );

			$checks_to_run = $runner->get_checks_to_run();
		} catch ( Exception $error ) {
			WP_CLI::error( $error->getMessage() );
		}

		if ( $this->has_runtime_check( $checks_to_run ) ) {
			WP_CLI::line( __( 'Setting up runtime environment.', 'plugin-check' ) );
			$runtime_setup = new Runtime_Environment_Setup();
			$runtime_setup->set_up();
		}

		$result = false;
		// Run checks against the plugin.
		try {
			$result = $runner->run();
		} catch ( Exception $error ) {
			Plugin_Request_Utility::destroy_runner();

			if ( isset( $runtime_setup ) ) {
				$runtime_setup->clean_up();
				WP_CLI::line( __( 'Cleaning up runtime environment.', 'plugin-check' ) );
			}

			WP_CLI::error( $error->getMessage() );
		}

		Plugin_Request_Utility::destroy_runner();

		if ( isset( $runtime_setup ) ) {
			$runtime_setup->clean_up();
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

		// Default fields.
		$default_fields = $this->get_check_default_fields( $assoc_args );

		// Get formatter.
		$formatter = $this->get_formatter( $assoc_args, $default_fields );

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
	 * Lists the available checks for plugins.
	 *
	 * ## OPTIONS
	 *
	 * [--fields=<fields>]
	 * : Limit displayed results to a subset of fields provided.
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
	 * [--categories]
	 * : Limit displayed results to include only specific categories.
	 *
	 * [--include-experimental]
	 * : Include experimental checks.
	 *
	 * ## EXAMPLES
	 *
	 *   wp plugin list-checks
	 *   wp plugin list-checks --format=json
	 *
	 * @subcommand list-checks
	 *
	 * @since 1.0.0
	 *
	 * @param array $args       List of the positional arguments.
	 * @param array $assoc_args List of the associative arguments.
	 *
	 * @throws WP_CLI\ExitException Show error if invalid format argument.
	 */
	public function list_checks( $args, $assoc_args ) {
		$check_repo = new Default_Check_Repository();

		$options = array(
			'format'               => get_flag_value( $assoc_args, 'format', 'table' ),
			'categories'           => wp_parse_list( get_flag_value( $assoc_args, 'categories', '' ) ),
			'include-experimental' => (bool) get_flag_value( $assoc_args, 'include-experimental', false ),
		);

		$check_flags = Check_Repository::TYPE_ALL;

		// Check whether to include experimental checks.
		if ( $options['include-experimental'] ) {
			$check_flags = $check_flags | Check_Repository::INCLUDE_EXPERIMENTAL;
		}

		$collection = $check_repo->get_checks( $check_flags );

		// Filters the checks by specific categories.
		if ( ! empty( $options['categories'] ) ) {
			$categories = array_map( 'trim', explode( ',', $options['categories'] ) );
			$collection = Check_Categories::filter_checks_by_categories( $collection, $categories );
		}

		$all_checks = array();

		foreach ( $collection as $key => $check ) {
			$item = array();

			$item['slug']      = $key;
			$item['stability'] = strtolower( $check->get_stability() );
			$item['category']  = join( ', ', $check->get_categories() );

			$all_checks[] = $item;
		}

		$fields = wp_parse_list(
			get_flag_value(
				$assoc_args,
				'fields',
				array(
					'slug',
					'category',
					'stability',
				)
			)
		);

		// Get formatter.
		$formatter = $this->get_formatter( $options, $fields );

		// Display results.
		$formatter->display_items( $all_checks );
	}

	/**
	 * Lists the available check categories for plugins.
	 *
	 * ## OPTIONS
	 *
	 * [--fields=<fields>]
	 * : Limit displayed results to a subset of fields provided.
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
	 * ## EXAMPLES
	 *
	 *   wp plugin list-check-categories
	 *   wp plugin list-check-categories --format=json
	 *
	 * @subcommand list-check-categories
	 *
	 * @since 1.0.0
	 *
	 * @param array $args       List of the positional arguments.
	 * @param array $assoc_args List of the associative arguments.
	 *
	 * @throws WP_CLI\ExitException Show error if invalid format argument.
	 */
	public function list_check_categories( $args, $assoc_args ) {
		$options = array(
			'format' => get_flag_value( $assoc_args, 'format', 'table' ),
		);

		// Get check categories details.
		$categories = $this->get_check_categories();

		$fields = wp_parse_list(
			get_flag_value(
				$assoc_args,
				'fields',
				array(
					'name',
					'slug',
				)
			)
		);

		// Get formatter.
		$formatter = $this->get_formatter( $options, $fields );

		// Display results.
		$formatter->display_items( $categories );
	}

	/**
	 * Returns check categories details.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of the check categories.
	 */
	private function get_check_categories() {
		$check_categories = new Check_Categories();
		$all_categories   = $check_categories->get_categories();

		$categories = array();

		foreach ( $all_categories as $slug => $label ) {
			$categories[] = array(
				'slug' => $slug,
				'name' => $label,
			);
		}

		return $categories;
	}

	/**
	 * Gets the formatter instance to format check results.
	 *
	 * @since 1.0.0
	 *
	 * @param array $assoc_args Associative arguments.
	 * @param array $fields     Fields to display of each item.
	 * @return WP_CLI\Formatter The formatter instance.
	 */
	private function get_formatter( $assoc_args, $fields ) {
		return new WP_CLI\Formatter(
			$assoc_args,
			$fields
		);
	}

	/**
	 * Returns check default fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return array Default fields.
	 */
	private function get_check_default_fields( $assoc_args ) {
		$default_fields = array(
			'line',
			'column',
			'code',
			'message',
		);

		// If both errors and warnings are included, display the type of each result too.
		if ( empty( $assoc_args['ignore-errors'] ) && empty( $assoc_args['ignore-warnings'] ) ) {
			$default_fields = array(
				'line',
				'column',
				'type',
				'code',
				'message',
			);
		}

		return $default_fields;
	}

	/**
	 * Flattens and combines the given associative array of file errors and file warnings into a two-dimensional array.
	 *
	 * @since 1.0.0
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
			static function ( $a, $b ) {
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
