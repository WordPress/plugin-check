<?php
/**
 * Class Core_Library_File_Signature_Checker.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

/**
 * Helper for confirming whether a filename candidate is a bundled WordPress core library.
 *
 * @since n.e.x.t
 */
class Core_Library_File_Signature_Checker {

	/**
	 * Checks whether a file path and content match a known WordPress core library.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $file          Absolute file path.
	 * @param string $relative_file File path relative to the plugin root.
	 * @return bool True when the file is likely a bundled WordPress core library, false otherwise.
	 */
	public static function file_matches_core_library_signature( $file, $relative_file ) {
		foreach ( self::get_core_library_file_signatures() as $library ) {
			if ( preg_match( $library['file_pattern'], $relative_file ) && self::file_contains_core_library_signature( $file, $library ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets WordPress core library filename candidates and required content signatures.
	 *
	 * The filename patterns intentionally match full basenames only. A matching
	 * filename is only a candidate; the file must also contain library-specific
	 * symbols, package names, global classes, or namespaced classes before the
	 * check reports it as a bundled copy of a WordPress core library.
	 *
	 * @since n.e.x.t
	 *
	 * @return array[] Core library file signature definitions.
	 */
	private static function get_core_library_file_signatures() {
		return array_merge(
			self::get_jquery_core_library_file_signatures(),
			self::get_javascript_core_library_file_signatures(),
			self::get_php_core_library_file_signatures()
		);
	}

	/**
	 * Gets WordPress core jQuery-family library filename candidates and required content signatures.
	 *
	 * @since n.e.x.t
	 *
	 * @return array[] Core library file signature definitions.
	 */
	private static function get_jquery_core_library_file_signatures() {
		return array(
			array(
				'file_pattern'     => '~(?:^|/)jquery(?:-[0-9.]+)?(?:\.slim)?(?:\.min)?\.js$~i',
				'content_patterns' => array( '~jQuery JavaScript Library~i', '~\bwindow\.jQuery\b~', '~\bjQuery\.fn\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)jquery-ui(?:-[0-9.]+)?(?:\.slim)?(?:\.min)?\.js$~i',
				'content_patterns' => array( '~jQuery UI~i', '~\bjQuery\.ui\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)jquery\.color(?:\.slim)?(?:\.min)?\.js$~i',
				'content_patterns' => array( '~jQuery Color~i', '~\bjQuery\.Color\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)jquery\.ui\.touch-punch$~i',
				'content_patterns' => array( '~jQuery UI Touch Punch~i' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)jquery\.hoverintent$~i',
				'content_patterns' => array( '~hoverIntent~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)jquery\.imgareaselect$~i',
				'content_patterns' => array( '~imgAreaSelect~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)jquery\.hotkeys$~i',
				'content_patterns' => array( '~jQuery Hotkeys~i' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)jquery\.ba-serializeobject$~i',
				'content_patterns' => array( '~serializeObject~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)jquery\.query-object$~i',
				'content_patterns' => array( '~jQuery\.query~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)jquery\.suggest$~i',
				'content_patterns' => array( '~jQuery Suggest~i' ),
			),
		);
	}

	/**
	 * Gets WordPress core JavaScript library filename candidates and required content signatures.
	 *
	 * @since n.e.x.t
	 *
	 * @return array[] Core library file signature definitions.
	 */
	private static function get_javascript_core_library_file_signatures() {
		return array(
			array(
				'file_pattern'     => '~(?:^|/)polyfill(?:\.min)?\.js$~i',
				'content_patterns' => array( '~wp-polyfill~i', '~\bcore-js\b~i', '~\bregeneratorRuntime\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)iris(?:\.min)?\.js$~i',
				'content_patterns' => array( '~\bIris\b~', '~\.iris\s*\(~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)backbone(?:\.min)?\.js$~i',
				'content_patterns' => array( '~Backbone\.js~i', '~\bBackbone\.VERSION\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)clipboard(?:\.min)?\.js$~i',
				'content_patterns' => array( '~clipboard\.js~i', '~\bClipboardJS\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)closest(?:\.min)?\.js$~i',
				'content_patterns' => array( '~Element\.prototype\.closest~', '~element-closest~i' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)codemirror(?:\.min)?\.js$~i',
				'content_patterns' => array( '~\bCodeMirror\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)formdata(?:\.min)?\.js$~i',
				'content_patterns' => array( '~FormData\.prototype~', '~formdata-polyfill~i' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)json2(?:\.min)?\.js$~i',
				'content_patterns' => array( '~json2\.js~i', '~JSON\.stringify~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)lodash(?:\.min)?\.js$~i',
				'content_patterns' => array( '~lodash~i', '~\b_\.VERSION\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)masonry\.pkgd(?:\.min)?\.js$~i',
				'content_patterns' => array( '~masonry\.pkgd~i', '~\bMasonry\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)mediaelement-and-player(?:\.min)?\.js$~i',
				'content_patterns' => array( '~MediaElementPlayer~', '~\bmejs\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)moment(?:\.min)?\.js$~i',
				'content_patterns' => array( '~moment\.js~i', '~hooks\.version~', '~moment\.version~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)plupload\.full(?:\.min)?\.js$~i',
				'content_patterns' => array( '~\bplupload\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)thickbox(?:\.min)?\.js$~i',
				'content_patterns' => array( '~\btb_show\b~', '~thickbox~i' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)twemoji(?:\.min)?\.js$~i',
				'content_patterns' => array( '~\btwemoji\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)underscore(?:[.-]min)?\.js$~i',
				'content_patterns' => array( '~Underscore\.js~i', '~\b_\.VERSION\b~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)moxie(?:\.min)?\.js$~i',
				'content_patterns' => array( '~mOxie~' ),
			),
			array(
				'file_pattern'     => '~(?:^|/)zxcvbn(?:\.min)?\.js$~i',
				'content_patterns' => array( '~\bzxcvbn\b~' ),
			),
		);
	}

	/**
	 * Gets WordPress core PHP library filename candidates and required class signatures.
	 *
	 * @since n.e.x.t
	 *
	 * @return array[] Core library file signature definitions.
	 */
	private static function get_php_core_library_file_signatures() {
		return array(
			array(
				'file_pattern' => '~(?:^|/)getid3\.php$~i',
				'php_classes'  => array( 'getID3' ),
			),
			array(
				'file_pattern' => '~(?:^|/)pclzip\.lib\.php$~i',
				'php_classes'  => array( 'PclZip' ),
			),
			array(
				'file_pattern' => '~(?:^|/)PasswordHash\.php$~i',
				'php_classes'  => array( 'PasswordHash' ),
			),
			array(
				'file_pattern' => '~(?:^|/)PHPMailer\.php$~i',
				'php_classes'  => array( 'PHPMailer', 'PHPMailer\PHPMailer\PHPMailer' ),
			),
			array(
				'file_pattern' => '~(?:^|/)SimplePie\.php$~i',
				'php_classes'  => array( 'SimplePie' ),
			),
		);
	}

	/**
	 * Checks whether a candidate file contains a known core library signature.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $file    Absolute file path.
	 * @param array  $library Core library signature definition.
	 * @return bool True when the file contains a matching signature, false otherwise.
	 */
	private static function file_contains_core_library_signature( $file, array $library ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = file_get_contents( $file );
		if ( false === $contents ) {
			return false;
		}

		if ( isset( $library['php_classes'] ) || isset( $library['php_functions'] ) ) {
			$symbols = self::get_php_symbols( $contents );

			foreach ( $library['php_classes'] ?? array() as $class_name ) {
				if ( isset( $symbols['classes'][ self::normalize_php_symbol_name( $class_name ) ] ) ) {
					return true;
				}
			}

			foreach ( $library['php_functions'] ?? array() as $function_name ) {
				if ( isset( $symbols['functions'][ self::normalize_php_symbol_name( $function_name ) ] ) ) {
					return true;
				}
			}
		}

		foreach ( $library['content_patterns'] ?? array() as $pattern ) {
			if ( preg_match( $pattern, $contents ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extracts global and namespaced PHP class and function symbols.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $contents PHP file contents.
	 * @return array Extracted symbols keyed by normalized fully qualified name.
	 */
	private static function get_php_symbols( $contents ) {
		$tokens = token_get_all( $contents );
		$state  = array(
			'namespace'           => '',
			'brace_depth'         => 0,
			'pending_class_brace' => false,
			'class_brace_depths'  => array(),
			'symbols'             => array(
				'classes'   => array(),
				'functions' => array(),
			),
		);

		foreach ( $tokens as $index => $token ) {
			if ( self::update_php_symbol_scope( $token, $state ) || ! is_array( $token ) ) {
				continue;
			}

			self::collect_php_symbol( $tokens, $index, $state );
		}

		return $state['symbols'];
	}

	/**
	 * Updates PHP parser state for brace-delimited scopes.
	 *
	 * @since n.e.x.t
	 *
	 * @param mixed $token PHP token or token text.
	 * @param array $state PHP symbol parser state.
	 * @return bool True when the token was handled as a scope token, false otherwise.
	 */
	private static function update_php_symbol_scope( $token, array &$state ) {
		if ( '{' === $token ) {
			++$state['brace_depth'];
			if ( $state['pending_class_brace'] ) {
				$state['class_brace_depths'][] = $state['brace_depth'];
				$state['pending_class_brace']  = false;
			}
			return true;
		}

		if ( '}' === $token ) {
			self::leave_php_symbol_scope( $state );
			return true;
		}

		return false;
	}

	/**
	 * Leaves the current PHP symbol parser scope.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $state PHP symbol parser state.
	 */
	private static function leave_php_symbol_scope( array &$state ) {
		while ( ! empty( $state['class_brace_depths'] ) && end( $state['class_brace_depths'] ) === $state['brace_depth'] ) {
			array_pop( $state['class_brace_depths'] );
		}
		--$state['brace_depth'];
	}

	/**
	 * Collects a PHP class, interface, trait, function, or namespace symbol.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Current token index.
	 * @param array $state  PHP symbol parser state.
	 */
	private static function collect_php_symbol( array $tokens, $index, array &$state ) {
		$token = $tokens[ $index ];

		if ( T_NAMESPACE === $token[0] ) {
			$state['namespace'] = self::parse_php_namespace( $tokens, $index );
			return;
		}

		if ( self::is_php_class_declaration_token( $tokens, $index ) ) {
			self::add_php_class_symbol( $tokens, $index, $state );
			return;
		}

		if ( T_FUNCTION === $token[0] && empty( $state['class_brace_depths'] ) ) {
			self::add_php_function_symbol( $tokens, $index, $state );
		}
	}

	/**
	 * Checks whether the token is a class, interface, or trait declaration.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Current token index.
	 * @return bool True when the token declares a PHP type, false otherwise.
	 */
	private static function is_php_class_declaration_token( array $tokens, $index ) {
		$token = $tokens[ $index ];
		if ( ! in_array( $token[0], array( T_CLASS, T_INTERFACE, T_TRAIT ), true ) ) {
			return false;
		}

		return T_CLASS !== $token[0] || ! self::previous_php_token_is( $tokens, $index, T_DOUBLE_COLON );
	}

	/**
	 * Adds a PHP class, interface, or trait symbol to parser state.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Current token index.
	 * @param array $state  PHP symbol parser state.
	 */
	private static function add_php_class_symbol( array $tokens, $index, array &$state ) {
		$class_name = self::next_php_string_token( $tokens, $index );
		if ( '' !== $class_name ) {
			$symbol                                 = self::normalize_php_symbol_name( $state['namespace'] . '\\' . $class_name );
			$state['symbols']['classes'][ $symbol ] = true;
		}
		$state['pending_class_brace'] = true;
	}

	/**
	 * Adds a PHP function symbol to parser state.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Current token index.
	 * @param array $state  PHP symbol parser state.
	 */
	private static function add_php_function_symbol( array $tokens, $index, array &$state ) {
		$function_name = self::next_php_string_token( $tokens, $index );
		if ( '' === $function_name ) {
			return;
		}

		$symbol                                   = self::normalize_php_symbol_name( $state['namespace'] . '\\' . $function_name );
		$state['symbols']['functions'][ $symbol ] = true;
	}

	/**
	 * Parses a namespace declaration from a PHP token stream.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $tokens          Token stream from token_get_all().
	 * @param int   $namespace_index Index of the T_NAMESPACE token.
	 * @return string Namespace name, or an empty string for the global namespace.
	 */
	private static function parse_php_namespace( array $tokens, $namespace_index ) {
		$namespace = '';

		for ( $index = $namespace_index + 1; isset( $tokens[ $index ] ); ++$index ) {
			$token = $tokens[ $index ];
			if ( ';' === $token || '{' === $token ) {
				break;
			}

			if ( is_array( $token ) && self::is_php_name_token( $token ) ) {
				$namespace .= $token[1];
			}
		}

		return trim( $namespace, '\\' );
	}

	/**
	 * Checks whether a token can be part of a PHP qualified name.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $token Token from token_get_all().
	 * @return bool True when the token is a PHP name token, false otherwise.
	 */
	private static function is_php_name_token( array $token ) {
		$name_token_ids = array( T_STRING, T_NS_SEPARATOR );
		if ( defined( 'T_NAME_QUALIFIED' ) ) {
			$name_token_ids[] = constant( 'T_NAME_QUALIFIED' );
		}
		if ( defined( 'T_NAME_FULLY_QUALIFIED' ) ) {
			$name_token_ids[] = constant( 'T_NAME_FULLY_QUALIFIED' );
		}

		return in_array( $token[0], $name_token_ids, true );
	}

	/**
	 * Gets the next PHP T_STRING token after a given token index.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $tokens Token stream from token_get_all().
	 * @param int   $index  Token index to start after.
	 * @return string Token value, or an empty string when none is found.
	 */
	private static function next_php_string_token( array $tokens, $index ) {
		for ( $next_index = $index + 1; isset( $tokens[ $next_index ] ); ++$next_index ) {
			$token = $tokens[ $next_index ];
			if ( is_array( $token ) && T_WHITESPACE === $token[0] ) {
				continue;
			}

			return is_array( $token ) && T_STRING === $token[0] ? $token[1] : '';
		}

		return '';
	}

	/**
	 * Checks whether the previous non-whitespace PHP token has the given token ID.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $tokens   Token stream from token_get_all().
	 * @param int   $index    Token index to start before.
	 * @param int   $token_id Token ID to check for.
	 * @return bool True when the previous meaningful token matches, false otherwise.
	 */
	private static function previous_php_token_is( array $tokens, $index, $token_id ) {
		for ( $previous_index = $index - 1; $previous_index >= 0; --$previous_index ) {
			$token = $tokens[ $previous_index ];
			if ( is_array( $token ) && T_WHITESPACE === $token[0] ) {
				continue;
			}

			return is_array( $token ) && $token_id === $token[0];
		}

		return false;
	}

	/**
	 * Normalizes a PHP symbol name for case-insensitive lookup.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $symbol_name PHP symbol name.
	 * @return string Normalized PHP symbol name.
	 */
	private static function normalize_php_symbol_name( $symbol_name ) {
		return strtolower( trim( $symbol_name, '\\' ) );
	}
}
