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
use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
use WP_CLI;

/**
 * Plugin check command.
 */
final class Plugin_Check_Command extends Abstract_Plugin_Check_Command {

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
	 * : Additional directories to exclude from checks
	 * By default, `.git`, `vendor` and `node_modules` directories are excluded.
	 *
	 * ## EXAMPLES
	 *
	 *   wp plugin check akismet
	 *   wp plugin check akismet --checks=late_escaping
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
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function check( $args, $assoc_args ) {
		// Get options based on the CLI arguments.
		$options = $this->get_options(
			$assoc_args,
			array(
				'checks'               => '',
				'format'               => 'table',
				'ignore-warnings'      => false,
				'ignore-errors'        => false,
				'include-experimental' => false,
			)
		);

		// Create the plugin and checks array from CLI arguments.
		$plugin = isset( $args[0] ) ? $args[0] : '';
		$checks = wp_parse_list( $options['checks'] );

		// Create the categories array from CLI arguments.
		$categories = isset( $options['categories'] ) ? wp_parse_list( $options['categories'] ) : array();

		$excluded_directories = isset( $options['exclude-directories'] ) ? wp_parse_list( $options['exclude-directories'] ) : array();

		add_filter(
			'wp_plugin_check_ignore_directories',
			static function ( $dirs ) use ( $excluded_directories ) {
				return array_unique( array_merge( $dirs, $excluded_directories ) );
			}
		);

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
			$runner->set_experimental_flag( $options['include-experimental'] );
			$runner->set_check_slugs( $checks );
			$runner->set_plugin( $plugin );
			$runner->set_categories( $categories );

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
			$this->display_results( $formatter, $file_results, sprintf( 'FILE: %s', $file_name ) );
		}

		// If there are any files left with only warnings, print those next.
		foreach ( $warnings as $file_name => $file_warnings ) {
			$file_results = $this->flatten_file_results( array(), $file_warnings );
			$this->display_results( $formatter, $file_results, sprintf( 'FILE: %s', $file_name ) );
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
	 * @subcommand list-checks
	 *
	 * @since n.e.x.t
	 *
	 * @param array $args       List of the positional arguments.
	 * @param array $assoc_args List of the associative arguments.
	 *
	 * @throws WP_CLI\ExitException Show error if invalid format argument.
	 */
	public function list_checks( $args, $assoc_args ) {
		$check_repo = new Default_Check_Repository();

		// Get options based on the CLI arguments.
		$options = $this->get_options(
			$assoc_args,
			array(
				'format'               => 'table',
				'categories'           => '',
				'include-experimental' => false,
			)
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

		// Get formatter.
		$formatter = $this->get_formatter(
			$options,
			array(
				'slug',
				'category',
				'stability',
			)
		);

		// Display results.
		$this->display_results( $formatter, $all_checks );
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
	 * @since n.e.x.t
	 *
	 * @param array $args       List of the positional arguments.
	 * @param array $assoc_args List of the associative arguments.
	 *
	 * @throws WP_CLI\ExitException Show error if invalid format argument.
	 */
	public function list_check_categories( $args, $assoc_args ) {
		// Get options based on the CLI arguments.
		$options = $this->get_options( $assoc_args, array( 'format' => 'table' ) );

		// Get check categories details.
		$categories = $this->get_check_categories();

		// Get formatter.
		$formatter = $this->get_formatter(
			$options,
			array(
				'name',
				'slug',
			)
		);

		// Display results.
		$this->display_results( $formatter, $categories );
	}

	/**
	 * Returns check categories details.
	 *
	 * @since n.e.x.t
	 *
	 * @return array List of the check categories.
	 */
	private function get_check_categories() {
		$check_categories = new Check_Categories();
		$category_labels  = $check_categories->get_category_labels();

		$categories = array();

		foreach ( $category_labels as $slug => $label ) {
			$categories[] = array(
				'slug' => $slug,
				'name' => $label,
			);
		}

		return $categories;
	}

	/**
	 * Returns check default fields.
	 *
	 * @since n.e.x.t
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
		if ( empty( $assoc_args['ignore_errors'] ) && empty( $assoc_args['ignore_warnings'] ) ) {
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
}
