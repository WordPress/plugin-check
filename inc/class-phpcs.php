<?php
namespace WordPressdotorg\Plugin_Check;
use WP_Error;

/**
 * Class PHPCS
 *
 * A PHP wrapper for running the phpcs command line tool.
 *
 * @package WordPressdotorg\Plugin_Check
 */
class PHPCS {
	/**
	 * @var string The path to the phpcs script.
	 */
	protected $phpcs;

	/**
	 * @var PHP_CLI Instance of the PHP_CLI class.
	 */
	protected $php;

	/**
	 * @var string The name or path of the coding standard to use with phpcs.
	 */
	protected $standard = 'WordPress';

	/**
	 * @var string The state of the phpcs cache.
	 */
	protected $cache = 'disabled';

	/**
	 * @var string The path of the custom file that phpcs will use for caching.
	 */
	protected $cache_file;

	/**
	 * @var array Arguments for every phpcs call.
	 */
	protected $common_args = array(
		'q'            => true,
		'parallel'     => 3,
		'report-width' => 80,
		'tab-width'    => 4,
	);

	/**
	 * PHPCS constructor.
	 */
	public function __construct() {
		$this->phpcs = dirname( __DIR__ ) . '/vendor/squizlabs/php_codesniffer/bin/phpcs';
		if ( is_callable( 'get_temp_dir' ) ) {
			$this->cache_file = get_temp_dir() . 'wporg-code-analysis/phpcs-cache';
		} else {
			$this->cache_file = '/tmp/wporg-code-analysis/phpcs-cache';
		}

		$this->php = new PHP_CLI();
	}

	/**
	 * Set the name or path of the coding standard that will be used when running phpcs.
	 *
	 * @param string $standard The name or path of the coding standard to use.
	 *
	 * @return void
	 */
	public function set_standard( $standard ) {
		$this->standard = $standard;
	}

	/**
	 * Toggle the cache state to "enabled".
	 *
	 * @return void
	 */
	public function enable_cache() {
		$this->cache = 'enabled';
		$this->common_args['cache'] = $this->cache_file;
	}

	/**
	 * Toggle the cache state to "disabled".
	 *
	 * @return void
	 */
	public function disable_cache() {
		$this->cache = 'disabled';
		unset( $this->common_args['cache'] );
	}

	/**
	 * Delete the cache file.
	 *
	 * @return void
	 */
	public function clear_cache() {
		@unlink( $this->cache_file );
	}

	/**
	 * Run phpcs on a given path with a given set of arguments.
	 *
	 * The arguments array can take any of the arguments described in phpcs's Usage blurb, but there are some that
	 * should be set in other ways instead:
	 * - standard              This should be set using the `set_standard` class method instead. This makes it simpler
	 *                         to run phpcs on several different directories in the same session.
	 * - cache and no-cache    These should be set using the `enable_cache` and `disable_cache` class methods instead.
	 *                         As of v3.5.8 phpcs doesn't have a simple way to clear its own cache.
	 *                         (See https://github.com/squizlabs/PHP_CodeSniffer/issues/2993.)
	 *                         To get around this we set a custom cache file, which can then be deleted with the
	 *                         `clear_cache` class method.
	 *
	 * @param string $path The directory/file on which to to run phpcs.
	 * @param array  $args Command line args as key => value pairs. Valueless keys should be set to true.
	 *                     See https://github.com/squizlabs/PHP_CodeSniffer/wiki/Usage.
	 *                     Note that the `standard` arg should be set using the `set_standard` class method.
	 *
	 * @return string|WP_Error|null
	 */
	public function run( $path, array $args = array() ) {
		if ( ! file_exists( $this->phpcs ) ) {
			return new \WP_Error(
				'missing_dependency',
				'PHP Code Sniffer is not available. Try running <code>composer install</code> first.'
			);
		}

		if ( ! is_readable( $path ) ) {
			return new \WP_Error(
				'invalid_path',
				'The given path is not readable.'
			);
		}

		if ( isset( $args['cache'] ) ) {
			$this->enable_cache();
			unset( $args['cache'] );
		} elseif ( isset( $args['no-cache'] ) ) {
			$this->disable_cache();
		}

		$args = array_merge(
			array(
				'basepath' => $path,
				'standard' => $this->standard,
			),
			$this->common_args,
			$args
		);

		$arg_array = array();
		foreach ( $args as $key => $value ) {
			$prefix = '--';
			if ( 1 === strlen( $key ) || in_array( $key, array( 'vv', 'vvv' ), true ) ) {
				$prefix = '-';
			}

			if ( true === $value ) {
				$arg_array[] = "{$prefix}{$key}";
			} else {
				$arg_array[] = "{$prefix}{$key}={$value}";
			}
		}
		$arg_string = implode( ' ', $arg_array );

		$command = $this->php->get_cmd( "{$this->phpcs} $arg_string $path 2>&1" );
		$response = shell_exec( $command );

		if ( strpos( $response, 'No such file or directory' ) !== false ) {
			return new \WP_Error(
				'plugin_check_php_binary_not_found',
				__( 'Cannot find the PHP Binary file, please define it using the <code>`PLUGIN_CHECK_PHP_BIN`</code> constant', 'plugin-check' )
			);
		}

		return $response;
	}

	/**
	 * Get the summary.
	 *
	 * @param string $path The directory/file on which to to run phpcs.
	 * @param array  $args Command line args. See the `run` method.
	 *
	 * @return string|WP_Error|null
	 */
	public function run_summary_report( $path, array $args = array() ) {
		$args = array_merge(
			$args,
			array(
				'report' => 'summary',
			)
		);

		return $this->run( $path, $args );
	}

	/**
	 * Get the full report.
	 *
	 * @param string $path The directory/file on which to to run phpcs.
	 * @param array  $args Command line args. See the `run` method.
	 *
	 * @return string|WP_Error|null
	 */
	public function run_full_report( $path, array $args = array() ) {
		$args = array_merge(
			$args,
			array(
				'report' => 'full',
			)
		);

		return $this->run( $path, $args );
	}

	/**
	 * Get the full report as JSON.
	 *
	 * @param string $path   The directory/file on which to to run phpcs.
	 * @param array  $args   Command line args. See the `run` method.
	 * @param string $output The format of the return data. Possible values raw|object|array.
	 *                       "raw" means an un-decoded JSON string. Default raw.
	 *
	 * @return mixed|string|WP_Error|null
	 */
	public function run_json_report( $path, array $args = array(), $output = 'raw' ) {
		$args = array_merge(
			$args,
			array(
				'report' => 'json',
			)
		);

		$result = $this->run( $path, $args );

		if ( is_wp_error( $result ) ) {
			return new Error(
				$result->get_error_code(),
				$result->get_error_message()
			);
		}

		if ( in_array( $output, array( 'object', 'array' ), true ) ) {
			$assoc = 'array' === $output;
			$result = json_decode( $result, $assoc );
			// Does this belong here?
			if ( $result ) {
				array_walk_recursive( $result, function( &$value, $key ) {
					if ( is_string( $value ) ) {
						$value = stripcslashes( $value );
					}
				} );
			}
		}

		return $result;
	}
}
