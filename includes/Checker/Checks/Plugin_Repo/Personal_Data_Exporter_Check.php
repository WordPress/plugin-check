<?php
/**
 * Class Personal_Data_Exporter_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Experimental_Check;

/**
 * Check to detect personal data handling without a registered exporter callback.
 *
 * Plugins that collect or store personal data are expected to register an
 * exporter callback via the `wp_privacy_personal_data_exporters` filter so
 * that WordPress's built-in Personal Data Export tool can include the plugin's
 * data in the generated ZIP.
 *
 * This check is marked experimental because signals like `$wpdb->insert()` and
 * `update_user_meta()` are common for non-personal data (view counters, plugin
 * settings, caches) and may produce false positives on real plugins. Opt in
 * with `--include-experimental`.
 *
 * @since 2.0.0
 * @link https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-exporter-to-your-plugin/
 */
class Personal_Data_Exporter_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Experimental_Check;

	/**
	 * Personal-data storage function names that trigger the check.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const PERSONAL_DATA_FUNCTIONS = array(
		'add_user_meta',
		'update_user_meta',
		'add_comment_meta',
		'update_comment_meta',
	);

	/**
	 * `$wpdb` method names that trigger the check.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const WPDB_METHODS = array( 'insert', 'update', 'replace' );

	/**
	 * Filter name that registers a personal data exporter.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const EXPORTER_FILTER = 'wp_privacy_personal_data_exporters';

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 2.0.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );
		$php_files = $this->filter_out_test_paths( $php_files, $result->plugin()->path() );

		$this->check_for_missing_exporter( $result, $php_files );
	}

	/**
	 * Checks whether the plugin handles personal data but omits the exporter filter.
	 *
	 * The check is intentionally a two-step process:
	 * 1. Confirm the plugin has at least one personal-data storage call.
	 * 2. Only then verify whether it registers the exporter filter.
	 *
	 * This avoids false positives for plugins that do not touch personal data at all.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result $result    The check result to amend.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function check_for_missing_exporter( Check_Result $result, array $php_files ) {
		if ( empty( $php_files ) ) {
			return;
		}

		// Step 1: detect personal data signals across all plugin PHP files.
		$signal_file = $this->find_file_with_personal_data_signal( $php_files );

		if ( null === $signal_file ) {
			// No personal data handling detected — nothing to warn about.
			return;
		}

		// Step 2: check if the plugin already registers a personal data exporter.
		if ( $this->plugin_registers_exporter( $php_files ) ) {
			// Exporter is registered — no issue.
			return;
		}

		// Personal data is handled but no exporter is registered: emit a warning.
		$this->add_result_warning_for_file(
			$result,
			__( 'Personal data was detected in this plugin but no data exporter has been registered. Plugins that store personal data should implement a data exporter via the <code>wp_privacy_personal_data_exporters</code> filter so that site administrators can fulfill data export requests.', 'plugin-check' ),
			'missing_personal_data_exporter',
			$signal_file,
			0,
			0,
			'https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-exporter-to-your-plugin/',
			5
		);
	}

	/**
	 * Filters out files inside the plugin's own `tests/` subdirectory.
	 *
	 * The match is anchored to the plugin root so a host project's
	 * `tests/phpunit/...` test runner is not affected.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $php_files     Absolute file paths.
	 * @param string $plugin_root   Absolute path to the plugin's main file.
	 * @return array Filtered list.
	 */
	private function filter_out_test_paths( array $php_files, string $plugin_root ): array {
		$plugin_dir = wp_normalize_path( dirname( $plugin_root ) );

		return array_values(
			array_filter(
				$php_files,
				static function ( string $file ) use ( $plugin_dir ): bool {
					$normalized = wp_normalize_path( $file );

					// File must be inside the plugin directory to be subject to filtering.
					if ( 0 !== strpos( $normalized, $plugin_dir . '/' ) ) {
						return true;
					}

					$relative = substr( $normalized, strlen( $plugin_dir ) + 1 );

					// Skip top-level "tests" or "tests/anything" inside the plugin.
					if ( 0 === strpos( $relative, 'tests' ) && ( strlen( $relative ) === 5 || '/' === $relative[5] ) ) {
						return false;
					}

					return true;
				}
			)
		);
	}

	/**
	 * Returns the first file containing a personal-data storage call, or null.
	 *
	 * Uses `token_get_all()` to skip comments and ensure the call is a real
	 * function/variable call rather than text inside a string literal or
	 * docblock.
	 *
	 * @since 2.0.0
	 *
	 * @param array $php_files Absolute file paths.
	 * @return string|null First matching file path, or null if none found.
	 */
	private function find_file_with_personal_data_signal( array $php_files ): ?string {
		$personal_data_functions = array_map( 'strtolower', self::PERSONAL_DATA_FUNCTIONS );
		$wpdb_methods            = array_map( 'strtolower', self::WPDB_METHODS );

		foreach ( $php_files as $file ) {
			$source = file_get_contents( $file );
			if ( false === $source || '' === $source ) {
				continue;
			}

			$tokens = token_get_all( $source );
			$count  = count( $tokens );

			for ( $i = 0; $i < $count; $i++ ) {
				$token = $tokens[ $i ];

				// Match `add_user_meta(`, `update_user_meta(`, etc.
				if ( is_array( $token ) && T_STRING === $token[0] ) {
					$name = strtolower( $token[1] );
					if ( ! in_array( $name, $personal_data_functions, true ) ) {
						continue;
					}
					if ( ! $this->is_global_function_call( $tokens, $i ) ) {
						continue;
					}
					$next = $this->get_next_significant_token_index( $tokens, $i );
					if ( null === $next || '(' !== $tokens[ $next ] ) {
						continue;
					}
					return $file;
				}

				// Match `$wpdb->insert(`, `$wpdb->update(`, etc.
				if ( is_array( $token ) && T_VARIABLE === $token[0] && '$wpdb' === $token[1] ) {
					$next = $this->get_next_significant_token_index( $tokens, $i );
					if ( null === $next ) {
						continue;
					}
					$next_token = $tokens[ $next ];
					if ( ! is_array( $next_token ) || T_OBJECT_OPERATOR !== $next_token[0] ) {
						continue;
					}
					$method_index = $this->get_next_significant_token_index( $tokens, $next );
					if ( null === $method_index ) {
						continue;
					}
					$method_token = $tokens[ $method_index ];
					if ( ! is_array( $method_token ) || T_STRING !== $method_token[0] ) {
						continue;
					}
					if ( in_array( strtolower( $method_token[1] ), $wpdb_methods, true ) ) {
						return $file;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Determines whether any of the given files register the personal data exporter.
	 *
	 * Looks for `add_filter( 'wp_privacy_personal_data_exporters', … )` using
	 * the PHP tokenizer to avoid matching commented-out registrations.
	 *
	 * @since 2.0.0
	 *
	 * @param array $php_files Absolute file paths.
	 * @return bool True if the filter is registered.
	 */
	private function plugin_registers_exporter( array $php_files ): bool {
		$target_filter = self::EXPORTER_FILTER;

		foreach ( $php_files as $file ) {
			$source = file_get_contents( $file );
			if ( false === $source || '' === $source ) {
				continue;
			}

			$tokens = token_get_all( $source );
			$count  = count( $tokens );

			for ( $i = 0; $i < $count; $i++ ) {
				$token = $tokens[ $i ];
				if ( ! is_array( $token ) || T_STRING !== $token[0] ) {
					continue;
				}
				if ( 'add_filter' !== strtolower( $token[1] ) ) {
					continue;
				}
				if ( ! $this->is_global_function_call( $tokens, $i ) ) {
					continue;
				}

				$open_paren = $this->get_next_significant_token_index( $tokens, $i );
				if ( null === $open_paren || '(' !== $tokens[ $open_paren ] ) {
					continue;
				}

				$arg_index = $this->get_next_significant_token_index( $tokens, $open_paren );
				if ( null === $arg_index ) {
					continue;
				}

				$arg = $tokens[ $arg_index ];
				if ( ! is_array( $arg ) || T_CONSTANT_ENCAPSED_STRING !== $arg[0] ) {
					continue;
				}

				if ( trim( $arg[1], "\"' \t\n\r\0\x0B" ) === $target_filter ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks whether a tokenized T_STRING is a global function call.
	 *
	 * Mirrors the helper from WP_Functions_Compatibility_Check — excludes
	 * method calls (`->method`), static calls (`Class::method`), and
	 * namespaced calls (`\My\function`).
	 *
	 * @since 2.0.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return bool
	 */
	private function is_global_function_call( array $tokens, int $index ): bool {
		$previous_index = $this->get_previous_significant_token_index( $tokens, $index );
		if ( null === $previous_index ) {
			return true;
		}

		$previous_token = $tokens[ $previous_index ];

		if ( is_array( $previous_token ) ) {
			if ( in_array( $previous_token[0], array( T_FUNCTION, T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON ), true ) ) {
				return false;
			}

			if ( T_NS_SEPARATOR === $previous_token[0] ) {
				$before_namespace_index = $this->get_previous_significant_token_index( $tokens, $previous_index );
				if ( null === $before_namespace_index ) {
					return true;
				}

				$before_namespace_token = $tokens[ $before_namespace_index ];
				if ( is_array( $before_namespace_token ) && in_array( $before_namespace_token[0], array( T_STRING, T_NAMESPACE ), true ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Finds the next significant token index.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return int|null
	 */
	private function get_next_significant_token_index( array $tokens, int $index ): ?int {
		$count = count( $tokens );
		for ( $i = $index + 1; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( is_array( $token ) && T_WHITESPACE === $token[0] ) {
				continue;
			}

			return $i;
		}

		return null;
	}

	/**
	 * Finds the previous significant token index.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return int|null
	 */
	private function get_previous_significant_token_index( array $tokens, int $index ): ?int {
		for ( $i = $index - 1; $i >= 0; $i-- ) {
			$token = $tokens[ $i ];

			if ( is_array( $token ) && in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}

			return $i;
		}

		return null;
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 2.0.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects plugins that store personal data without registering a personal data exporter for GDPR compliance.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 2.0.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return 'https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-exporter-to-your-plugin/';
	}
}
