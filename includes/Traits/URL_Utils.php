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
		if ( filter_var( $url, FILTER_VALIDATE_URL ) !== $url || ! str_starts_with( $url, 'http' ) ) {
			return false;
		}

		// Detect duplicated protocol (e.g., "https://http://example.com/").
		$parsed_url = wp_parse_url( $url );

		if ( isset( $parsed_url['scheme'] ) && str_contains( substr( $url, strlen( $parsed_url['scheme'] ) + 3 ), '://' ) ) {
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
