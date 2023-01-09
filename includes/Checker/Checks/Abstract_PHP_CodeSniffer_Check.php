<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\PHP_CodeSniffer_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check;
use WordPress\Plugin_Check\Checker\Check_Result;
use Exception;

/**
 * Check for running one or more PHP CodeSniffer sniffs.
 *
 * @since 1.0.0
 */
abstract class Abstract_PHP_CodeSniffer_Check implements Check {

	/**
	 * List of allowed PHPCS arguments.
	 *
	 * @var array
	 */
	protected $allowed_args = array(
		'extensions',
		'standard',
		'sniffs',
		'exclude',
	);

	/**
	 * Returns an associative array of arguements to pass to PHPCS.
	 *
	 * @return array
	 */
	abstract public function get_args();

	/**
	 * Creates the command arguments.
	 *
	 * @param Check_Result $result The check results including the plugin context to check.
	 *
	 * @return array
	 */
	private function create_args( Check_Result $result ) {
		// Set the default arguments for PHPCS.
		$argv = array(
			'',
			$result->plugin()->path( '' ),
			'--report=Json',
			'--report-width=9999',
		);

		// Only accept allowed PHPCS arguments from check arguments array.
		$check_args = array_filter(
			$this->get_args(),
			function( $key ) {
				return in_array( $key, $this->allowed_args, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		// Format check arguments for PHPCS.
		foreach ( $check_args as $key => $value ) {
			$argv[] = "--{$key}=$value";
		}

		return $argv;
	}

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
	public function run( Check_Result $result ) {
		// Include the PHPCS autoloader.
		$autoloader = TESTS_PLUGIN_DIR . '/vendor/squizlabs/php_codesniffer/autoload.php' );

		if ( file_exists( $autoloader ) ) {
			include_once $autoloader;
		}

		// Backup the original command line arguments.
		$orig_cmd_args = $_SERVER['argv'];

		// Set the check arguments for PHPCS.
		$_SERVER['argv'] = $this->create_args( $result );

		// Run PHPCS.
		try {
			ob_start();
			$runner = new \PHP_CodeSniffer\Runner();
			$runner->runPHPCS();
			$reports = ob_get_clean();
		} catch ( Exception $e ) {
			$_SERVER['argv'] = $orig_cmd_args;
			throw $e;
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
				$result->add_message(
					strtoupper( $file_message['type'] ) === 'ERROR',
					$file_message['message'],
					array(
						'code'   => $file_message['source'],
						'file'   => $file_name,
						'line'   => $file_message['line'],
						'column' => $file_message['column'],
					)
				);
			}
		}
	}
}
