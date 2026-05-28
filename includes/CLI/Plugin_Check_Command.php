<?php
/**
 * Class WordPress\Plugin_Check\CLI\Plugin_Check_Command
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\CLI;

use Exception;
use WordPress\Plugin_Check\Checker\Check;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Repository;
use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Checker\Default_Check_Repository;
use WordPress\Plugin_Check\Plugin_Context;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
use WordPress\Plugin_Check\Utilities\Results_Exporter;
use WP_CLI;

/**
 * Plugin check command.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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
	 * Output format type.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	protected $output_formats = array(
		'table',
		'csv',
		'json',
		'ctrf',
		'strict-table',
		'strict-csv',
		'strict-json',
		'strict-ctrf',
	);

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
	 * [--ignore-codes=<codes>]
	 * : Ignore error codes provided as an argument in comma-separated values.
	 *
	 * [--format=<format>]
	 * : Format to display the results. Options are table, csv, json, ctrf, strict-table, strict-csv, strict-json, and strict-ctrf. The default will be a table.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - ctrf
	 *   - strict-table
	 *   - strict-csv
	 *   - strict-json
	 *   - strict-ctrf
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
	 * By default, `.git`, `vendor`, `vendor_prefixed`, `vendor-prefixed` and `node_modules` directories are excluded.
	 *
	 * [--exclude-files=<files>]
	 * : Additional files to exclude from checks.
	 *
	 * [--severity=<severity>]
	 * : Severity level.
	 *
	 * [--error-severity=<error-severity>]
	 * : Error severity level.
	 *
	 * [--warning-severity=<warning-severity>]
	 * : Warning severity level.
	 *
	 * [--include-low-severity-errors]
	 * : Include errors with lower severity than the threshold as other type.
	 *
	 * [--include-low-severity-warnings]
	 * : Include warnings with lower severity than the threshold as other type.
	 *
	 * [--slug=<slug>]
	 * : Slug to override the default.
	 *
	 * [--mode=<mode>]
	 * : Mode to run the checks in. Options are 'new' (default) or 'update'.
	 * ---
	 * default: new
	 * options:
	 *   - new
	 *   - update
	 * ---
	 *
	 * [--ai]
	 * : Enable AI-based analysis to detect false positives in check results.
	 *
	 * [--ai-model=<model>]
	 * : AI model preference for analysis (e.g., 'openai::gpt-4o'). Requires --ai.
	 *
	 * ## EXAMPLES
	 *
	 *   wp plugin check akismet
	 *   wp plugin check akismet --checks=late_escaping
	 *   wp plugin check akismet --format=json
	 *   wp plugin check akismet --mode=update
	 *   wp plugin check akismet --ai
	 *   wp plugin check akismet --ai --ai-model=openai::gpt-4o
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
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function check( $args, $assoc_args ) {
		// Get options based on the CLI arguments.
		$options = $this->get_options(
			$assoc_args,
			array(
				'checks'                        => '',
				'format'                        => 'table',
				'ignore-warnings'               => false,
				'ignore-errors'                 => false,
				'include-experimental'          => false,
				'severity'                      => '',
				'error-severity'                => '',
				'warning-severity'              => '',
				'include-low-severity-errors'   => false,
				'include-low-severity-warnings' => false,
				'slug'                          => '',
				'ignore-codes'                  => '',
				'mode'                          => 'new',
				'ai'                            => false,
				'ai-model'                      => '',
			)
		);

		// Create the plugin and checks array from CLI arguments.
		$plugin = isset( $args[0] ) ? $args[0] : '';
		$checks = wp_parse_list( $options['checks'] );

		// Ignore codes.
		$ignore_codes = isset( $options['ignore-codes'] ) ? wp_parse_list( $options['ignore-codes'] ) : array();

		// Create the categories array from CLI arguments.
		$categories = isset( $options['categories'] ) ? wp_parse_list( $options['categories'] ) : array();

		$excluded_directories = isset( $options['exclude-directories'] ) ? wp_parse_list( $options['exclude-directories'] ) : array();

		add_filter(
			'wp_plugin_check_ignore_directories',
			static function ( $dirs ) use ( $excluded_directories ) {
				return array_unique( array_merge( $dirs, $excluded_directories ) );
			}
		);

		$excluded_files = isset( $options['exclude-files'] ) ? wp_parse_list( $options['exclude-files'] ) : array();

		add_filter(
			'wp_plugin_check_ignore_files',
			static function ( $dirs ) use ( $excluded_files ) {
				return array_unique( array_merge( $dirs, $excluded_files ) );
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

		// Ensure the correct slug.
		if ( is_dir( $plugin ) && empty( $options['slug'] ) ) {
			$options['slug'] = basename( $plugin );
		} elseif ( filter_var( $plugin, FILTER_VALIDATE_URL ) && empty( $options['slug'] ) ) {
			$options['slug'] = Plugin_Request_Utility::get_slug_from_url( $plugin );
		}

		try {
			$runner->set_experimental_flag( $options['include-experimental'] );
			$runner->set_check_slugs( $checks );
			$runner->set_plugin( $plugin );
			$runner->set_categories( $categories );
			$runner->set_slug( $options['slug'] );
			$runner->set_mode( $options['mode'] );
			$runner->set_use_ai( $options['ai'] );
			if ( ! empty( $options['ai-model'] ) ) {
				$runner->set_ai_model_preference( $options['ai-model'] );
			}
		} catch ( Exception $error ) {
			WP_CLI::error( $error->getMessage() );
		}

		$result = false;
		// Run checks against the plugin.
		try {
			$result = $runner->run();
		} catch ( Exception $error ) {
			Plugin_Request_Utility::destroy_runner();

			WP_CLI::error( $error->getMessage() );
		}

		Plugin_Request_Utility::destroy_runner();

		// Get errors and warnings from the results.
		$errors = array();
		if ( $result && empty( $assoc_args['ignore-errors'] ) ) {
			$errors = $result->get_errors();
		}
		$warnings = array();
		if ( $result && empty( $assoc_args['ignore-warnings'] ) ) {
			$warnings = $result->get_warnings();
		}

		// Get AI analysis results if available.
		$ai_analysis = array();
		if ( $result && $options['ai'] ) {
			$ai_analysis = $result->get_ai_analysis();
		}

		// Get AI statistics if available.
		$ai_stats = array();
		if ( $result && $options['ai'] ) {
			$ai_stats = $result->get_ai_stats();
		}

		if ( empty( $errors ) && empty( $warnings ) ) {
			$message = __( 'Checks complete. No errors found.', 'plugin-check' );

			// Add AI statistics to the message if available.
			if ( ! empty( $ai_stats ) && isset( $ai_stats['false_positives'] ) && $ai_stats['false_positives'] > 0 ) {
				$ai_info = sprintf(
					// translators: %1$d: Number of possible false positives, %2$s: "possible false positive(s)" label.
					__( ' AI detected %1$d %2$s', 'plugin-check' ),
					$ai_stats['false_positives'],
					_n( 'possible false positive', 'possible false positives', $ai_stats['false_positives'], 'plugin-check' )
				);
				if ( isset( $ai_stats['tokens_spent'] ) && $ai_stats['tokens_spent'] > 0 ) {
					$ai_info .= sprintf(
						// translators: %s: Tokens spent (formatted).
						__( ' (Tokens spent: %s)', 'plugin-check' ),
						number_format_i18n( $ai_stats['tokens_spent'] )
					);
				}
				$message .= '.' . $ai_info;
			}

			WP_CLI::success( $message );

			return;
		}

		// Default fields.
		$default_fields = $this->get_check_default_fields( $assoc_args );

		// Get formatter.
		$formatter = $this->get_formatter( $assoc_args, $default_fields );

		// Severity.
		$error_severity                = ! empty( $options['error-severity'] ) ? $options['error-severity'] : $options['severity'];
		$warning_severity              = ! empty( $options['warning-severity'] ) ? $options['warning-severity'] : $options['severity'];
		$include_low_severity_errors   = ! empty( $options['include-low-severity-errors'] ) ? true : false;
		$include_low_severity_warnings = ! empty( $options['include-low-severity-warnings'] ) ? true : false;

		$all_results = array();

		// Collect all errors.
		foreach ( $errors as $file_name => $file_errors ) {
			$file_warnings = array();
			if ( isset( $warnings[ $file_name ] ) ) {
				$file_warnings = $warnings[ $file_name ];
				unset( $warnings[ $file_name ] );
			}
			$file_results = Results_Exporter::flatten_file_results( $file_errors, $file_warnings );

			if ( ! empty( $ignore_codes ) ) {
				$file_results = $this->get_filtered_results_by_ignore_codes( $file_results, $ignore_codes );
			}

			if ( '' !== $error_severity || '' !== $warning_severity ) {
				$file_results = $this->get_filtered_results_by_severity( $file_results, intval( $error_severity ), intval( $warning_severity ), $include_low_severity_errors, $include_low_severity_warnings );
			}

			foreach ( $file_results as $item ) {
				$item['file']  = $file_name;
				$all_results[] = $item;
			}
		}

		// Collect remaining warnings.
		foreach ( $warnings as $file_name => $file_warnings ) {
			$file_results = Results_Exporter::flatten_file_results( array(), $file_warnings );

			if ( ! empty( $ignore_codes ) ) {
				$file_results = $this->get_filtered_results_by_ignore_codes( $file_results, $ignore_codes );
			}

			if ( '' !== $error_severity || '' !== $warning_severity ) {
				$file_results = $this->get_filtered_results_by_severity( $file_results, intval( $error_severity ), intval( $warning_severity ), $include_low_severity_errors, $include_low_severity_warnings );
			}

			foreach ( $file_results as $item ) {
				$item['file']  = $file_name;
				$all_results[] = $item;
			}
		}

		// Handle CTRF formats.
		if ( Results_Exporter::FORMAT_CTRF === $options['format'] || 'strict-' . Results_Exporter::FORMAT_CTRF === $options['format'] ) {
			$ctrf_report = Results_Exporter::to_ctrf_json(
				$all_results,
				array(
					'timestamp_iso' => gmdate( 'c' ),
				)
			);

			WP_CLI::line( $ctrf_report );
			return;
		}

		// Handle strict-* formats.
		if ( str_starts_with( $options['format'], 'strict-' ) ) {
			$base_format = substr( $options['format'], 7 );

			$formatter_args           = $assoc_args;
			$formatter_args['format'] = $base_format;

			$formatter = $this->get_formatter( $formatter_args, $default_fields );
			$formatter->display_items( $all_results );
			return;
		}

		$false_positive_results = array();
		if ( ! empty( $ai_analysis ) ) {
			$split_results          = $this->split_false_positive_results( $all_results, $ai_analysis );
			$all_results            = $split_results['actionable'];
			$false_positive_results = $split_results['false_positives'];
		}

		// Group results by file.
		$results_by_file = array();

		foreach ( $all_results as $item ) {
			$results_by_file[ $item['file'] ][] = $item;
		}

		foreach ( $results_by_file as $file_name => $file_results ) {
			$this->display_results( $formatter, $file_name, $file_results );
		}

		// Display AI analysis summary if available.
		if ( ! empty( $ai_analysis ) || ! empty( $ai_stats ) ) {
			$this->display_ai_summary( $ai_analysis, $ai_stats, $false_positive_results );
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

		/**
		 * All checks to list.
		 *
		 * @var Check $check
		 */
		foreach ( $collection as $key => $check ) {
			$item = array();

			$item['slug']        = $key;
			$item['stability']   = strtolower( $check->get_stability() );
			$item['category']    = join( ', ', $check->get_categories() );
			$item['description'] = $check->get_description();
			$item['url']         = $check->get_documentation_url();

			$all_checks[] = $item;
		}

		// Get formatter.
		$formatter = $this->get_formatter(
			$options,
			array(
				'slug',
				'category',
				'stability',
				'description',
				'url',
			)
		);

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
	 * Validates the associative arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $assoc_args List of the associative arguments.
	 * @param array $defaults   List of the default arguments.
	 * @return array List of the associative arguments.
	 *
	 * @throws WP_CLI\ExitException Show error if invalid format argument.
	 */
	private function get_options( $assoc_args, $defaults ) {
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
	 * @since 1.0.0
	 *
	 * @param array $assoc_args     Associative arguments.
	 * @param array $default_fields Default fields.
	 * @return WP_CLI\Formatter The formatter instance.
	 */
	private function get_formatter( $assoc_args, $default_fields ) {
		if ( isset( $assoc_args['fields'] ) ) {
			$default_fields = wp_parse_args( $assoc_args['fields'], $default_fields );
		}

		return new WP_CLI\Formatter(
			$assoc_args,
			$default_fields
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
			'docs',
		);

		// If both errors and warnings are included, display the type of each result too.
		if ( empty( $assoc_args['ignore_errors'] ) && empty( $assoc_args['ignore_warnings'] ) ) {
			$default_fields = array(
				'line',
				'column',
				'type',
				'code',
				'message',
				'docs',
			);
		}

		return $default_fields;
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
	 * Splits likely false positives out of the main check results.
	 *
	 * @since 2.0.0
	 *
	 * @param array $results     Check results.
	 * @param array $ai_analysis AI analysis results.
	 * @return array Results split into actionable and false positive groups.
	 */
	private function split_false_positive_results( array $results, array $ai_analysis ) {
		$split_results = array(
			'actionable'      => array(),
			'false_positives' => array(),
		);

		foreach ( $results as $item ) {
			$analysis = $this->find_ai_analysis_for_result( $item, $ai_analysis );

			if ( ! empty( $analysis['is_false_positive'] ) ) {
				if ( ! empty( $analysis['reasoning'] ) ) {
					$item['reasoning'] = $analysis['reasoning'];
				}
				$split_results['false_positives'][] = $item;
				continue;
			}

			$split_results['actionable'][] = $item;
		}

		return $split_results;
	}

	/**
	 * Finds the AI analysis entry for a result item.
	 *
	 * @since 2.0.0
	 *
	 * @param array $item        Result item.
	 * @param array $ai_analysis AI analysis results.
	 * @return array|null AI analysis entry, or null if none is found.
	 */
	private function find_ai_analysis_for_result( array $item, array $ai_analysis ) {
		foreach ( $ai_analysis as $analysis ) {
			if ( ! is_array( $analysis ) ) {
				continue;
			}

			if (
				(string) ( $analysis['file'] ?? '' ) === (string) ( $item['file'] ?? '' ) &&
				(int) ( $analysis['line'] ?? 0 ) === (int) ( $item['line'] ?? 0 ) &&
				(int) ( $analysis['column'] ?? 0 ) === (int) ( $item['column'] ?? 0 ) &&
				(string) ( $analysis['code'] ?? '' ) === (string) ( $item['code'] ?? '' )
			) {
				return $analysis;
			}
		}

		return null;
	}

	/**
	 * Displays AI analysis summary.
	 *
	 * @since 2.0.0
	 *
	 * @param array $ai_analysis            AI analysis results.
	 * @param array $ai_stats               AI statistics.
	 * @param array $false_positive_results False positive results.
	 */
	private function display_ai_summary(
		array $ai_analysis,
		array $ai_stats,
		array $false_positive_results = array()
	) {
		WP_CLI::line( '' );
		WP_CLI::line( str_repeat( '─', 60 ) );
		WP_CLI::line( '✨ ' . __( 'AI Possible False Positive Analysis', 'plugin-check' ) );
		WP_CLI::line( str_repeat( '─', 60 ) );

		if ( ! empty( $ai_stats ) ) {
			$issues_analyzed = isset( $ai_stats['issues_analyzed'] ) ? (int) $ai_stats['issues_analyzed'] : 0;
			$false_positives = isset( $ai_stats['false_positives'] ) ? (int) $ai_stats['false_positives'] : 0;
			$tokens_spent    = isset( $ai_stats['tokens_spent'] ) ? (int) $ai_stats['tokens_spent'] : 0;

			WP_CLI::line(
				sprintf(
					/* translators: %d: Number of issues analyzed. */
					__( 'Issues analyzed: %d', 'plugin-check' ),
					$issues_analyzed
				)
			);
			WP_CLI::line(
				sprintf(
					/* translators: %d: Number of possible false positives detected. */
					__( 'Possible false positives detected: %d', 'plugin-check' ),
					$false_positives
				)
			);

			if ( $tokens_spent > 0 ) {
				WP_CLI::line(
					sprintf(
						/* translators: %s: Number of tokens spent. */
						__( 'Tokens spent: %s', 'plugin-check' ),
						number_format_i18n( $tokens_spent )
					)
				);
			}
		}

		// Show individual false positive details.
		if ( ! empty( $false_positive_results ) ) {
			WP_CLI::line( '' );
			WP_CLI::line( __( 'Possible false positives:', 'plugin-check' ) );

			foreach ( $false_positive_results as $item ) {
				$location = isset( $item['file'] ) ? $item['file'] : '';
				if ( isset( $item['line'] ) ) {
					$location .= ':' . $item['line'];
				}

				WP_CLI::line(
					sprintf(
						'  %s - %s',
						$location,
						isset( $item['reasoning'] ) ? $item['reasoning'] : $item['message']
					)
				);
			}
		}

		WP_CLI::line( '' );
	}

	/**
	 * Returns check results filtered by severity level.
	 *
	 * @since 1.1.0
	 *
	 * @param array $results                       Check results.
	 * @param int   $error_severity                Error severity level.
	 * @param int   $warning_severity              Warning severity level.
	 * @param bool  $include_low_severity_errors   Include less level of severity issues as warning.
	 * @param bool  $include_low_severity_warnings Include less level of severity issues as warning.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 * @return array Filtered results.
	 */
	private function get_filtered_results_by_severity( $results, $error_severity, $warning_severity, $include_low_severity_errors = false, $include_low_severity_warnings = false ) {
		$errors   = array();
		$warnings = array();

		foreach ( $results as $item ) {
			if ( 'ERROR' === $item['type'] && $item['severity'] >= $error_severity ) {
				$errors[] = $item;
			} elseif ( $include_low_severity_errors && 'ERROR' === $item['type'] && $item['severity'] < $error_severity ) {
				$item['type'] = 'ERROR_LOW_SEVERITY';
				$errors[]     = $item;
			} elseif ( $include_low_severity_warnings && 'WARNING' === $item['type'] && $item['severity'] < $warning_severity ) {
				$item['type'] = 'WARNING_LOW_SEVERITY';
				$warnings[]   = $item;
			} elseif ( 'WARNING' === $item['type'] && $item['severity'] >= $warning_severity ) {
				$warnings[] = $item;
			}
		}

		return array_merge( $errors, $warnings );
	}

	/**
	 * Returns check results filtered by ignore codes.
	 *
	 * @since 1.4.0
	 *
	 * @param array $results      Check results.
	 * @param array $ignore_codes Array of error codes to be ignored.
	 * @return array Filtered results.
	 */
	private function get_filtered_results_by_ignore_codes( $results, $ignore_codes ) {
		return array_filter(
			$results,
			static function ( $result ) use ( $ignore_codes ) {
				return ! in_array( $result['code'], $ignore_codes, true );
			}
		);
	}
}
