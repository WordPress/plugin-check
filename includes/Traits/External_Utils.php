<?php
/**
 * Trait WordPress\Plugin_Check\Traits\External_Utils
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for find files php,js,css.
 *
 * @since 1.0.0
 */
trait External_Utils {
	/**
	 * Load domains mentioned in readme file.
	 *
	 * @since 1.4.0
	 *
	 * @param string $readme_file        Readme file path.
	 * @param array  $existing_tld_names Existing TLD names.
	 * @return array An array containing domains mentioned in readme file.
	 */
	protected function load_domains_mentioned_in_readme( $readme_file, $existing_tld_names ) {
		$lines             = file( $readme_file );
		$domains_mentioned = array();

		if ( empty( $lines ) ) {
			return $domains_mentioned;
		}

		$urls = $this->extract_urls_from_readme_lines( $lines );
		if ( empty( $urls ) ) {
			return $domains_mentioned;
		}

		$domains_mentioned = $this->process_urls_for_domains( $urls, $existing_tld_names );
		$domains_mentioned = $this->cleanup_domain_urls( $domains_mentioned );

		return $domains_mentioned;
	}

	/**
	 * Extract URLs from readme file lines.
	 *
	 * @since 1.8.0
	 *
	 * @param array $lines Lines from readme file.
	 * @return array Array of unique URLs.
	 */
	private function extract_urls_from_readme_lines( $lines ) {
		$urls = array();
		foreach ( $lines as $line ) {
			preg_match_all( '/@?(https?:\/\/)?(www\.)?[-a-zA-Z0-9:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9(:%_\+~#?&\/=]*)/', $line, $result );
			foreach ( $result[0] as $url ) {
				$url = strtolower( $url );
				// Remove domains in email addresses.
				if ( ! str_starts_with( $url, '@' ) ) {
					// Add protocol if domain taken without protocol.
					if ( ! str_starts_with( $url, 'http' ) ) {
						$url = 'http://' . $url;
					}
					$urls[] = $url;
				}
			}
		}
		return array_unique( $urls );
	}

	/**
	 * Process URLs to extract domains.
	 *
	 * @since 1.8.0
	 *
	 * @param array $urls               Array of URLs.
	 * @param array $existing_tld_names Array of existing TLD names.
	 * @return array Array of domain information.
	 */
	private function process_urls_for_domains( $urls, $existing_tld_names ) {
		$domains_mentioned = array();

		foreach ( $urls as $url ) {
			$parsed_url = parse_url( $url );
			if ( false === $parsed_url || empty( $parsed_url['host'] ) ) {
				continue;
			}

			$path = ! empty( $parsed_url['path'] ) ? $parsed_url['path'] : '';
			$this->extract_domain_from_url( $url, $parsed_url['host'], $path, $existing_tld_names, $domains_mentioned );
		}

		return $domains_mentioned;
	}

	/**
	 * Extract domain from URL and add to domains array.
	 *
	 * @since 1.8.0
	 *
	 * @param string $url               The URL.
	 * @param string $host              The host from parsed URL.
	 * @param string $path              The path from parsed URL.
	 * @param array  $existing_tld_names Array of existing TLD names.
	 * @param array  &$domains_mentioned Reference to domains array to update.
	 */
	private function extract_domain_from_url( $url, $host, $path, $existing_tld_names, &$domains_mentioned ) {
		preg_match_all( '/(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]/', $url, $result );

		foreach ( $result[0] as $domain ) {
			$domain = strtolower( $domain );

			if ( $this->is_invalid_domain( $domain ) ) {
				continue;
			}

			$domain_tld = $this->get_longest_matching_tld( $host, $existing_tld_names );
			if ( empty( $domain_tld ) ) {
				continue;
			}

			$domain = $this->extract_root_domain( $host, $domain_tld );
			$this->add_or_update_domain( $domain, $url, $path, $domains_mentioned );
		}
	}

	/**
	 * Check if domain is invalid (numeric TLD or file extension).
	 *
	 * @since 1.8.0
	 *
	 * @param string $domain Domain to check.
	 * @return bool True if invalid, false otherwise.
	 */
	private function is_invalid_domain( $domain ) {
		$typical_off_loading_extensions = array( 'css', 'svg', 'jpg', 'jpeg', 'gif', 'png', 'webm', 'mp4', 'mpg', 'mpeg', 'mp3' );
		$domain_elements                = explode( '.', $domain );
		$tld                            = end( $domain_elements );

		// Invalid TLD, numeric, looks like detected a version.
		if ( $tld === (int) $tld ) {
			return true;
		}

		// Invalid, looks like detected a file.
		return in_array(
			$tld,
			array_merge( $typical_off_loading_extensions, array( 'php', 'html', 'zip' ) ),
			true
		);
	}

	/**
	 * Get the longest matching TLD from host.
	 *
	 * @since 1.8.0
	 *
	 * @param string $host              The host.
	 * @param array  $existing_tld_names Array of existing TLD names.
	 * @return string The longest matching TLD or empty string.
	 */
	private function get_longest_matching_tld( $host, $existing_tld_names ) {
		$domain_tld = '';
		foreach ( $existing_tld_names as $tld ) {
			if ( str_ends_with( $host, $tld ) && strlen( $tld ) > strlen( $domain_tld ) ) {
				$domain_tld = $tld;
			}
		}
		return $domain_tld;
	}

	/**
	 * Extract root domain from host and TLD.
	 *
	 * @since 1.8.0
	 *
	 * @param string $host       The host.
	 * @param string $domain_tld The TLD.
	 * @return string The root domain.
	 */
	private function extract_root_domain( $host, $domain_tld ) {
		$domain = str_replace( '.' . $domain_tld, '', $host );
		$parts  = explode( '.', $domain );
		return end( $parts ) . '.' . $domain_tld;
	}

	/**
	 * Add or update domain in domains array.
	 *
	 * @since 1.8.0
	 *
	 * @param string $domain            The domain.
	 * @param string $url               The URL.
	 * @param string $path              The path.
	 * @param array  &$domains_mentioned Reference to domains array to update.
	 */
	private function add_or_update_domain( $domain, $url, $path, &$domains_mentioned ) {
		$key = $this->get_key_domain_mentioned_in_readme( $domain );

		if ( false !== $key ) {
			// Domain exists, add URL and path.
			$domains_mentioned[ $key ]['urls'][] = $url;
			if ( ! empty( $path ) ) {
				$domains_mentioned[ $key ]['paths'][] = $path;
			}
		} else {
			// Create new domain entry.
			$domain_mentioned    = array(
				'domains' => $this->add_domains_of_same_service( $domain ),
				'urls'    => array( $url ),
				'paths'   => ! empty( $path ) ? array( $path ) : array(),
			);
			$domains_mentioned[] = $domain_mentioned;
		}
	}

	/**
	 * Cleanup domain URLs by removing duplicates.
	 *
	 * @since 1.8.0
	 *
	 * @param array $domains_mentioned Array of domain information.
	 * @return array Cleaned array.
	 */
	private function cleanup_domain_urls( $domains_mentioned ) {
		if ( empty( $domains_mentioned ) ) {
			return $domains_mentioned;
		}

		return array_map(
			function ( $domain ) {
				$domain['urls'] = array_unique( $domain['urls'] );
				return $domain;
			},
			$domains_mentioned
		);
	}

	/**
	 * Get key domain mentioned in readme file.
	 *
	 * @since 1.4.0
	 *
	 * @param string $domain_search Domain search string.
	 * @return string|int|bool Key of domain mentioned in readme file, or false if not found.
	 */
	protected function get_key_domain_mentioned_in_readme( $domain_search ) {
		if ( ! empty( $this->domains_mentioned_readme ) ) {
			foreach ( $this->domains_mentioned_readme as $key => $domains ) {
				if ( ! empty( $domains['domains'] ) ) {
					foreach ( $domains['domains'] as $domain ) {
						if ( str_contains( $domain_search, $domain ) || str_contains( $domain, $domain_search ) ) {
							return $key;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Add domains of the same service.
	 *
	 * @since 1.4.0
	 *
	 * @param string $domain Domain.
	 * @return array An array containing domains of the same service.
	 */
	protected function add_domains_of_same_service( $domain ) {
		$domains                     = array( $domain );
		$domains_of_the_same_service = array(
			'paypal.com'    => array( 'paypal.com', 'paypalobjects.com' ),
			'google.com'    => array( 'google.com', 'googleapis.com', 'googletagmanager.com' ),
			'microsoft.com' => array( 'microsoft.com', 'outlook.com', 'live.com' ),
			'atlassian.net' => array( 'atlassian.com', 'trello.com' ),
			'dropbox.com'   => array( 'dropbox.com', 'dropboxapi.com' ),
			'tiktok.com'    => array( 'tiktok.com', 'tiktokapis.com' ),
			'zendesk.com'   => array( 'zendesk.com', 'zdassets.com' ),
		);
		foreach ( $domains_of_the_same_service as $key => $service ) {
			foreach ( $service as $service_domain ) {
				if ( $service_domain === $domain ) {
					$domains = array_merge( $domains, $domains_of_the_same_service[ $key ] );
					$domains = array_unique( $domains );
				}
			}
		}

		return $domains;
	}

	/**
	 * Check if domain is mentioned in readme file.
	 *
	 * @since 1.4.0
	 *
	 * @param string $domain Domain.
	 * @return bool True if domain is mentioned in readme file, false otherwise.
	 */
	protected function is_domain_mentioned_in_readme( $domain ) {
		$key = $this->get_key_domain_mentioned_in_readme( $domain );
		if ( false !== $key ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if domain is documented in readme file.
	 *
	 * @since 1.4.0
	 *
	 * @param string $domain Domain.
	 * @return bool True if domain is documented in readme file, false otherwise.
	 */
	protected function is_domain_documented_readme( $domain ) {
		$key     = $this->get_key_domain_mentioned_in_readme( $domain );
		$privacy = false;
		$terms   = false;

		if ( ! empty( $this->domains_mentioned_readme[ $key ]['paths'] ) ) {
			foreach ( $this->domains_mentioned_readme[ $key ]['paths'] as $path ) {
				foreach ( $this->privacy_common_uris_paths as $privacy_str ) {
					if ( str_contains( $path, $privacy_str ) ) {
						$privacy = $path;
						break;
					}
				}
				foreach ( $this->terms_common_uris_paths as $terms_str ) {
					if ( str_contains( $path, $terms_str ) ) {
						$terms = $path;
						break;
					}
				}
			}
		}

		if ( $privacy || $terms ) { // To lower down false positives while keeping the check we are ok to have just one of them.
			return true;
		}

		return false;
	}

	/**
	 * Common privacy URI paths.
	 *
	 * @since 1.4.0
	 *
	 * @var array
	 */
	private $privacy_common_uris_paths = array( 'privacy', 'legal' );

	/**
	 * Common terms URI paths.
	 *
	 * @since 1.4.0
	 *
	 * @var array
	 */
	private $terms_common_uris_paths = array( 'terms', 'tos', 'conditions', 'legal' );

	/**
	 * Domains mentioned in readme.
	 *
	 * @since 1.4.0
	 *
	 * @var array
	 */
	private $domains_mentioned_readme = array();

	/**
	 * Find external domains in a file.
	 *
	 * @since 1.4.0
	 *
	 * @param string $file File path.
	 * @return array Array of domains found in the file.
	 */
	protected function find_external_domains_in_file( $file ) {
		$domains = array();

		// Skip if file doesn't exist or is not readable.
		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return $domains;
		}

		$content = file_get_contents( $file );
		if ( false === $content ) {
			return $domains;
		}

		// Skip plugin header section in PHP files to avoid flagging metadata URLs.
		$extension = pathinfo( $file, PATHINFO_EXTENSION );
		if ( 'php' === $extension ) {
			// Remove the plugin header block (first DocBlock in the file).
			$content = preg_replace( '#^<\?php\s*/\*\*.*?\*/\s*#s', '', $content, 1 );
		}

		// Pattern to match URLs in function calls that indicate actual service usage.
		$service_patterns = array(
			// Remote HTTP functions.
			'#(?:wp_remote_get|wp_remote_post|wp_remote_request|wp_safe_remote_get|wp_safe_remote_post|wp_safe_remote_request|file_get_contents|fopen|curl_init)\s*\(\s*["\'](?:https?:)?//([a-zA-Z0-9][-a-zA-Z0-9]*(?:\.[a-zA-Z0-9][-a-zA-Z0-9]*)+)[^"\']*["\']#i',
			// Enqueue functions.
			'#(?:wp_enqueue_script|wp_register_script|wp_enqueue_style|wp_register_style)\s*\([^,]+,\s*["\'](?:https?:)?//([a-zA-Z0-9][-a-zA-Z0-9]*(?:\.[a-zA-Z0-9][-a-zA-Z0-9]*)+)[^"\']*["\']#i',
			// JavaScript fetch.
			'#fetch\s*\(\s*["\'](?:https?:)?//([a-zA-Z0-9][-a-zA-Z0-9]*(?:\.[a-zA-Z0-9][-a-zA-Z0-9]*)+)[^"\']*["\']#i',
		);

		foreach ( $service_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[1] as $domain ) {
					$domain = strtolower( trim( $domain ) );

					// Skip common WordPress and localhost domains.
					if ( $this->is_common_wordpress_domain( $domain ) ) {
						continue;
					}

					if ( $this->is_localhost_domain( $domain ) ) {
						continue;
					}

					if ( $this->is_known_safe_domain( $domain ) ) {
						continue;
					}

					$domains[] = $domain;
				}
			}
		}

		return array_unique( $domains );
	}

	/**
	 * Check if domain is a common WordPress domain.
	 *
	 * @since 1.4.0
	 *
	 * @param string $domain Domain to check.
	 * @return bool True if it's a common WordPress domain, false otherwise.
	 */
	private function is_common_wordpress_domain( $domain ) {
		$wordpress_domains = array(
			'wordpress.org',
			'w.org',
			'wordpress.com',
			'gravatar.com',
			'wp.com',
		);

		foreach ( $wordpress_domains as $wp_domain ) {
			if ( str_contains( $domain, $wp_domain ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if domain is a common/known safe domain that should be ignored.
	 *
	 * @since 1.4.0
	 *
	 * @param string $domain Domain to check.
	 * @return bool True if it's a known safe domain, false otherwise.
	 */
	private function is_known_safe_domain( $domain ) {
		$safe_domains = array(
			'github.com',
			'gitlab.com',
			'bitbucket.org',
			'gnu.org',         // GNU licenses.
			'opensource.org',  // OSI licenses.
			'creativecommons.org',
			'fsf.org',         // Free Software Foundation.
		);

		foreach ( $safe_domains as $safe_domain ) {
			if ( str_contains( $domain, $safe_domain ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if domain is a localhost/staging domain.
	 *
	 * @since 1.4.0
	 *
	 * @param string $domain Domain to check.
	 * @return bool True if it's a localhost domain, false otherwise.
	 */
	private function is_localhost_domain( $domain ) {
		$patterns = array(
			'localhost',
			'127.0.0.1',
			'example.com',
			'example.org',
			'.local',
			'.test',
			'.localhost',
		);

		foreach ( $patterns as $pattern ) {
			if ( str_contains( $domain, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract domain from URL or hostname.
	 *
	 * @since 1.4.0
	 *
	 * @param string $host           Hostname to extract domain from.
	 * @param array  $existing_tld_names Array of TLD names.
	 * @return string Extracted domain.
	 */
	private function extract_domain_from_host( $host, $existing_tld_names ) {
		$host       = strtolower( $host );
		$domain_tld = '';

		// Get domain biggest TLD.
		foreach ( $existing_tld_names as $tld ) {
			if ( str_ends_with( $host, $tld ) ) {
				if ( strlen( $tld ) > strlen( $domain_tld ) ) {
					$domain_tld = $tld;
				}
			}
		}

		if ( empty( $domain_tld ) ) {
			// Fallback: assume last two parts are the domain.
			$parts = explode( '.', $host );
			if ( count( $parts ) >= 2 ) {
				return $parts[ count( $parts ) - 2 ] . '.' . $parts[ count( $parts ) - 1 ];
			}
			return $host;
		}

		// Get domain from host and TLD.
		$domain = str_replace( '.' . $domain_tld, '', $host );
		$parts  = explode( '.', $domain );
		$domain = end( $parts ) . '.' . $domain_tld;

		return $domain;
	}
}
