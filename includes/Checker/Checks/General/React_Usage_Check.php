<?php
/**
 * Class React_Usage_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\General;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check to detect React usage that breaks once WordPress upgrades to React 19.
 *
 * WordPress is moving from React 18 to React 19, and two kinds of build output
 * stop working at that point.
 *
 * The first, and by far the most common cause of breakage, is a plugin inlining
 * React into its build output instead of externalizing it (i.e. relying on the
 * copy shipped with WordPress). The element object shape changed between React 18
 * and 19, so elements produced by an inlined pre-19 build are rejected by the
 * React 19 bundled with WordPress. Those files are reported as errors.
 *
 * The second is calling one of the long-deprecated public APIs that React 19
 * drops. Such calls keep working today and stop working after the upgrade, so
 * they are reported as warnings.
 *
 * @since 2.2.0
 */
class React_Usage_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * URL explaining how to externalize React.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	const EXTERNALIZE_DOCS_URL = 'https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dependency-extraction-webpack-plugin/';

	/**
	 * URL of the React 19 upgrade guide.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	const UPGRADE_DOCS_URL = 'https://react.dev/blog/2024/04/25/react-19-upgrade-guide';

	/**
	 * Pattern matching the element factory of a build predating React 19.
	 *
	 * `_owner` is a field of the element object that React 19 dropped, so only a
	 * build that creates pre-19 elements itself assigns it. Requiring it stops a
	 * name as ordinary as `jsx` from implicating a bundle that merely mentions
	 * the element symbol, and keeps libraries that reimplement the React API on
	 * their own element type, such as `preact/compat`, out of the results.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	const ELEMENT_FACTORY_PATTERN = '/_owner\s*:/';

	/**
	 * Characters that may appear in a JavaScript identifier, keyword, or number.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	const WORD_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_$';

	/**
	 * Characters that separate JavaScript tokens without being one.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	const WHITESPACE_CHARACTERS = " \t\n\r";

	/**
	 * Characters that begin a token the tokenizer has to look at.
	 *
	 * Anything else is an operator or punctuator, which the tokenizer only needs
	 * to know it has passed.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	const TOKEN_START_CHARACTERS = self::WORD_CHARACTERS . self::WHITESPACE_CHARACTERS . "\"'`/)]";

	/**
	 * Keywords that expect a value to follow them.
	 *
	 * A slash divides when a value precedes it and opens a regular expression
	 * otherwise. These are the keywords that look like a value, because they are
	 * made of word characters, but are followed by one instead.
	 *
	 * @since 2.2.0
	 * @var string[]
	 */
	const VALUE_EXPECTING_KEYWORDS = array(
		'return',
		'typeof',
		'instanceof',
		'in',
		'of',
		'new',
		'delete',
		'void',
		'throw',
		'case',
		'do',
		'else',
		'yield',
		'await',
	);

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 2.2.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_GENERAL );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$js_files = self::filter_files_by_extension( $files, 'js' );

		foreach ( $js_files as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $file );
			if ( false === $contents ) {
				continue;
			}

			// A file with React inlined into it is reported for that alone. Most
			// of what it contains is React's own code rather than the plugin's,
			// and externalizing the package is the fix either way.
			if ( $this->check_inlined_packages( $result, $file, $contents ) ) {
				continue;
			}

			$this->check_removed_apis( $result, $file, $contents );
		}
	}

	/**
	 * Reports every pre-React 19 package inlined into a single file.
	 *
	 * Detection happens in two steps. The `react.element` symbol name establishes
	 * that a pre-19 build is in the file at all: React 19 renamed it to
	 * `react.transitional.element`, and a build that externalizes React contains
	 * neither. Markers internal to a package then identify which one was inlined,
	 * because the three packages WordPress externalizes are fixed separately.
	 *
	 * Both steps are required. The symbol name alone proves nothing, because small
	 * libraries such as `react-is` list every React symbol without inlining any
	 * React code, and a file may well inline one package while externalizing the
	 * rest.
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result $result   The check result to amend.
	 * @param string       $file     Absolute path to the JavaScript file.
	 * @param string       $contents Contents of the JavaScript file.
	 * @return bool True if any inlined package was reported, false otherwise.
	 */
	private function check_inlined_packages( Check_Result $result, $file, $contents ) {
		$position = $this->find_inlined_pre_19_react( $contents );

		if ( false === $position ) {
			return false;
		}

		$reported       = false;
		$is_development = $this->is_development_build( $contents );

		foreach ( $this->get_packages() as $package ) {
			if ( ! $this->matches_every_pattern( $package['patterns'], $contents ) ) {
				continue;
			}

			if ( $this->externalizes_global( $package['global'], $contents ) ) {
				continue;
			}

			$this->add_package_error( $result, $file, $position, $package, $is_development );
			$reported = true;
		}

		return $reported;
	}

	/**
	 * Locates the element marker emitted by React builds predating React 19.
	 *
	 * `react.element` is the name of the element type symbol used up to React
	 * 18. React 19 renamed it to `react.transitional.element`, and a build that
	 * externalizes React to the copy shipped with WordPress contains neither.
	 *
	 * Only the string literal is matched, not the surrounding
	 * `Symbol.for( ... )` call: the React 17 production builds hoist `Symbol.for`
	 * into a local variable and call it through that variable instead.
	 *
	 * @since 2.2.0
	 *
	 * @param string $contents Contents of the JavaScript file.
	 * @return array|false Array with `line` and `column` keys, or false if no match was found.
	 */
	private function find_inlined_pre_19_react( $contents ) {
		return $this->find_first_match( '/([\'"])react\.element\1/', $contents );
	}

	/**
	 * Adds the error for a single inlined package.
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result $result         The check result to amend.
	 * @param string       $file           Absolute path to the JavaScript file.
	 * @param array        $position       Array with `line` and `column` keys.
	 * @param array        $package        Package definition as returned by `get_packages()`.
	 * @param bool         $is_development Whether the inlined build is a development build.
	 */
	private function add_package_error( Check_Result $result, $file, array $position, array $package, $is_development ) {
		if ( $is_development ) {
			$message = sprintf(
				/* translators: %s: npm package name, e.g. "react-dom" */
				__( 'This file inlines a development build of the "%s" package instead of externalizing it. The bundled copy predates React 19 and breaks when WordPress upgrades to React 19, and development builds are far larger and slower than production builds. Use the dependency extraction webpack plugin so that the package is loaded from WordPress instead.', 'plugin-check' ),
				$package['label']
			);
		} else {
			$message = sprintf(
				/* translators: %s: npm package name, e.g. "react-dom" */
				__( 'This file inlines the "%s" package instead of externalizing it. The bundled copy predates React 19 and breaks when WordPress upgrades to React 19. Use the dependency extraction webpack plugin so that the package is loaded from WordPress instead.', 'plugin-check' ),
				$package['label']
			);
		}

		$this->add_result_error_for_file(
			$result,
			$message,
			$package['code'],
			$file,
			$position['line'],
			$position['column'],
			self::EXTERNALIZE_DOCS_URL,
			$is_development ? 7 : 6
		);
	}

	/**
	 * Reports whether a package is externalized to the copy WordPress ships.
	 *
	 * Externalizing keeps the package out of the build and reads it from a
	 * browser global instead, so reading that global is what proves a package
	 * was externalized.
	 *
	 * Writing the global proves the opposite, and one assignment anywhere in a
	 * file overrides every read in it. A build can only publish a copy of the
	 * package it already carries, and publishing it replaces the copy WordPress
	 * loaded, for every script that runs afterwards as well.
	 *
	 * A `*.asset.php` dependency is deliberately not accepted as proof either.
	 * The element marker means a pre-19 build is inlined regardless, and a
	 * declared dependency does not rule out a stale or mixed build that still
	 * bundles its own copy.
	 *
	 * @since 2.2.0
	 *
	 * @param string $name     Name of the browser global, e.g. `ReactDOM`.
	 * @param string $contents Contents of the JavaScript file.
	 * @return bool True if the package is externalized, false otherwise.
	 */
	private function externalizes_global( $name, $contents ) {
		// The trailing word boundary keeps `window.ReactDOM` from counting as a
		// reference to `window.React`.
		$reference = '/\bwindow\.' . $name . '\b/';

		// Plain assignment, along with the logical assignments a minifier may
		// emit. Ruling out a second equals sign leaves the comparison operators
		// out.
		$assignment = '/\bwindow\.' . $name . '\b\s*(?:\|\||&&|\?\?)?=[^=]/';

		return 1 === preg_match( $reference, $contents ) && 1 !== preg_match( $assignment, $contents );
	}

	/**
	 * Reports whether every one of the given patterns matches the contents.
	 *
	 * @since 2.2.0
	 *
	 * @param string[] $patterns Regular expression patterns.
	 * @param string   $contents Contents of the JavaScript file.
	 * @return bool True if all of the patterns match, false otherwise.
	 */
	private function matches_every_pattern( array $patterns, $contents ) {
		foreach ( $patterns as $pattern ) {
			if ( ! preg_match( $pattern, $contents ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the packages this check can tell apart.
	 *
	 * Every one of a package's `patterns` has to match. They match code internal
	 * to the package, so that a build which merely calls the package does not.
	 * `global` names the browser global that the dependency extraction webpack
	 * plugin maps the package to.
	 *
	 * @since 2.2.0
	 *
	 * @return array List of package definitions.
	 */
	private function get_packages() {
		return array(
			array(
				'label'    => 'react/jsx-runtime',
				'code'     => 'inlined_react_jsx_runtime',
				'global'   => 'ReactJSXRuntime',
				'patterns' => array(
					// The runtime assigns `jsx`/`jsxs` onto its exports object.
					// Call sites such as `ReactJSXRuntime.jsxs( ... )` are not
					// matched. Either name alone is enough, because a bundler
					// that sees only `jsx` call sites tree-shakes the `jsxs`
					// export away.
					'/\bjsxs?\s*[:=][^=]/',
					self::ELEMENT_FACTORY_PATTERN,
				),
			),
			array(
				'label'    => 'react',
				'code'     => 'inlined_react',
				'global'   => 'React',
				'patterns' => array(
					// Only the library itself assigns this export. `react-dom`
					// also assigns its own, which is fine because bundling the
					// renderer always bundles the library too, but
					// `react/jsx-runtime` merely reads it, so the assignment is
					// what tells the two apart.
					'/__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED\s*[:=][^=]/',
					self::ELEMENT_FACTORY_PATTERN,
				),
			),
			array(
				'label'    => 'react-dom',
				'code'     => 'inlined_react_dom',
				'global'   => 'ReactDOM',
				'patterns' => array(
					// The key under which the renderer caches the fiber on every
					// DOM node it owns, renamed in React 17. Nothing but the
					// renderer defines it, code that merely calls the renderer
					// does not, and it survives minification because it is a
					// string literal.
					//
					// The element factory is deliberately not required here. The
					// renderer consumes elements instead of creating them, so a
					// file holding nothing but a copy of `react-dom` has no
					// factory in it, and this marker is specific on its own.
					'/__reactFiber\$|__reactInternalInstance\$/',
				),
			),
		);
	}

	/**
	 * Determines whether the inlined build is a development build.
	 *
	 * Development builds embed documentation links in their warning messages,
	 * under `reactjs.org` up to React 18 and under `react.dev` from React 19.
	 * Production builds strip every warning, so neither link survives.
	 *
	 * @since 2.2.0
	 *
	 * @param string $contents Contents of the JavaScript file.
	 * @return bool True if the file inlines a development build, false otherwise.
	 */
	private function is_development_build( $contents ) {
		return 1 === preg_match( '#https://(?:reactjs\.org|react\.dev)/link/#', $contents );
	}

	/**
	 * Reports every call to a removed React API in a single file.
	 *
	 * @since 2.2.0
	 *
	 * @param Check_Result $result   The check result to amend.
	 * @param string       $file     Absolute path to the JavaScript file.
	 * @param string       $contents Contents of the JavaScript file.
	 */
	private function check_removed_apis( Check_Result $result, $file, $contents ) {
		// Blank out comments and string literals first, so a mention in a code
		// comment, changelog entry, or translation string is not reported as
		// usage.
		$scannable = $this->blank_comments_and_strings( $contents );

		foreach ( $this->get_removed_apis() as $api ) {
			$position = $this->find_first_match( $api['pattern'], $scannable );

			if ( false === $position ) {
				continue;
			}

			$this->add_result_warning_for_file(
				$result,
				$this->get_removed_api_message( $api ),
				'react_removed_api',
				$file,
				$position['line'],
				$position['column'],
				self::UPGRADE_DOCS_URL,
				5
			);
		}
	}

	/**
	 * Returns the message reported for a call to a removed API.
	 *
	 * Most of the removed APIs have a drop-in replacement, and the name of that
	 * replacement is code that must stay untranslated, so those share a single
	 * sentence with the name interpolated into it. The rest have no such
	 * replacement and need prose, which has to be part of the translated
	 * sentence rather than substituted into it.
	 *
	 * @since 2.2.0
	 *
	 * @param array $api Removed API definition as returned by `get_removed_apis()`.
	 * @return string The message to report.
	 */
	private function get_removed_api_message( array $api ) {
		if ( isset( $api['message'] ) ) {
			return $api['message'];
		}

		return sprintf(
			/* translators: 1: the removed React API name, 2: the name of the API replacing it */
			__( 'This file calls "%1$s", which was removed in React 19 and stops working once WordPress upgrades React. Use %2$s instead.', 'plugin-check' ),
			$api['name'],
			$api['replacement']
		);
	}

	/**
	 * Returns the public React APIs removed in React 19.
	 *
	 * Only the documented public surface is matched. Internals such as
	 * `ReactCurrentOwner` are deliberately left out: they never appear in plugin
	 * code, only inside a React build that the plugin inlined, which the inlined
	 * package errors cover.
	 *
	 * `render` and `hydrate` are common words, so they are only matched when
	 * called on a `ReactDOM` object. The remaining names are specific enough to
	 * match on their own.
	 *
	 * An entry carries either a `replacement`, naming the API to migrate to, or
	 * a complete `message` for the APIs that have no such replacement.
	 *
	 * @since 2.2.0
	 *
	 * @return array List of removed API definitions.
	 */
	private function get_removed_apis() {
		return array(
			array(
				'name'        => 'ReactDOM.render',
				'pattern'     => '/\bReactDOM\s*\.\s*render\s*\(/',
				'replacement' => 'createRoot()',
			),
			array(
				'name'        => 'ReactDOM.hydrate',
				'pattern'     => '/\bReactDOM\s*\.\s*hydrate\s*\(/',
				'replacement' => 'hydrateRoot()',
			),
			array(
				'name'        => 'ReactDOM.unmountComponentAtNode',
				'pattern'     => '/\bunmountComponentAtNode\s*\(/',
				'replacement' => 'root.unmount()',
			),
			array(
				'name'    => 'ReactDOM.findDOMNode',
				'pattern' => '/\bfindDOMNode\s*\(/',
				'message' => __( 'This file calls "ReactDOM.findDOMNode", which was removed in React 19 and stops working once WordPress upgrades React. Use a ref on the element instead.', 'plugin-check' ),
			),
			array(
				'name'        => 'ReactDOM.unstable_renderSubtreeIntoContainer',
				'pattern'     => '/\bunstable_renderSubtreeIntoContainer\s*\(/',
				'replacement' => 'createPortal()',
			),
			array(
				'name'        => 'ReactDOMServer.renderToNodeStream',
				'pattern'     => '/\brenderToNodeStream\s*\(/',
				'replacement' => 'renderToPipeableStream()',
			),
			array(
				'name'    => 'React.createFactory',
				'pattern' => '/\bReact\s*\.\s*createFactory\s*\(/',
				'message' => __( 'This file calls "React.createFactory", which was removed in React 19 and stops working once WordPress upgrades React. Use JSX or createElement() instead.', 'plugin-check' ),
			),
		);
	}

	/**
	 * Blanks out comments and literals in JavaScript contents.
	 *
	 * Characters inside line comments, block comments, quoted strings, and
	 * regular expression literals are replaced with spaces. The length of the
	 * contents and every newline are preserved, so match offsets still map to
	 * the correct line and column in the original file.
	 *
	 * The contents are tokenized rather than matched with a single regular
	 * expression, for two reasons. A regular expression cannot tell a regex
	 * literal from a division operator, so the quote in `/"/` was read as the
	 * start of a string and swallowed the code following it. PCRE also gives up
	 * on the long string literals of a bundled file, which left the contents
	 * unblanked and reported mentions in comments as calls.
	 *
	 * @since 2.2.0
	 *
	 * @param string $contents Contents of the JavaScript file.
	 * @return string The contents with comments and literals blanked out.
	 */
	private function blank_comments_and_strings( $contents ) {
		$length      = strlen( $contents );
		$blanked     = '';
		$copied      = 0;
		$offset      = 0;
		$after_value = false;

		while ( $offset < $length ) {
			// Whitespace does not change which token may come next.
			$offset += strspn( $contents, self::WHITESPACE_CHARACTERS, $offset );

			// Operators and punctuators all expect a value after them.
			$punctuation = strcspn( $contents, self::TOKEN_START_CHARACTERS, $offset );

			if ( $punctuation > 0 ) {
				$after_value = false;
				$offset     += $punctuation;
				continue;
			}

			if ( $offset >= $length ) {
				break;
			}

			// Identifiers, keywords, and numbers are consumed whole, so that the
			// slash in `return/^a$/.test( s )` is not taken for a division.
			$word = strspn( $contents, self::WORD_CHARACTERS, $offset );

			if ( $word > 0 ) {
				$after_value = ! in_array( substr( $contents, $offset, $word ), self::VALUE_EXPECTING_KEYWORDS, true );
				$offset     += $word;
				continue;
			}

			$char = $contents[ $offset ];
			$next = $offset + 1 < $length ? $contents[ $offset + 1 ] : '';

			if ( ')' === $char || ']' === $char ) {
				$after_value = true;
				++$offset;
				continue;
			}

			if ( '/' === $char && '/' === $next ) {
				$end = $offset + strcspn( $contents, "\r\n", $offset );
			} elseif ( '/' === $char && '*' === $next ) {
				$close = strpos( $contents, '*/', $offset + 2 );
				$end   = false === $close ? $length : $close + 2;
			} elseif ( '/' !== $char ) {
				$end         = $this->find_literal_end( $contents, $offset, $char );
				$after_value = true;
			} elseif ( ! $after_value ) {
				$end         = $this->find_literal_end( $contents, $offset, '/' );
				$after_value = true;
			} else {
				// A division operator.
				$after_value = false;
				++$offset;
				continue;
			}

			$blanked .= substr( $contents, $copied, $offset - $copied );
			$blanked .= preg_replace( '/[^\r\n]/', ' ', substr( $contents, $offset, $end - $offset ) );
			$copied   = $end;
			$offset   = $end;
		}

		return $blanked . substr( $contents, $copied );
	}

	/**
	 * Finds the offset just past the end of a string or regex literal.
	 *
	 * @since 2.2.0
	 *
	 * @param string $contents  Contents being scanned.
	 * @param int    $start     Offset of the opening delimiter.
	 * @param string $delimiter The delimiter that closes the literal.
	 * @return int Offset just past the literal, or where it is cut short by the end
	 *             of the line or of the contents.
	 */
	private function find_literal_end( $contents, $start, $delimiter ) {
		$length = strlen( $contents );

		// Characters that interrupt the literal: an escape, its own delimiter,
		// and a line break for the literals that may not span lines. In a regular
		// expression a character class opens too, because the slash inside one
		// does not close the literal.
		if ( '/' === $delimiter ) {
			$stops = "\\/[\r\n";
		} elseif ( '`' === $delimiter ) {
			$stops = '\\`';
		} else {
			$stops = '\\' . $delimiter . "\r\n";
		}

		$offset = $start + 1;

		while ( $offset < $length ) {
			$offset += strcspn( $contents, $stops, $offset );

			if ( $offset >= $length ) {
				break;
			}

			$char = $contents[ $offset ];

			if ( '\\' === $char ) {
				$offset += 2;
				continue;
			}

			if ( $delimiter === $char ) {
				return $offset + 1;
			}

			if ( '[' === $char ) {
				$offset = $this->find_character_class_end( $contents, $offset );
				continue;
			}

			// Cut short by the end of the line.
			return $offset;
		}

		return $length;
	}

	/**
	 * Finds the offset just past the end of a regex character class.
	 *
	 * @since 2.2.0
	 *
	 * @param string $contents Contents being scanned.
	 * @param int    $start    Offset of the opening bracket.
	 * @return int Offset just past the character class, or where it is cut short by
	 *             the end of the line or of the contents.
	 */
	private function find_character_class_end( $contents, $start ) {
		$length = strlen( $contents );
		$offset = $start + 1;

		while ( $offset < $length ) {
			$offset += strcspn( $contents, "\\]\r\n", $offset );

			if ( $offset >= $length ) {
				break;
			}

			if ( '\\' === $contents[ $offset ] ) {
				$offset += 2;
				continue;
			}

			// Closed, or cut short by the end of the line.
			return ']' === $contents[ $offset ] ? $offset + 1 : $offset;
		}

		return $length;
	}

	/**
	 * Finds the first occurrence of a pattern and returns its line and column.
	 *
	 * All three line endings are recognized, and a carriage return followed by a
	 * line feed counts once. The line ending of the file being read is what
	 * matters here, which is unrelated to the one native to the machine running
	 * the check.
	 *
	 * @since 2.2.0
	 *
	 * @param string $pattern  The regular expression pattern to search for.
	 * @param string $contents The contents to search.
	 * @return array|false Array with `line` and `column` keys, or false if no match was found.
	 */
	private function find_first_match( $pattern, $contents ) {
		if ( ! preg_match( $pattern, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
			return false;
		}

		$before = substr( $contents, 0, $matches[0][1] );
		$lines  = preg_split( '/\r\n|\n|\r/', $before );

		return array(
			'line'   => count( $lines ),
			'column' => strlen( (string) end( $lines ) ) + 1,
		);
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 2.2.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects React usage that breaks when WordPress upgrades to React 19.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 2.2.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return self::UPGRADE_DOCS_URL;
	}
}
