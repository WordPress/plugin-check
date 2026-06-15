<?php
/**
 * Class Privacy_Policy_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check that plugins handling personal data call wp_add_privacy_policy_content().
 *
 * Plugins that collect, use, store, or transmit personal data to a third party
 * are required by WordPress.org guidelines to suggest privacy policy text to site
 * administrators via wp_add_privacy_policy_content(). This check detects common
 * personal-data-handling patterns and warns if that function is not used.
 *
 * Uses token-based parsing to avoid false positives from function names appearing
 * in comments or string literals.
 *
 * @since 1.7.0
 */
class Privacy_Policy_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Function names that indicate a plugin may handle personal data.
	 *
	 * Each function name is accompanied by a human-readable label used in the
	 * warning message to help plugin authors understand why the check fired.
	 *
	 * @since 1.7.0
	 * @var array<string, string>
	 */
	const PERSONAL_DATA_FUNCTIONS = array(
		'wp_remote_post'     => 'wp_remote_post()',
		'wp_remote_get'      => 'wp_remote_get()',
		'setcookie'          => 'setcookie()',
		'wp_set_auth_cookie' => 'wp_set_auth_cookie()',
	);

	/**
	 * Variable names that indicate a plugin may handle personal data.
	 *
	 * @since 1.7.0
	 * @var array<string, string>
	 */
	const PERSONAL_DATA_VARIABLES = array(
		'$_COOKIE' => '$_COOKIE',
	);

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.7.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );

		if ( empty( $php_files ) ) {
			return;
		}

		// First, detect whether the plugin already calls wp_add_privacy_policy_content().
		$has_privacy_call = $this->find_function_in_files( $php_files, array( 'wp_add_privacy_policy_content' => 'wp_add_privacy_policy_content()' ) );

		// If the plugin already registers privacy policy content, nothing to warn about.
		if ( $has_privacy_call ) {
			return;
		}

		// Check for personal-data-handling function calls.
		$matched_label = $this->find_function_in_files( $php_files, self::PERSONAL_DATA_FUNCTIONS );

		// If no function call found, check for personal-data-handling variable usage.
		if ( ! $matched_label ) {
			$matched_label = $this->find_variable_in_files( $php_files, self::PERSONAL_DATA_VARIABLES );
		}

		if ( $matched_label ) {
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: %s: The detected function or variable name indicating personal data usage. */
					__( '<strong>Missing privacy policy content registration.</strong><br>The plugin uses %s which may involve handling personal data, but does not call wp_add_privacy_policy_content(). Plugins that collect, store, or transmit personal data should suggest privacy policy text to site administrators.', 'plugin-check' ),
					'<code>' . esc_html( $matched_label ) . '</code>'
				),
				'missing_privacy_policy_content',
				$result->plugin()->main_file(),
				0,
				0,
				'https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/',
				5
			);
		}
	}

	/**
	 * Searches PHP files for a global function call using token-based parsing.
	 *
	 * Uses token_get_all() to avoid false positives from function names appearing
	 * in comments or string literals.
	 *
	 * @since 1.7.0
	 *
	 * @param array                 $files           List of absolute file paths.
	 * @param array<string, string> $function_names Map of function names (lowercase) to human-readable labels.
	 * @return string|false Human-readable label of the first matched function, or false if none found.
	 */
	private function find_function_in_files( array $files, array $function_names ) {
		$lowercase_names = array();
		foreach ( $function_names as $name => $label ) {
			$lowercase_names[ strtolower( $name ) ] = $label;
		}

		foreach ( $files as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$source = file_get_contents( $file );
			if ( false === $source || '' === $source ) {
				continue;
			}

			$tokens = token_get_all( $source );

			foreach ( $tokens as $index => $token ) {
				if ( ! is_array( $token ) || T_STRING !== $token[0] ) {
					continue;
				}

				$name = strtolower( $token[1] );
				if ( ! isset( $lowercase_names[ $name ] ) ) {
					continue;
				}

				$next_index = $this->get_next_significant_token_index( $tokens, $index );
				if ( null === $next_index || '(' !== $tokens[ $next_index ] ) {
					continue;
				}

				if ( ! $this->is_global_function_call( $tokens, $index ) ) {
					continue;
				}

				return $lowercase_names[ $name ];
			}
		}

		return false;
	}

	/**
	 * Checks whether a tokenized T_STRING is a global function call.
	 *
	 * Rejects method calls (->method), static calls (Class::method),
	 * function definitions (function name), instantiation (new name),
	 * and namespace-qualified calls (\NS\name or NS\name).
	 *
	 * @since 1.7.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return bool True if the token represents a global function call.
	 */
	private function is_global_function_call( array $tokens, int $index ): bool {
		$previous_index = $this->get_previous_significant_token_index( $tokens, $index );
		if ( null === $previous_index ) {
			return true;
		}

		$previous_token = $tokens[ $previous_index ];

		if ( is_array( $previous_token ) ) {
			$disqualifying_tokens = array( T_FUNCTION, T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON );

			// T_NULLSAFE_OBJECT_OPERATOR (?->) only exists on PHP 8.0+.
			if ( defined( 'T_NULLSAFE_OBJECT_OPERATOR' ) ) {
				$disqualifying_tokens[] = constant( 'T_NULLSAFE_OBJECT_OPERATOR' );
			}

			if ( in_array( $previous_token[0], $disqualifying_tokens, true ) ) {
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
	 * Searches PHP files for a variable usage using token-based parsing.
	 *
	 * Uses token_get_all() to avoid false positives from variable names appearing
	 * in comments or string literals.
	 *
	 * @since 1.7.0
	 *
	 * @param array                 $files           List of absolute file paths.
	 * @param array<string, string> $variable_names  Map of variable names to human-readable labels.
	 * @return string|false Human-readable label of the first matched variable, or false if none found.
	 */
	private function find_variable_in_files( array $files, array $variable_names ) {
		foreach ( $files as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$source = file_get_contents( $file );
			if ( false === $source || '' === $source ) {
				continue;
			}

			$tokens = token_get_all( $source );

			foreach ( $tokens as $token ) {
				if ( ! is_array( $token ) || T_VARIABLE !== $token[0] ) {
					continue;
				}

				if ( isset( $variable_names[ $token[1] ] ) ) {
					return $variable_names[ $token[1] ];
				}
			}
		}

		return false;
	}

	/**
	 * Finds the next significant token index, skipping whitespace.
	 *
	 * @since 1.7.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return int|null Index of the next significant token, or null if end of stream.
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
	 * Finds the previous significant token index, skipping whitespace and comments.
	 *
	 * @since 1.7.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return int|null Index of the previous significant token, or null if start of stream.
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
	 * @since 1.7.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Checks that plugins handling personal data call wp_add_privacy_policy_content() to suggest privacy policy text to site administrators.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.7.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/', 'plugin-check' );
	}
}
