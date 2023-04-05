<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

abstract class Check_Base {
	public $args      = [];
	public $post      = null;
	public $readme    = null;
	public $headers   = null;
	public $path      = null;
	public $slug      = null;

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
			} elseif ( is_array( $result ) && is_wp_error( $result[0] ) ) {
				$this->errors = array_merge( $this->errors, $result );
			}
		}
	}
}
