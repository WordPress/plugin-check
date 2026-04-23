<?php
/**
 * Class WP_Functions_Compatibility_Check.
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
 * Checks whether WordPress function usage is compatible with the plugin's minimum supported WordPress version.
 *
 * @since 2.0.0
 */
class WP_Functions_Compatibility_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Path to WordPress function since data file.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private const DATA_FILE = __DIR__ . '/../../../Vars/wp-functions-since.json';

	/**
	 * Cached dataset.
	 *
	 * @since 2.0.0
	 * @var array<string, string>|null
	 */
	private static $functions_since_map = null;

	/**
	 * Gets the categories for the check.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Runs the check on all plugin PHP files.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result $result The check result to amend.
	 * @param array        $files  Plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$functions_since_map = $this->get_functions_since_map();
		if ( empty( $functions_since_map ) ) {
			return;
		}

		$minimum_supported_wp = $this->get_minimum_supported_wp_version( $result );
		if ( '' === $minimum_supported_wp ) {
			return;
		}

		$php_files = self::filter_files_by_extension( $files, 'php' );
		foreach ( $php_files as $file ) {
			foreach ( $this->find_wp_function_calls( $file ) as $call ) {
				$function_name = $call['name'];

				if ( ! isset( $functions_since_map[ $function_name ] ) ) {
					continue;
				}

				$introduced_in = $functions_since_map[ $function_name ];
				if ( ! version_compare( $introduced_in, $minimum_supported_wp, '>' ) ) {
					continue;
				}

				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: 1: Function name, 2: Function introduced in version, 3: Minimum supported WordPress version */
						__( 'Function "%1$s()" requires WordPress %2$s, but your plugin minimum supported version is WordPress %3$s.', 'plugin-check' ),
						esc_html( $function_name ),
						esc_html( $introduced_in ),
						esc_html( $minimum_supported_wp )
					),
					'wp_function_not_compatible_with_requires_wp',
					$file,
					$call['line'],
					0,
					'https://developer.wordpress.org/reference/'
				);
			}
		}
	}

	/**
	 * Gets the check description.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Checks whether WordPress functions used by the plugin are compatible with its minimum supported WordPress version.', 'plugin-check' );
	}

	/**
	 * Gets the check documentation URL.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields', 'plugin-check' );
	}

	/**
	 * Loads and caches the WordPress function since dataset.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, string>
	 */
	private function get_functions_since_map(): array {
		if ( is_array( self::$functions_since_map ) ) {
			return self::$functions_since_map;
		}

		$raw_map                   = $this->load_functions_since_raw_map();
		self::$functions_since_map = $this->normalize_functions_since_map( $raw_map );
		return self::$functions_since_map;
	}

	/**
	 * Loads the raw function-since map from the JSON dataset file.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, string>
	 */
	private function load_functions_since_raw_map(): array {
		if ( ! is_readable( self::DATA_FILE ) ) {
			return array();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw_json = file_get_contents( self::DATA_FILE );
		if ( false === $raw_json || '' === $raw_json ) {
			return array();
		}

		$decoded = json_decode( $raw_json, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$map = $decoded['function_since'] ?? $decoded;
		return is_array( $map ) ? $map : array();
	}

	/**
	 * Normalizes raw function-since map values.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_map Raw function map from the dataset.
	 * @return array<string, string>
	 */
	private function normalize_functions_since_map( array $raw_map ): array {
		$normalized_map = array();

		foreach ( $raw_map as $function_name => $since_version ) {
			if ( ! is_string( $function_name ) || ! is_string( $since_version ) ) {
				continue;
			}

			$normalized_version = $this->normalize_wp_version( $since_version );
			if ( '' === $normalized_version ) {
				continue;
			}

			$normalized_map[ strtolower( $function_name ) ] = $normalized_version;
		}

		return $normalized_map;
	}

	/**
	 * Gets plugin minimum supported WordPress version.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result $result The check result.
	 * @return string
	 */
	private function get_minimum_supported_wp_version( Check_Result $result ): string {
		return $this->normalize_wp_version( $result->plugin()->minimum_supported_wp() );
	}

	/**
	 * Normalizes a WordPress version string.
	 *
	 * @since 2.0.0
	 *
	 * @param string $version Version to normalize.
	 * @return string
	 */
	private function normalize_wp_version( string $version ): string {
		if ( '' === $version ) {
			return '';
		}

		$normalized = strtok( trim( $version ), '-' );
		if ( ! preg_match( '/^\d+(?:\.\d+){1,2}$/', (string) $normalized ) ) {
			return '';
		}

		$parts = array_map(
			'intval',
			explode( '.', (string) $normalized )
		);

		while ( count( $parts ) < 3 ) {
			$parts[] = 0;
		}

		return implode( '.', array_slice( $parts, 0, 3 ) );
	}

	/**
	 * Finds function calls in a PHP file.
	 *
	 * @since 2.0.0
	 *
	 * @param string $file Absolute file path.
	 * @return array<int, array{name: string, line: int}>
	 */
	private function find_wp_function_calls( string $file ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$source = file_get_contents( $file );
		if ( false === $source || '' === $source ) {
			return array();
		}

		$tokens = token_get_all( $source );
		$calls  = array();

		foreach ( $tokens as $index => $token ) {
			if ( ! is_array( $token ) || T_STRING !== $token[0] ) {
				continue;
			}

			$next_index = $this->get_next_significant_token_index( $tokens, $index );
			if ( null === $next_index || '(' !== $tokens[ $next_index ] ) {
				continue;
			}

			if ( ! $this->is_global_function_call( $tokens, $index ) ) {
				continue;
			}

			$calls[] = array(
				'name' => strtolower( $token[1] ),
				'line' => (int) $token[2],
			);
		}

		return $calls;
	}

	/**
	 * Checks whether a tokenized T_STRING is a global function call.
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
}
