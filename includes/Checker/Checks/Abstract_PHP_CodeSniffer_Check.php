<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use PHP_CodeSniffer\Config;
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
		'standard'    => true,
		'extensions'  => true,
		'sniffs'      => true,
		'runtime-set' => true,
		'exclude'     => true, //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
	);

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @return array {
	 *    An associative array of PHPCS CLI arguments. Can include one or more of the following options.
	 *
	 *    @type string $standard   The name or path to the coding standard to check against.
	 *    @type string $extensions A comma separated list of file extensions to check against.
	 *    @type string $sniffs     A comma separated list of sniff codes to include from checks.
	 *    @type string $exclude    A comma separated list of sniff codes to exclude from checks.
	 * }
	 */
	abstract protected function get_args( Check_Result $result );

	/**
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
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

		if ( ! class_exists( Runner::class ) ) {
			throw new Exception(
				__( 'Unable to find PHPCS Runner class.', 'plugin-check' )
			);
		}

		if ( ! class_exists( Config::class ) ) {
			throw new Exception(
				__( 'Unable to find PHPCS Config class.', 'plugin-check' )
			);
		}

		// Backup the original command line arguments.
		$orig_cmd_args = $_SERVER['argv'] ?? '';

		$args = $this->get_args( $result );

		// Reset PHP_CodeSniffer config.
		$this->reset_php_codesniffer_config();

		// Get current installed_paths config.
		$installed_paths = Config::getConfigData( 'installed_paths' );

		// Override installed_paths to load custom sniffs.
		if ( isset( $args['installed_paths'] ) && is_array( $args['installed_paths'] ) ) {
			Config::setConfigData( 'installed_paths', implode( ',', $args['installed_paths'] ), true );
		}

		// Create the default arguments for PHPCS.
		$defaults = $this->get_argv_defaults( $result );

		// Set the check arguments for PHPCS.
		$_SERVER['argv'] = $this->parse_argv( $args, $defaults );

		// Run PHPCS.
		$php_codesniffer_error_handler = $this->register_php_codesniffer_error_handler();
		try {
			ob_start();
			$this->run_php_codesniffer();
			$reports = ob_get_clean();
		} catch ( Exception $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			throw $e;
		} finally {
			$this->restore_php_codesniffer_error_handler( $php_codesniffer_error_handler );
			// Reset installed_paths.
			Config::setConfigData( 'installed_paths', $installed_paths, true );

			// Restore original arguments.
			$_SERVER['argv'] = $orig_cmd_args;
		}

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
					esc_html( $file_message['message'] ),
					$file_message['source'],
					$file_name,
					$file_message['line'],
					$file_message['column'],
					'',
					$file_message['severity']
				);
			}
		}
	}

	/**
	 * Runs PHP_CodeSniffer.
	 *
	 * @since n.e.x.t
	 */
	protected function run_php_codesniffer() {

		$runner = new Runner();
		$runner->runPHPCS();
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
			if ( 'runtime-set' === $key ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $item_key => $item_value ) {
						$defaults = array_merge( $defaults, array( "--{$key}", $item_key, $item_value ) );
					}
				}
			} else {
				$defaults[] = "--{$key}=$value";
			}
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

		$ignore_patterns = array();

		$directories_to_ignore = Plugin_Request_Utility::get_directories_to_ignore();
		$files_to_ignore       = Plugin_Request_Utility::get_files_to_ignore();

		// Ignore directories.
		if ( ! empty( $directories_to_ignore ) ) {
			$ignore_patterns[] = '*/' . implode( '/*,*/', $directories_to_ignore ) . '/*';
		}

		// Ignore files.
		if ( ! empty( $files_to_ignore ) ) {
			$ignore_patterns[] = '/' . implode( ',/', $files_to_ignore );
		}

		if ( ! empty( $ignore_patterns ) ) {
			$defaults[] = '--ignore=' . implode( ',', $ignore_patterns );
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
	 * Registers an error handler for known PHPCS notices.
	 *
	 * @since n.e.x.t
	 */
	private function register_php_codesniffer_error_handler() {
		$previous_error_handler = null;

		$error_handler = static function ( $errno, $errstr, $errfile, $errline ) use ( &$previous_error_handler ) {
			$normalized_file = wp_normalize_path( $errfile );

			if (
				E_DEPRECATED === $errno &&
				false !== strpos( $errstr, 'auto_detect_line_endings is deprecated' ) &&
				false !== strpos( $normalized_file, 'vendor/squizlabs/php_codesniffer/src/Runner.php' )
			) {
				return true;
			}

			if ( is_callable( $previous_error_handler ) ) {
				return (bool) call_user_func( $previous_error_handler, $errno, $errstr, $errfile, $errline );
			}

			return false;
		};

		$previous_error_handler = set_error_handler( $error_handler );

		return $error_handler;
	}

	/**
	 * Restores the error handler that was active before running PHP_CodeSniffer.
	 *
	 * PHP_CodeSniffer registers its own error handler while processing files. If
	 * processing throws, PHP_CodeSniffer does not restore that handler, so it must
	 * be removed before the temporary deprecation handler can be restored.
	 *
	 * @since n.e.x.t
	 *
	 * @param callable $php_codesniffer_error_handler The temporary error handler registered before running PHP_CodeSniffer.
	 */
	private function restore_php_codesniffer_error_handler( $php_codesniffer_error_handler ) {

		$current_error_handler = set_error_handler( $php_codesniffer_error_handler );
		restore_error_handler();

		if ( $current_error_handler !== $php_codesniffer_error_handler ) {
			restore_error_handler();
		}

		restore_error_handler();
	}
	/**
	 * Resets \PHP_CodeSniffer\Config::$overriddenDefaults to prevent
	 * incorrect results when running multiple checks.
	 *
	 * @since 1.0.0
	 */
	private function reset_php_codesniffer_config() {
		if ( class_exists( Config::class ) ) {
			/*
			 * PHPStan ignore reason: PHPStan raised an issue because we can't
			 * use class in ReflectionClass.
			 *
			 * @phpstan-ignore-next-line
			 */
			$reflected_phpcs_config = new \ReflectionClass( Config::class );
			$overridden_defaults    = $reflected_phpcs_config->getProperty( 'overriddenDefaults' );

			/*
			 * The setAccessible function has no effect in PHP >= 8.1, and it is marked as deprecated in PHP 8.5.
			 * Since the tests are also run on PHP 7.4 and 8.0, we can only call the function if the php version is lower than 8.1.
			*/
			if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
				$overridden_defaults->setAccessible( true );
			}
			$overridden_defaults->setValue( $reflected_phpcs_config, array() );

			if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
				$overridden_defaults->setAccessible( false );
			}
		}
	}
}
