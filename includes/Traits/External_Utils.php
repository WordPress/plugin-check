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
	 * Filter the given array of files for php,js,css files.
	 *
	 * @since 1.4.0
	 *
	 * @param array  $files                Array of file files to be filtered.
	 * @param string $plugin_relative_path Plugin relative path.
	 * @return array An array containing php,js.css files, or an empty array if none are found.
	 */
	protected function filter_files_for_external( array $files, $plugin_relative_path ) {
		// Find the readme file.
		$ext_list = preg_grep( '/\.(php|js|css)$/i', $files );

		// Filter the readme files located at root.
		$potential_ext_files = array_filter(
			$ext_list,
			function ( $file ) use ( $plugin_relative_path ) {
				$file = str_replace( $plugin_relative_path, '', $file );
				return ! str_contains( $file, '/' );
			}
		);

		return ! empty( $potential_ext_files ) ? $potential_ext_files : array();
	}

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
		$urls              = array();

		$typical_off_loading_extensions = [
			'css',
			'svg',
			'jpg',
			'jpeg',
			'gif',
			'png',
			'webm',
			'mp4',
			'mpg',
			'mpeg',
			'mp3',
		];

		if ( ! empty( $lines ) ) {
			foreach ( $lines as $line ) {
				preg_match_all( '/@?(https?:\/\/)?(www\.)?[-a-zA-Z0-9:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9(:%_\+~#?&\/=]*)/', $line, $result );
				foreach ( $result[0] as $url ) {
					$url = strtolower( $url );
					if ( ! str_starts_with( $url, '@' ) ) { //Remove domains in email addresses.
						if ( ! str_starts_with( $url, 'http' ) ) { //Add protocol if domain taken without protocol.
							$url = 'http://' . $url;
						}
						$urls[] = $url;
					}
				}
			}
			$urls = array_unique( $urls );

			if ( ! empty( $urls ) ) {
				foreach ( $urls as $url ) {
					$parsed_url = parse_url( $url );
					if ( false !== $parsed_url ) {
						$path = '';
						if ( ! empty( $parsed_url['path'] ) ) {
							$path = $parsed_url['path'];
						}
						preg_match_all( '/(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]/', $url, $result );
						foreach ( $result[0] as $domain ) {
							$domain         = strtolower( $domain );
							$domainElements = explode( '.', $domain );
							$tld            = end( $domainElements );
							if ( $tld == (int) $tld ) {
								//Invalid TLD, numeric, looks like detected a version.
							} else if ( in_array( $tld, array_merge( $typical_off_loading_extensions, [
								'php',
								'html',
								'zip'
							] ) ) ) {
								//Invalid, looks like detected a file
							} else {
								$host = $parsed_url['host'];

								//Get domain biggest TLD.
								$domain_tld = '';
								foreach ( $existing_tld_names as $tld ) {
									if ( str_ends_with( $host, $tld ) ) {
										if ( strlen( $tld ) > strlen( $domain_tld ) ) {
											$domain_tld = $tld;
										}
									}
								}

								if ( ! empty( $domain_tld ) ) {
									// Get domain from host and tld
									$domain = str_replace( '.' . $domain_tld, '', $host );  // remove the TLD from the host
									$parts  = explode( '.', $domain );  // split the remaining host into parts
									$domain = end( $parts ) . '.' . $domain_tld;

									//Find domain
									$key = $this->get_key_domain_mentioned_in_readme( $domain );
									if ( false !== $key ) {
										// If found, just add URL
										$domains_mentioned[ $key ]['urls'][] = $url;
										if ( ! empty( $path ) ) {
											$domains_mentioned[ $key ]['paths'][] = $path;
										}
									} else {
										//Not found, create it.
										$domain_mentioned = array(
											'domains' => $this->add_domains_of_same_service( $domain ),
											'urls'    => array( $url ),
											'paths'   => array(),
										);
										if ( ! empty( $path ) ) {
											$domain_mentioned['paths'] = array( $path );
										}
										$domains_mentioned[] = $domain_mentioned;
									}
								}
							}
						}
					}
				}
			}

		}
		if ( ! empty( $domains_mentioned ) ) {
			$domains_mentioned = array_map( function ( $domain ) {
				$domain['urls'] = array_unique( $domain['urls']);
				return $domain;
			}, $domains_mentioned );
		}

		return $domains_mentioned;
	}

	/**
	 * Get key domain mentioned in readme file.
	 *
	 * @since 1.4.0
	 *
	 * @param string $string String.
	 * @return string|bool Key of domain mentioned in readme file, or false if not found.
	 */
	function get_key_domain_mentioned_in_readme( $string ) {
		if ( ! empty( $this->domainsMentionedReadme ) ) {
			foreach ( $this->domainsMentionedReadme as $key => $domains ) {
				if ( ! empty( $domains['domains'] ) ) {
					foreach ( $domains['domains'] as $domain ) {
						if ( str_contains( $string, $domain ) ) {
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
		$domains                 = array( $domain );
		$domainsOfTheSameService = array(
			'paypal.com'    => [ 'paypal.com', 'paypalobjects.com' ],
			'google.com'    => [ 'google.com', 'googleapis.com', 'googletagmanager.com' ],
			'microsoft.com' => [ 'microsoft.com', 'outlook.com', 'live.com' ],
			'atlassian.net' => [ 'atlassian.com', 'trello.com' ],
			'dropbox.com'   => [ 'dropbox.com', 'dropboxapi.com' ],
			'tiktok.com'   => [ 'tiktok.com', 'tiktokapis.com' ],
			'zendesk.com' => [ 'zendesk.com', 'zdassets.com' ]
		);
		foreach ( $domainsOfTheSameService as $key => $service ) {
			foreach ( $service as $serviceDomain ) {
				if ( $serviceDomain === $domain ) {
					$domains = array_merge( $domains, $domainsOfTheSameService[ $key ] );
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

		if ( ! empty( $this->domainsMentionedReadme[ $key ]['paths'] ) ) {
			foreach ( $this->domainsMentionedReadme[ $key ]['paths'] as $path ) {
				foreach ( $this->privacyCommonURIsPaths as $privacyStr ) {
					if ( str_contains( $path, $privacyStr ) ) {
						$privacy = $path;
						break;
					}
				}
				foreach ( $this->termsCommonURIsPaths as $termsStr ) {
					if ( str_contains( $path, $termsStr ) ) {
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

	protected function find_external_calls( $file ) {
		$lines = file( $file );
		$this->find_functions();
		$this->regexKnownUrls( $lines );
		$this->findClasses();
		$this->regexEstructures( $lines );
		$this->findDeclarations( $lines );
	}

	/**
	 * Find functions in the file.
	 *
	 * @since 1.4.0
	 */
	protected function find_functions() {

	}

}
