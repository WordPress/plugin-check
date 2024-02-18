<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use PHP_CodeSniffer\Runner;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

/**
 * Check for running one or more PHP CodeSniffer sniffs.
 *
 * @since 1.0.0
 */
abstract class Abstract_PHP_CodeSniffer_Check implements Static_Check {

	use Amend_Check_Result;

	/**
	 * List of allowed PHPCS arguments.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $allowed_args = array(
		'standard'   => true,
		'extensions' => true,
		'sniffs'     => true,
		'exclude'    => true, //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
	);

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *    An associative array of PHPCS CLI arguments. Can include one or more of the following options.
	 *
	 *    @type string $standard   The name or path to the coding standard to check against.
	 *    @type string $extensions A comma separated list of file extensions to check against.
	 *    @type string $sniffs     A comma separated list of sniff codes to include from checks.
	 *    @type string $exclude    A comma separated list of sniff codes to exclude from checks.
	 * }
	 */
	abstract protected function get_args();

	/**
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	final public function run( Check_Result $result ) {
		// Include the PHPCS autoloader.
		$autoloader = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'vendor/squizlabs/php_codesniffer/autoload.php';

		if ( file_exists( $autoloader ) ) {
			include_once $autoloader;
		}

		if ( ! class_exists( '\PHP_CodeSniffer\Runner' ) ) {
			throw new Exception(
				__( 'Unable to find PHPCS Runner class.', 'plugin-check' )
			);
		}

		// Backup the original command line arguments.
		$orig_cmd_args = $_SERVER['argv'] ?? '';

		// Create the default arguments for PHPCS.
		$defaults = $this->get_argv_defaults( $result );

		// Set the check arguments for PHPCS.
		$_SERVER['argv'] = $this->parse_argv( $this->get_args(), $defaults );

		// Reset PHP_CodeSniffer config.
		$this->reset_php_codesniffer_config();

		// Run PHPCS.
		try {
			ob_start();
			$runner = new Runner();
			$runner->runPHPCS();
			$reports = ob_get_clean();
		} catch ( Exception $e ) {
			$_SERVER['argv'] = $orig_cmd_args;
			throw $e;
		}

		// Restore original arguments.
		$_SERVER['argv'] = $orig_cmd_args;

		// Parse the reports into data to add to the overall $result.
		$reports = json_decode( trim( $reports ), true );

		if ( empty( $reports['files'] ) ) {
			return;
		}

		foreach ( $reports['files'] as $file_name => $file_results ) {
			if ( empty( $file_results['messages'] ) ) {
				continue;
			}

			foreach ( $file_results['messages'] as $file_message ) {
				$this->add_result_message_for_file(
					$result,
					strtoupper( $file_message['type'] ) === 'ERROR',
					$file_message['message'],
					$file_message['source'],
					$file_name,
					$file_message['line'],
					$file_message['column']
				);
			}
		}
	}

	/**
	 * Parse the command arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $argv     An array of arguments to pass.
	 * @param array $defaults An array of default arguments.
	 * @return array An indexed array of PHPCS CLI arguments.
	 */
	private function parse_argv( $argv, $defaults ) {
		// Only accept allowed PHPCS arguments from check arguments array.
		$check_args = array_intersect_key( $argv, $this->allowed_args );

		// Format check arguments for PHPCS.
		foreach ( $check_args as $key => $value ) {
			$defaults[] = "--{$key}=$value";
		}

		return $defaults;
	}

	/**
	 * Gets the default command arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @return array An indexed array of PHPCS CLI arguments.
	 */
	private function get_argv_defaults( Check_Result $result ): array {
		$defaults = array(
			'',
			$result->plugin()->location(),
			'--report=Json',
			'--report-width=9999',
		);

		$directories_to_ignore = Plugin_Request_Utility::get_directories_to_ignore();
		if ( ! empty( $directories_to_ignore ) ) {
			$defaults[] = '--ignore=*/' . implode( '/*,*/', $directories_to_ignore ) . '/*';
		}

		// Set the Minimum WP version supported for the plugin.
		if ( $result->plugin()->minimum_supported_wp() ) {
			// Due to the syntax of runtime-set, these must be passed as individual args.
			$defaults[] = '--runtime-set';
			$defaults[] = 'minimum_wp_version';
			$defaults[] = $result->plugin()->minimum_supported_wp();
		}

		return $defaults;
	}

	/**
	 * Resets \PHP_CodeSniffer\Config::$overriddenDefaults to prevent
	 * incorrect results when running multiple checks.
	 *
	 * @since 1.0.0
	 */
	private function reset_php_codesniffer_config() {
		if ( class_exists( '\PHP_CodeSniffer\Config' ) ) {
			/*
			 * PHPStan ignore reason: PHPStan raised an issue because we can't
			 * use class in ReflectionClass.
			 *
			 * @phpstan-ignore-next-line
			 */
			$reflected_phpcs_config = new \ReflectionClass( '\PHP_CodeSniffer\Config' );
			$overridden_defaults    = $reflected_phpcs_config->getProperty( 'overriddenDefaults' );
			$overridden_defaults->setAccessible( true );
			$overridden_defaults->setValue( array() );
			$overridden_defaults->setAccessible( false );
		}
	}
}
