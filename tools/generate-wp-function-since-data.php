<?php
/**
 * Generates a JSON dataset of WordPress functions and their first @since version.
 *
 * Usage:
 * php tools/generate-wp-function-since-data.php --wordpress-dir=/path/to/wordpress --output=includes/Vars/wp-functions-since.json
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "This script must run in CLI mode.\n" );
	exit( 1 );
}

$options = getopt( '', array( 'wordpress-dir:', 'output:' ) );

$wordpress_dir = isset( $options['wordpress-dir'] ) ? rtrim( (string) $options['wordpress-dir'], '/\\' ) : '';
$output_file   = isset( $options['output'] ) ? (string) $options['output'] : '';

if ( '' === $wordpress_dir || '' === $output_file ) {
	fwrite( STDERR, "Usage: php tools/generate-wp-function-since-data.php --wordpress-dir=/path/to/wordpress --output=includes/Vars/wp-functions-since.json\n" );
	exit( 1 );
}

$scan_dirs = array(
	$wordpress_dir . '/wp-includes',
	$wordpress_dir . '/wp-admin',
);

foreach ( $scan_dirs as $scan_dir ) {
	if ( ! is_dir( $scan_dir ) ) {
		fwrite( STDERR, "Directory does not exist: {$scan_dir}\n" );
		exit( 1 );
	}
}

$function_since = array();

foreach ( $scan_dirs as $scan_dir ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $scan_dir, FilesystemIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $file_info ) {
		/** @var SplFileInfo $file_info */
		if ( 'php' !== strtolower( $file_info->getExtension() ) ) {
			continue;
		}

		$source = file_get_contents( $file_info->getPathname() );
		if ( false === $source || '' === $source ) {
			continue;
		}

		$tokens = token_get_all( $source );
		$count  = count( $tokens );
		$brace_depth        = 0;
		$class_brace_depths = array();
		$pending_class_like = false;

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];

			if ( is_array( $token ) ) {
				if ( is_class_like_token( $token[0], $tokens, $i ) ) {
					$pending_class_like = true;
					continue;
				}

				if ( defined( 'T_CURLY_OPEN' ) && T_CURLY_OPEN === $token[0] ) {
					++$brace_depth;
					continue;
				}

				if ( defined( 'T_DOLLAR_OPEN_CURLY_BRACES' ) && T_DOLLAR_OPEN_CURLY_BRACES === $token[0] ) {
					++$brace_depth;
					continue;
				}
			} elseif ( '{' === $token ) {
				++$brace_depth;
				if ( $pending_class_like ) {
					$class_brace_depths[] = $brace_depth;
				}
				$pending_class_like = false;
				continue;
			} elseif ( '}' === $token ) {
				$closing_brace_depth = $brace_depth;
				if ( $brace_depth > 0 ) {
					--$brace_depth;
				}

				if ( ! empty( $class_brace_depths ) ) {
					$last_class_brace_depth = $class_brace_depths[ count( $class_brace_depths ) - 1 ];
					if ( $last_class_brace_depth === $closing_brace_depth ) {
						array_pop( $class_brace_depths );
					}
				}
				continue;
			}

			if ( ! is_array( $token ) || T_FUNCTION !== $token[0] ) {
				continue;
			}

			if ( ! empty( $class_brace_depths ) ) {
				continue;
			}

			$name_index = null;
			for ( $j = $i + 1; $j < $count; $j++ ) {
				$next = $tokens[ $j ];
				if ( is_array( $next ) && T_WHITESPACE === $next[0] ) {
					continue;
				}

				if ( is_array( $next ) && T_STRING === $next[0] ) {
					$name_index = $j;
				}
				break;
			}

			// Anonymous function.
			if ( null === $name_index ) {
				continue;
			}

			$function_name = strtolower( $tokens[ $name_index ][1] );
			$doc_comment   = get_previous_doc_comment( $tokens, $i );
			$since_version = extract_since_version( $doc_comment );

			if ( '' === $since_version ) {
				continue;
			}

			if ( ! isset( $function_since[ $function_name ] ) || version_compare( $since_version, $function_since[ $function_name ], '<' ) ) {
				$function_since[ $function_name ] = $since_version;
			}
		}
	}
}

ksort( $function_since, SORT_STRING );

$dataset = array(
	'metadata'       => array(
		'source'           => 'wordpress-core',
		'wordpress_version' => get_wordpress_version( $wordpress_dir ),
	),
	'function_since' => $function_since,
);

$output_dir = dirname( $output_file );
if ( ! is_dir( $output_dir ) ) {
	mkdir( $output_dir, 0777, true );
}

$json = json_encode( $dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
if ( false === $json ) {
	fwrite( STDERR, "Failed to encode JSON.\n" );
	exit( 1 );
}

file_put_contents( $output_file, $json . PHP_EOL );

fwrite( STDOUT, sprintf( "Generated %d functions in %s\n", count( $function_since ), $output_file ) );

/**
 * Finds the nearest preceding docblock for a function token.
 *
 * @param array $tokens Token stream.
 * @param int   $index  Current function token index.
 * @return string
 */
function get_previous_doc_comment( array $tokens, int $index ): string {
	for ( $i = $index - 1; $i >= 0; $i-- ) {
		$token = $tokens[ $i ];

		if ( is_array( $token ) ) {
			if ( T_DOC_COMMENT === $token[0] ) {
				return $token[1];
			}

			if ( in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL ), true ) ) {
				continue;
			}
		} elseif ( trim( $token ) === '' || '[' === $token || ']' === $token || ',' === $token ) {
			continue;
		}

		break;
	}

	return '';
}

/**
 * Extracts @since version from a docblock.
 *
 * @param string $doc_comment Docblock string.
 * @return string
 */
function extract_since_version( string $doc_comment ): string {
	if ( '' === $doc_comment ) {
		return '';
	}

	if ( preg_match( '/@since\s+([0-9]+(?:\.[0-9]+){1,2})/i', $doc_comment, $matches ) ) {
		return $matches[1];
	}

	return '';
}

/**
 * Reads WordPress version from wp-includes/version.php.
 *
 * @param string $wordpress_dir WordPress root directory.
 * @return string
 */
function get_wordpress_version( string $wordpress_dir ): string {
	$version_file = $wordpress_dir . '/wp-includes/version.php';
	if ( ! is_readable( $version_file ) ) {
		return 'unknown';
	}

	$source = file_get_contents( $version_file );
	if ( false === $source ) {
		return 'unknown';
	}

	if ( preg_match( "/\\\$wp_version\\s*=\\s*'([^']+)'\\s*;/", $source, $matches ) ) {
		return $matches[1];
	}

	return 'unknown';
}

/**
 * Determines whether a token is a class-like declaration token.
 *
 * @param int   $token_id Current token ID.
 * @param array $tokens   Token stream.
 * @param int   $index    Current token index.
 * @return bool
 */
function is_class_like_token( int $token_id, array $tokens, int $index ): bool {
	$class_like_tokens = array( T_CLASS, T_INTERFACE, T_TRAIT );

	if ( defined( 'T_ENUM' ) ) {
		$class_like_tokens[] = constant( 'T_ENUM' );
	}

	if ( ! in_array( $token_id, $class_like_tokens, true ) ) {
		return false;
	}

	// Skip ::class constant usage.
	for ( $i = $index - 1; $i >= 0; $i-- ) {
		$token = $tokens[ $i ];
		if ( is_array( $token ) && T_WHITESPACE === $token[0] ) {
			continue;
		}

		return ! ( is_array( $token ) && T_DOUBLE_COLON === $token[0] );
	}

	return true;
}
