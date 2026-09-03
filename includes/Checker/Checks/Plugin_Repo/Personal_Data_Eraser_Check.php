<?php
/**
 * Class Personal_Data_Eraser_Check.
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
 * Check to detect personal data handling without a registered eraser callback.
 *
 * Plugins that collect or store personal data are expected to register an
 * eraser callback via the `wp_privacy_personal_data_erasers` filter so that
 * WordPress's built-in Personal Data Removal tool can delete the plugin's
 * data on behalf of a user who submits a removal request.
 *
 * @since 2.0.0
 * @link https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-eraser-to-your-plugin/
 */
class Personal_Data_Eraser_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Experimental_Check;

	/**
	 * Functions that are strong indicators a plugin is storing personal data.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	const PERSONAL_DATA_FUNCTIONS = array(
		'add_user_meta',
		'update_user_meta',
		'add_comment_meta',
		'update_comment_meta',
	);

	/**
	 * $wpdb methods that write data and are strong indicators of personal data storage.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	const WPDB_METHODS = array(
		'insert',
		'update',
		'replace',
	);

	/**
	 * The filter used to register a personal data eraser.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const ERASER_FILTER = 'wp_privacy_personal_data_erasers';

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

		$this->check_for_missing_eraser( $result, $php_files );
	}

	/**
	 * Checks whether the plugin handles personal data but omits the eraser filter.
	 *
	 * The check is intentionally a two-step process:
	 * 1. Confirm the plugin has at least one personal-data storage call.
	 * 2. Only then verify whether it registers the eraser filter.
	 *
	 * This avoids false positives for plugins that do not touch personal data at all.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result $result    The check result to amend.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function check_for_missing_eraser( Check_Result $result, array $php_files ) {
		// Step 1: detect personal data signals across all plugin PHP files.
		$line        = 0;
		$signal_file = $this->find_file_with_personal_data_signal( $php_files, $line );

		if ( null === $signal_file ) {
			// No personal data handling detected — nothing to warn about.
			return;
		}

		// Step 2: check if the plugin already registers a personal data eraser.
		if ( $this->has_eraser_registration( $php_files ) ) {
			// Eraser is registered — no issue.
			return;
		}

		// Personal data is handled but no eraser is registered: emit a warning.
		$this->add_result_warning_for_file(
			$result,
			__( 'Personal data was detected in this plugin but no data eraser has been registered. Plugins that store personal data should implement a data eraser via the <code>wp_privacy_personal_data_erasers</code> filter so that site administrators can fulfill data removal requests.', 'plugin-check' ),
			'missing_personal_data_eraser',
			$signal_file,
			$line,
			0,
			'https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-eraser-to-your-plugin/',
			5
		);
	}

	/**
	 * Finds the first file that contains a personal data signal.
	 *
	 * @since 2.0.0
	 *
	 * @param array $php_files List of absolute PHP file paths.
	 * @param int   $line      Reference to store the line of the detected signal.
	 * @return string|null The file path, or null if no signal was found.
	 */
	private function find_file_with_personal_data_signal( array $php_files, int &$line ) {
		foreach ( $php_files as $file ) {
			$source = file_get_contents( $file );
			if ( false === $source || '' === $source ) {
				continue;
			}

			$tokens = token_get_all( $source );
			$count  = count( $tokens );

			for ( $i = 0; $i < $count; $i++ ) {
				if ( $this->is_personal_data_function_call( $tokens, $i ) || $this->is_wpdb_method_call( $tokens, $i ) ) {
					$line = (int) $tokens[ $i ][2];
					return $file;
				}
			}
		}

		return null;
	}

	/**
	 * Checks whether the plugin registers a personal data eraser.
	 *
	 * @since 2.0.0
	 *
	 * @param array $php_files List of absolute PHP file paths.
	 * @return bool True if an eraser is registered, false otherwise.
	 */
	private function has_eraser_registration( array $php_files ): bool {
		foreach ( $php_files as $file ) {
			$source = file_get_contents( $file );
			if ( false === $source || '' === $source ) {
				continue;
			}

			$tokens = token_get_all( $source );
			$count  = count( $tokens );

			for ( $i = 0; $i < $count; $i++ ) {
				if ( $this->is_eraser_filter_registration( $tokens, $i ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determines whether the token at the given index is a personal data function call.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Index of the token to inspect.
	 * @return bool True if the token is a personal data function call.
	 */
	private function is_personal_data_function_call( array $tokens, int $index ): bool {
		$token = $tokens[ $index ];

		if ( ! is_array( $token ) || T_STRING !== $token[0] ) {
			return false;
		}

		$name = strtolower( $token[1] );

		if ( ! in_array( $name, self::PERSONAL_DATA_FUNCTIONS, true ) ) {
			return false;
		}

		if ( ! $this->is_global_function_call( $tokens, $index ) ) {
			return false;
		}

		$next = $this->get_next_significant_token_index( $tokens, $index );

		return null !== $next && '(' === $tokens[ $next ];
	}

	/**
	 * Determines whether the token at the given index is a $wpdb write method call.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Index of the token to inspect.
	 * @return bool True if the token is a $wpdb write method call.
	 */
	private function is_wpdb_method_call( array $tokens, int $index ): bool {
		$token = $tokens[ $index ];

		if ( ! is_array( $token ) || T_VARIABLE !== $token[0] || '$wpdb' !== $token[1] ) {
			return false;
		}

		$arrow_index = $this->get_next_significant_token_index( $tokens, $index );

		if ( null === $arrow_index ) {
			return false;
		}

		$method_index = $this->get_next_significant_token_index( $tokens, $arrow_index );

		if ( null === $method_index ) {
			return false;
		}

		$arrow_token  = $tokens[ $arrow_index ];
		$method_token = $tokens[ $method_index ];

		if (
			! is_array( $arrow_token )
			|| T_OBJECT_OPERATOR !== $arrow_token[0]
			|| ! is_array( $method_token )
			|| T_STRING !== $method_token[0]
			|| ! in_array( strtolower( $method_token[1] ), self::WPDB_METHODS, true )
		) {
			return false;
		}

		// Confirm the method is actually invoked with a call.
		$call_index = $this->get_next_significant_token_index( $tokens, $method_index );

		return null !== $call_index && '(' === $tokens[ $call_index ];
	}

	/**
	 * Determines whether the token at the given index registers a personal data eraser.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Index of the token to inspect.
	 * @return bool True if the token registers a personal data eraser.
	 */
	private function is_eraser_filter_registration( array $tokens, int $index ): bool {
		$token = $tokens[ $index ];

		if ( ! is_array( $token ) || T_STRING !== $token[0] || 'add_filter' !== strtolower( $token[1] ) ) {
			return false;
		}

		if ( ! $this->is_global_function_call( $tokens, $index ) ) {
			return false;
		}

		$open_paren = $this->get_next_significant_token_index( $tokens, $index );

		if ( null === $open_paren || '(' !== $tokens[ $open_paren ] ) {
			return false;
		}

		$arg_index = $this->get_next_significant_token_index( $tokens, $open_paren );

		if ( null === $arg_index ) {
			return false;
		}

		$arg = $tokens[ $arg_index ];

		if ( ! is_array( $arg ) || T_CONSTANT_ENCAPSED_STRING !== $arg[0] ) {
			return false;
		}

		return trim( $arg[1], "\"' \t\n\r\0\x0B" ) === self::ERASER_FILTER;
	}

	/**
	 * Determines whether the token at the given index is a call to a global function.
	 *
	 * Excludes method calls, static calls, function declarations, and namespaced
	 * function calls.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Index of the token to inspect.
	 * @return bool True if the token is a global function call.
	 */
	private function is_global_function_call( array $tokens, int $index ): bool {
		$previous_index = $this->get_previous_significant_token_index( $tokens, $index );

		if ( null === $previous_index ) {
			return true;
		}

		$previous_token = $tokens[ $previous_index ];

		if ( ! is_array( $previous_token ) ) {
			return ! is_string( $previous_token ) || '(' !== $previous_token;
		}

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

		return true;
	}

	/**
	 * Gets the index of the next significant token, skipping whitespace and comments.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Index to start scanning from.
	 * @return int|null The next significant token index, or null if none exists.
	 */
	private function get_next_significant_token_index( array $tokens, int $index ): ?int {
		$count = count( $tokens );

		for ( $i = $index + 1; $i < $count; $i++ ) {
			$token = $tokens[ $i ];

			if ( is_array( $token ) && in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}

			return $i;
		}

		return null;
	}

	/**
	 * Gets the index of the previous significant token, skipping whitespace and comments.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Index to start scanning backwards from.
	 * @return int|null The previous significant token index, or null if none exists.
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
	 * Removes files under the plugin's top-level tests directory.
	 *
	 * The plugin's own test fixtures are not genuine personal data handling and
	 * should not trigger the check.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $php_files   List of absolute PHP file paths.
	 * @param string $plugin_path Absolute path to the plugin directory, with trailing slash.
	 * @return array Filtered list of absolute PHP file paths.
	 */
	private function filter_out_test_paths( array $php_files, string $plugin_path ): array {
		$root = wp_normalize_path( $plugin_path );

		return array_values(
			array_filter(
				$php_files,
				static function ( string $file ) use ( $root ): bool {
					$relative = str_replace( $root, '', wp_normalize_path( $file ) );

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
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 2.0.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects plugins that store personal data without registering a personal data eraser for GDPR compliance.', 'plugin-check' );
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
		return 'https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-eraser-to-your-plugin/';
	}
}
