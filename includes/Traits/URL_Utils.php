<?php
/**
 * Trait WordPress\Plugin_Check\Traits\URL_Utils
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for URL utilities.
 *
 * @since 1.6.0
 */
trait URL_Utils {

	/**
	 * Checks if URL is valid.
	 *
	 * @since 1.6.0
	 *
	 * @param string $url URL.
	 * @return bool true if the URL is valid, otherwise false.
	 */
	protected function is_valid_url( string $url ): bool {
		// Parse the URL to validate its structure.
		$parsed_url = wp_parse_url( $url );

		// wp_parse_url returns false on parse failure.
		if ( false === $parsed_url || ! is_array( $parsed_url ) ) {
			return false;
		}

		// Must have a valid scheme and host.
		if ( empty( $parsed_url['scheme'] ) || empty( $parsed_url['host'] ) ) {
			return false;
		}

		// Scheme must be http or https (case-insensitive).
		if ( ! in_array( strtolower( $parsed_url['scheme'] ), array( 'http', 'https' ), true ) ) {
			return false;
		}

		// Validate host doesn't contain obviously invalid characters.
		// Allow alphanumeric, dots, hyphens, and underscores (for localhost, etc.).
		if ( preg_match( '/[^a-z0-9.\-_]/i', $parsed_url['host'] ) ) {
			return false;
		}

		// Detect duplicated protocol in the host/path portion (e.g., "https://http://example.com/").
		// Only check up to the query string to avoid false positives with URLs in query parameters.
		$query_position = strpos( $url, '?' );

		if ( false !== $query_position ) {
			$url_without_query = substr( $url, 0, $query_position );
		} else {
			$url_without_query = $url;
		}

		// Check for duplicated protocol after the scheme:// portion.
		$scheme_length = strlen( $parsed_url['scheme'] );
		$after_scheme  = substr( $url_without_query, $scheme_length + 3 );

		if ( str_contains( $after_scheme, '://' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Finds and returns the discouraged domain matched in the URL's host, or null if none.
	 *
	 * @since 1.6.0
	 *
	 * @param string $url The URL to check.
	 * @return string|null The matched discouraged domain, or null if none matched.
	 */
	protected function find_discouraged_domain( string $url ) {
		$discouraged_domains = $this->get_discouraged_domains();

		if ( empty( $discouraged_domains ) ) {
			return null;
		}

		$parsed_url = wp_parse_url( $url );

		if ( empty( $parsed_url['host'] ) ) {
			return null;
		}

		$host = strtolower( rtrim( $parsed_url['host'], '.' ) );

		foreach ( $discouraged_domains as $domain ) {
			$domain = strtolower( rtrim( $domain, '.' ) );
			if (
				$host === $domain ||
				( strlen( $host ) > strlen( $domain ) && substr( $host, -strlen( $domain ) - 1 ) === '.' . $domain )
			) {
				return $domain;
			}
		}

		return null;
	}

	/**
	 * Checks if URL has discouraged domain.
	 *
	 * @since 1.6.0
	 *
	 * @param string $url The URL to check.
	 * @return bool True if the URL has a discouraged domain, false otherwise.
	 */
	protected function has_discouraged_domain( $url ) {
		return null !== $this->find_discouraged_domain( $url );
	}

	/**
	 * Returns discouraged domains.
	 *
	 * @since 1.6.0
	 *
	 * @return array Discouraged domains.
	 */
	private function get_discouraged_domains() {
		$discouraged_domains = array(
			'example.com',
			'example.net',
			'example.org',
			'yourdomain.com',
			'yourwebsite.com',
		);

		/**
		 * Filter the list of discouraged domains.
		 *
		 * @since 1.6.0
		 *
		 * @param array $discouraged_domains Array of discouraged domains.
		 */
		$discouraged_domains = (array) apply_filters( 'wp_plugin_check_discouraged_domains', $discouraged_domains );

		return $discouraged_domains;
	}
}
