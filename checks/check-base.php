<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};
use Exception;

abstract class Check_Base {
	public $args      = [];
	public $post      = null;
	public $readme    = null;
	public $headers   = null;
	public $path      = null;
	public $slug      = null;
	public $files     = [];

	public $errors = null;

	/**
	 * Run all the checks against various plugin.
	 *
	 * @param array $args {
	 *   @type WP_Post $post      The plugin post.
	 *   @type Parser  $readme    The plugin readme.
	 *   @type string  $file_path Path to the plugin file.
	 * }
	 *
	 * @return WP_Error The result of the checks.
	 */
	public static function run_checks( $args = [] ) {
		// Get all the checks from the current directory.
		$files = array_diff(
			glob( __DIR__ . '/*.php' ),
			[ __FILE__ ]
		);

		foreach ( $files as $file ) {
			include_once $file;
		}

		$plugin_checks = array_values(
			array_diff(
				preg_grep( '!^' . preg_quote( __NAMESPACE__, '!' ) . '!', get_declared_classes() ),
				[ __CLASS__ ]
			)
		);

		/**
		 * Filter the list of checks to run.
		 *
		 * @param array $plugin_checks The list of checks to run.
		 * @param array $args          The arguments passed to the checks.
		 */
		$plugin_checks = apply_filters( 'plugin_checks', $plugin_checks, $args );

		$check_results = [];
		foreach ( $plugin_checks as $checker ) {
			$check_results = array_merge(
				$check_results,
				( new $checker( $args ) )->errors
			);
		}

		return $check_results;
	}

	/**
	 * Private Constructor. See self::run_checks();
	 *
	 * @ignore
	 */
	protected function __construct( $args = [] ) {
		$this->errors = [];
		$this->args   = $args;

		foreach ( $args as $v => $k ) {
			if ( property_exists( $this, $v ) ) {
				$this->$v = $k;
			}
		}

		$methods = preg_grep( '/^check_/', get_class_methods( $this ) );

		foreach ( $methods as $check ) {
			$result = $this->$check();
			if ( false === $result ) {
				$result = new Error( "failed_{$check}", "Failed {$check}." );
			}

			if ( is_wp_error( $result ) ) {
				$this->errors[] = $result;
			} elseif ( is_array( $result ) && ! empty( $result ) && is_wp_error( $result[0] ) ) {
				$this->errors = array_merge( $this->errors, $result );
			}
		}
	}

	/*
	 * Shared helper fixtures.
	 */

	/**
	 * Scan all files for a matching needle.
	 *
	 * @param string $needle The needle to search for. May be regex by wrapping with #...#.
	 * @return bool|string False if not found, string match if found.
	 */
	function scan_files_for_needle( $needle ) {
		return self::scan_matching_files_for_needle( $needle, '' );
	}

	/**
	 * Scan matching files for a matching needle.
	 *
	 * @param string $needle The needle to search for. May be regex by wrapping with #...#.
	 * @param string $files  A regex to apply to the list of files to scan.
	 * @return bool|string False if not found, string match if found.
	 */
	function scan_matching_files_for_needle( $needle, $files = '' ) {
		$is_regex = str_starts_with( $needle, '#' ) && preg_match( '!^#.+#\w*$!', $needle );

		$matching_files = $this->files;
		if ( $files ) {
			$matching_files = preg_grep( '#' . $files . '#', $matching_files );
		}

		$path = $this->path;

		try {
			array_walk(
				$matching_files,
				function( $file ) use( $path, $needle, $is_regex ) {
					$contents = Check_Base::file_get_contents( $path . '/' . $file );
					if ( $is_regex ) {
						if ( preg_match( $needle, $contents, $m ) ) {
							throw new Exception( $m[0] );
						}
					} else {
						if ( str_contains( $contents, $needle ) ) {
							throw new Exception( $needle );
						}
					}
				}
			);
		} catch( Exception $e ) {
			return $e->getMessage();
		}

		return false;
	}

	/**
	 * A caching wrapper for file_get_contents().
	 *
	 * @param string $file The filename.
	 * @return string
	 */
	public static function file_get_contents( $file ) {
		static $cache = [];

		return $cache[ $file ] ?? $cache[ $file ] = file_get_contents( $file );
	}

	/**
	 * Check if the current installation is not a production environment.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	protected function is_not_production(): bool {
		return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || 'production' !== wp_get_environment_type();
	}

	/**
	 * Throw an error or a warning, based on the environment.
	 *
	 * @since 0.2.0
	 *
	 * @param string $slug The filename.
	 * @param string $message The message.
	 *
	 * @return Error|Notice
	 */
	protected function throw_notice( string $slug, string $message ) {
		$notice_or_error = $this->is_not_production() ? Notice::class : Error::class;
		return new $notice_or_error( $slug, $message );
	}
}
