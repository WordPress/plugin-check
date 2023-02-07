<?php
/**
 * Class WordPress\Plugin_Check\CLI\Plugin_Check_Command
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\CLI;

use WordPress\Plugin_Check\Plugin_Context;
use Exception;

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
	 * @param array $args List of the positional arguments.
	 * @param array $assoc_args List of the associative arguments.
	 *
	 * @throws Exception Throws exception.
	 */
	public function check( $args, $assoc_args ) {

		$options          = $this->get_options( $assoc_args );
		$plugin_base_file = $this->get_plugin_from_args( $args );

		try {

			// TODO: Call `run()` method of the `CLI_Runner` class.

		} catch ( Exception $error ) {

			\WP_CLI::error( $error->getMessage() );
		}
	}

	/**
	 * Get plugin main file.
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
	 * @param array $assoc_args List of the associative arguments.
	 * @return array List of the associative arguments.
	 */
	protected function get_options( $assoc_args ) {

		$options = array(
			'checks'          => 'all',
			'flag'            => 'stable',
			'format'          => 'table',
			'fields'          => 'all',
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
	 * Get formatter class.
	 *
	 * @param array $assoc_args List of the associative arguments.
	 */
	protected function get_formatter( $assoc_args ) {
	}

	/**
	 * Combining a files errors and warning into a single array and order them by file number.
	 *
	 * @param array $file_errors   List of errors.
	 * @param array $file_warnings List of warnings.
	 */
	protected function flatten_file_results( $file_errors, $file_warnings ) {
	}

	/**
	 * Display results.
	 *
	 * @param \WP_CLI\Formatter $formatter    Formatter class.
	 * @param string            $file_name    File name.
	 * @param array             $file_results Results.
	 */
	protected function display_results( $formatter, $file_name, $file_results ) {
	}

	/**
	 * Converts a plugin basename back into a friendly slug.
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
	 * Uses the same filter core uses in plugins.php to determine which plugins
	 * should be available to manage through the WP_Plugins_List_Table class.
	 *
	 * @return array
	 */
	private function get_all_plugins() {

		return apply_filters( 'all_plugins', get_plugins() );
	}
}
