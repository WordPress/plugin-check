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
class Plugin_Check_Command extends \WP_CLI_Command {

//	public function __construct( $plugin_context ) {
//
//	}

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

		$plugin = $args[0];

		$plugins = $this->get_all_plugins();

		echo '$plugins: <pre>';
		print_r( $plugins );
		echo '</pre>';
		die;

		if ( empty( $plugin ) || validate_plugin( plugin_basename( $plugin ) ) ) {


			$plugin_name = \WP_CLI\Utils\get_plugin_name( $plugin );

			echo '$plugin_name: <pre>';
			print_r( $plugin_name );
			echo '</pre>';
			die;

		}


		// $args['plugin'];
	}

//	/**
//	 * If have optional args ([<plugin>...]) and an all option, then check have something to do.
//	 *
//	 * @param array  $args Passed-in arguments.
//	 * @param bool   $all All flag.
//	 * @param string $verb Optional. Verb to use. Defaults to 'install'.
//	 * @return array Same as $args if not all, otherwise all slugs.
//	 * @param string $exclude Comma separated list of plugin slugs.
//	 * @throws ExitException If neither plugin name nor --all were provided.
//	 */
//	protected function check_optional_args_and_all( $args, $all, $verb = 'install', $exclude = null ) {
//		if ( $all ) {
//			$args = array_map(
//				'\WP_CLI\Utils\get_plugin_name',
//				array_keys( $this->get_all_plugins() )
//			);
//		}
//
//		if ( $all && $exclude ) {
//			$exclude_list = explode( ',', trim( $exclude, ',' ) );
//			$args         = array_filter(
//				$args,
//				static function( $slug ) use ( $exclude_list ) {
//					return ! in_array( $slug, $exclude_list, true );
//				}
//			);
//		}
//
//		if ( empty( $args ) ) {
//			if ( ! $all ) {
//				WP_CLI::error( 'Please specify one or more plugins, or use --all.' );
//			}
//
//			$past_tense_verb = Utils\past_tense_verb( $verb );
//			WP_CLI::success( "No plugins {$past_tense_verb}." ); // Don't error if --all given for BC.
//		}
//
//		return $args;
//	}

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

\WP_CLI::add_command( 'plugin check', __CLASS_NAMESPACE__ );
