<?php
namespace WordPressdotorg\Plugin_Check;

use WP_Error;

/**
 * Class PHPCS_Runner
 *
 * @since   0.2.2
 *
 * @package WordPressdotorg\Plugin_Check
 */
class PHPCS_Runner {

	/**
	 * List of allowed PHPCS arguments.
	 *
	 * @since 0.2.2
	 *
	 * @var array
	 */
	protected array $allowed_args = [
		'standard' => true,
		'extensions' => true,
		'sniffs' => true,
		'exclude' => true, //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
	];

	/**
	 * Plugin path which will be used for the runner.
	 *
	 * @since 0.2.2
	 *
	 * @var string
	 */
	protected string $path;

	/**
	 * Which standard file we will use.
	 *
	 * @since 0.2.2
	 *
	 * @var string
	 */
	protected string $standard;

	/**
	 * Sets the plugin path which will be used for the runner.
	 *
	 * @since 0.2.2
	 *
	 * @param string $path The plugin path.
	 *
	 * @return void
	 */
	public function set_path( string $path ): void {
		$this->path = $path;
	}

	/**
	 * Gets the path for the plugin.
	 *
	 * @since 0.2.2
	 *
	 * @return string
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Sets the standards file which will be used for the runner.
	 * Normally this will be a .xml file.
	 *
	 * @since 0.2.2
	 *
	 * @param string $standard
	 *
	 * @return void
	 */
	public function set_standard( string $standard ): void {
		$this->standard = $standard;
	}

	/**
	 * Gets the standard file for the runner.
	 *
	 * @since 0.2.2
	 *
	 * @return string
	 */
	public function get_standard(): string {
		return $this->standard;
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 0.2.2
	 *
	 * @return array {
	 *    An associative array of PHPCS CLI arguments. Can include one or more of the following options.
	 *
	 * @type string $standard   The name or path to the coding standard to check against.
	 * @type string $extensions A comma separated list of file extensions to check against.
	 * @type string $sniffs     A comma separated list of sniff codes to include from checks.
	 * @type string $exclude    A comma separated list of sniff codes to exclude from checks.
	 *                          }
	 */
	protected function get_args(): array {
		return [
			'extensions' => 'php',
			'standard' => $this->get_standard(),
		];
	}

	/**
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @since 0.2.2
	 *
	 * @return string|WP_Error|null
	 */
	public function run() {
		// Backup the original command line arguments.
		$orig_cmd_args = $_SERVER['argv'];

		// Create the default arguments for PHPCS.
		$defaults = [
			'',
			$this->get_path(),
			'--report=Json',
			'--report-width=9999',
		];

		// Set the check arguments for PHPCS.
		$_SERVER['argv'] = $this->parse_argv( $this->get_args(), $defaults );

		// Reset PHP_CodeSniffer config.
		$this->reset_php_codesniffer_config();

		// Run PHPCS.
		try {
			ob_start();
			$runner = new \PHP_CodeSniffer\Runner();
			$runner->runPHPCS();
			$reports = ob_get_clean();
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'plugin_check_no_php_files_found',
				esc_html__( 'PHP Code Sniffer cannot be completed.', 'plugin-check' ),
				[
					'error_code' => $e->getCode(),
					'error_message' => $e->getMessage(),
				]
			);
		}

		// Restore original arguments.
		$_SERVER['argv'] = $orig_cmd_args;

		// Parse the reports into data to add to the overall $result.
		$reports = json_decode( trim( $reports ), true );

		if ( empty( $reports['files'] ) ) {
			return new \WP_Error(
				'plugin_check_no_php_files_found',
				esc_html__( 'Cannot find any PHP file to check, make sure your plugin contains PHP files.', 'plugin-check' )
			);
		}

		$base_dir    = trailingslashit( basename( $this->get_path() ) );
		$plugin_path = $this->get_path();

		$files_paths  = array_map( static function ( $file_path ) use ( $base_dir, $plugin_path ) {
			return str_replace( $plugin_path, $base_dir, $file_path );
		}, array_keys( $reports['files'] ) );
		$files_values = array_values( $reports['files'] );

		$reports['files'] = array_combine( $files_paths, $files_values );

		return $reports;
	}

	/**
	 * Parse the command arguments.
	 *
	 * @since 0.2.2
	 *
	 * @param array $argv     An array of arguments to pass.
	 * @param array $defaults An array of default arguments.
	 *
	 * @return array An indexed array of PHPCS CLI arguments.
	 */
	protected function parse_argv( $argv, $defaults ): array {
		// Only accept allowed PHPCS arguments from check arguments array.
		$check_args = array_intersect_key( $argv, $this->allowed_args );

		// Format check arguments for PHPCS.
		foreach ( $check_args as $key => $value ) {
			$defaults[] = "--{$key}=$value";
		}

		return $defaults;
	}

	/**
	 * Resets \PHP_CodeSniffer\Config::$overriddenDefaults to prevent
	 * incorrect results when running multiple checks.
	 *
	 * @since 0.2.2
	 */
	protected function reset_php_codesniffer_config(): void {
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
			$overridden_defaults->setValue( [] );
			$overridden_defaults->setAccessible( false );
		}
	}
}