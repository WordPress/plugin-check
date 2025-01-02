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
		$this->find_classes();
		$this->regex_estructures( $lines );
		$this->find_declarations( $lines );
		
	}

	//Check PHP function calls loading URLs.
	function find_functions() {
		if ( ! empty( $this->stmts ) ) {
			$funcCalls = $this->nodeFinder->findInstanceOf( $this->stmts, Node\Expr\FuncCall::class );
			if ( ! empty( $funcCalls ) ) {
				foreach ( $funcCalls as $funccall ) {
					$foundInSameLine = true;
					$lastFoundExprArray = [];
					if ( $this->hasFunctionName( $funccall ) ) {
						$log          = '';
						$functionName = $this->getCallName($funccall);

						//Enqueue functions
						if ( in_array( $functionName, [
							'wp_register_script',
							'wp_enqueue_script',
							'wp_register_style',
							'wp_enqueue_style'
						] ) ) {
							// Look for second parameter of this PHP functions.
							if ( isset( $funccall->args[1] ) ) {
								$argValue = $funccall->args[1]->value;
								if ( ! empty( $argValue ) ) {
									$log = $this->checkArgGetLog( $argValue, $foundInSameLine, $lastFoundExprArray );
								}
							}
						}

						// External calls
						if ( in_array( $functionName, [
							'wp_remote_request',
							'wp_safe_remote_request',
							'wp_remote_get',
							'wp_safe_remote_get',
							'wp_remote_post',
							'wp_safe_remote_post',
							'wp_remote_head',
							'wp_safe_remote_head',
							'wp_remote_fopen',
							'file_get_contents',
							'download_url',
							'fopen',
							'file'
						] ) ) {
							// Look for first parameter of this PHP functions.
							if ( isset( $funccall->args[0] ) ) {
								$argValue = $funccall->args[0]->value;
								if ( ! empty( $argValue ) ) {
									$log = $this->checkArgGetLog( $argValue, $foundInSameLine, $lastFoundExprArray );
								}
							}
						}

						if ( ! empty( $log ) ) {
							if ( ! $this->isAlreadyLogged( $funccall->getStartLine() ) ) {
								$this->logCallExpr( $funccall, 1, $log, true );
								if(!$foundInSameLine && !empty($lastFoundExprArray)){
									foreach ($lastFoundExprArray as $expr) {
										$this->saveLog( 0, '# ↳ Found: ' . $this->prettyPrinter->prettyPrint( [ $expr ] ), $this->getLogPostContextId( $log, $this->getLogLineID( $funccall->getStartLine() ) ) );
									}
								}
							}
						}
					}
				}
			}
		}
	}

	//Check PHP class calls loading URLs.
	function find_classes() {
		if ( ! empty( $this->stmts ) ) {
			$classNews = $this->nodeFinder->findInstanceOf( $this->stmts, Node\Expr\New_::class );
			if ( ! empty( $classNews ) ) {
				foreach ( $classNews as $classNew ) {
					$foundInSameLine = true;
					$lastFoundExprArray = [];
					if ( $this->hasClassNewName( $classNew ) ) {
						$log       = '';
						$className = $classNew->class->toString();
						if ( in_array( $className, [
							'SoapClient',
							'nusoap_client',
						] ) ) {
							if ( isset( $classNew->args[0] ) ) {
								$argValue = $classNew->args[0]->value;
								if ( ! empty( $argValue ) ) {
									$log = $this->checkArgGetLog( $argValue, $foundInSameLine, $lastFoundExprArray );
								}
							}
						}

						if ( ! empty( $log ) ) {
							if ( ! $this->isAlreadyLogged( $classNew->getStartLine() ) ) {
								$this->saveLinesNodeDetailLog( $classNew, $log, true );
								if(!$foundInSameLine && !empty($lastFoundExprArray)){
									foreach ($lastFoundExprArray as $expr) {
										$this->saveLog( 0, '# ↳ Found: ' . $this->prettyPrinter->prettyPrint( [ $expr ] ), $this->getLogPostContextId( $log, $this->getLogLineID( $classNew->getStartLine() ) ) );
									}
								}
							}
						}
					}
				}
			}
		}
	}

	// Regex over typical code structures cointaining URLs
	function regex_estructures( $lines ) {
		$regexArray = [
			'src-simple'       => '/src\s*=\s*\\\?\'((.*?(<\?.+?\?>)?.*?)+?)\\\?\'/',
			'src-double'       => '/src\s*=\s*\\\?"((.*?(<\?.+?\?>)?.*?)+?)\\\?"/',
			'css-simple'       => '/[:|\\s]\s*url\s*\(\s*\'((.*?(<\?.+?\?>)?.*?)+?)\'\s*\)/',
			//We are not covering the case of doing url(https://example.com) as without ' or " this is hard to find.
			'css-double'       => '/[:|\\s]\s*url\s*\(\s*"((.*?(<\?.+?\?>)?.*?)+?)"\s*\)/',
			//'css' => '[:|\\s]url\s*\(\s*["|\']?(.+?)["|\']?\)',
			'jsImport'         => '/@import\s*["|\'|`]((.*?(<\?.+?\?>)?.*?)+?)["|\'|`]/',
			'jsImportScripts'  => '/importScripts\s*\(\s*["|\'|`]((.*?(<\?.+?\?>)?.*?)+?)["|\'|`]\s*\)/',
			'jsSetAttribute'   => '/setAttribute\s*\(\s*["|\'|`]src["|\'|`]\s*,\s*["|\'|`](.+?)["|\'|`]\s*\)/',
			'jsAjax-simple'    => '/\s*url\s*:\s*\'((.*?(<\?.+?\?>)?.*?)+?)\'\s*/',
			'jsAjax-double'    => '/\s*url\s*:\s*"((.*?(<\?.+?\?>)?.*?)+?)"\s*/',
			'jsAjax-inverted'  => '/\s*url\s*:\s*`((.*?(<\?.+?\?>)?.*?)+?)`\s*/',
			'jsFetch-simple'   => '/\s*fetch\s*\(\s*\'((.*?(<\?.+?\?>)?.*?)+?)\'\s*/',
			'jsFetch-double'   => '/\s*fetch\s*\(\s*"((.*?(<\?.+?\?>)?.*?)+?)"\s*/',
			'jsFetch-inverted' => '/\s*fetch\s*\(\s*`((.*?(<\?.+?\?>)?.*?)+?)`\s*/',
		];

		foreach ( $regexArray as $regex ) {
			$this->logRegexIncidences( $lines, $regex, '', false );
		}
	}

	// Look for any PHP / JS variable declaration and guess if that looks like a external service.
	// TODO this function consumes too much time because of getStringsFromAssignsExpr, find ways to optimize it.
	function find_declarations( $lines ) {
		// Find all the assings in PHP
		if ( ! empty( $this->stmts ) ) {
			$assigns = $this->nodeFinder->findInstanceOf( $this->stmts, Node\Expr\Assign::class );
			if ( ! empty( $assigns ) ) {
				foreach ( $assigns as $assign ) {
					if ( ! empty( $assign->expr ) ) {
						$foundInSameLine = true;
						$stringsArray = $this->getStringsFromAssignsExpr( $assign->expr, $foundInSameLine );
						if ( ! empty( $stringsArray ) ) {
							foreach ( $stringsArray as $string ) {
								$log = $this->checkStringGetLog( $string, true );
								if ( ! empty( $log ) ) {
									if ( ! $this->isAlreadyLogged( $assign->getStartLine() ) ) {
										$this->saveLinesNodeDetailLog( $assign, $log, true );
										if(!$foundInSameLine){
											$this->saveLog( 0, '# ↳ Detected: ' . $string, $this->getLogPostContextId( $log, $this->getLogLineID( $assign->getStartLine() ) ) );
										}
									}
								}
							}
						}
					}
				}
			}
		}

		// Find anything else that looks like an assign (mostly for JS but will also catch PHP and HTML)
		// Regex: anything looking like a URL preceded by "XXXX =" except for href.
		$regex = '/[a-zA-Z_$][a-zA-Z_$0-9]*(?<!href)\s*=\s*["|\'](https?:\/\/[www\.]?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b[-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)["|\']/';
		$this->logRegexIncidences( $lines, $regex, '', true );
	}

}
