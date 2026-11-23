<?php
/**
 * Class JQuery_Deprecated_APIs_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\General;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Experimental_Check;
use WordPress\Plugin_Check\Traits\JS_Source_Scanner;

/**
 * Check to detect jQuery APIs that are removed in jQuery 4.0.
 *
 * WordPress 7.2 ships jQuery Migrate 3.6.0 so that developers can find these
 * calls before core moves to jQuery 4, and jQuery Migrate is planned to be
 * disabled by default after that. A call to a removed API keeps working today
 * and stops working then, so it is reported as a warning.
 *
 * Only calls to the static utilities are detected, written as `jQuery.trim()`
 * or `$.trim()`. The deprecated instance methods such as `.bind()` cannot be
 * told apart from the native methods of the same name without type
 * information, and the ones that are deprecated rather than removed still work.
 *
 * @since 2.3.0
 */
class JQuery_Deprecated_APIs_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Experimental_Check;
	use JS_Source_Scanner;

	/**
	 * URL of the jQuery 4 announcement for WordPress.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const DOCS_URL = 'https://make.wordpress.org/core/2026/11/17/preparing-for-jquery-4-with-wordpress-7-2/';

	/**
	 * Characters that may appear in a JavaScript identifier, keyword, or number.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const WORD_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_$';

	/**
	 * Characters that separate JavaScript tokens without being one.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const WHITESPACE_CHARACTERS = " \t\n\r";

	/**
	 * Characters that begin a token the tokenizer has to look at.
	 *
	 * Anything else is an operator or punctuator, which the tokenizer only needs
	 * to know it has passed.
	 *
	 * @since 2.3.0
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
	 * @since 2.3.0
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
	 * Pattern matching the start of a reference to the jQuery global.
	 *
	 * It accepts `jQuery`, `$` and `window.jQuery`, and refuses a longer name or
	 * a property access, so `foo$` and `obj.$` are not mistaken for jQuery.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const JQUERY_REFERENCE_PATTERN = '(?<![\w$.])(?:window\s*\.\s*)?(?:jQuery|\$)\s*\.\s*';

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 2.3.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_GENERAL );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 2.3.0
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
			if ( $this->is_minified_file( $file ) || $this->is_jquery_file( $file ) ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $file );
			if ( false === $contents ) {
				continue;
			}

			$this->check_removed_apis( $result, $file, $contents );
		}
	}

	/**
	 * Reports every removed jQuery API used in a single file.
	 *
	 * Each API is reported once per file, at its first use.
	 *
	 * @since 2.3.0
	 *
	 * @param Check_Result $result   The check result to amend.
	 * @param string       $file     Absolute path to the JavaScript file.
	 * @param string       $contents Contents of the JavaScript file.
	 */
	private function check_removed_apis( Check_Result $result, $file, $contents ) {
		// Blank out comments and string literals first, so a mention in a code
		// comment or translation string is not reported as usage.
		$scannable = $this->blank_comments_and_strings( $contents );

		foreach ( $this->get_removed_apis() as $api ) {
			$position = $this->find_first_match( '/' . self::JQUERY_REFERENCE_PATTERN . $api['pattern'] . '/', $scannable );

			if ( false === $position ) {
				continue;
			}

			$this->add_result_warning_for_file(
				$result,
				$api['message'],
				'jquery_removed_api',
				$file,
				$position['line'],
				$position['column'],
				self::DOCS_URL,
				5
			);
		}
	}

	/**
	 * Checks whether a file is minified, going by its name.
	 *
	 * @since 2.3.0
	 *
	 * @param string $file Absolute path to the JavaScript file.
	 * @return bool True for a `.min.js` file.
	 */
	private function is_minified_file( $file ) {
		return '.min.js' === strtolower( substr( $file, -7 ) );
	}

	/**
	 * Checks whether a file is a copy of jQuery itself or of one of its libraries.
	 *
	 * Those define the removed utilities rather than call them, so reporting them
	 * would only flag a vendored file. The file is recognized by its name, or by the
	 * license banner of jQuery, jQuery Migrate and jQuery UI at the top of it.
	 *
	 * @since 2.3.0
	 *
	 * @param string $file Absolute path to the JavaScript file.
	 * @return bool True when the file is part of jQuery.
	 */
	private function is_jquery_file( $file ) {
		if ( preg_match( '/^jquery[.-]/i', basename( $file ) ) ) {
			return true;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$header = file_get_contents( $file, false, null, 0, 2048 );

		return false !== $header && (bool) preg_match( '/jQuery (?:JavaScript Library|Migrate|UI)\b[\s-]*v?\d|jquery\.org\/license/i', $header );
	}

	/**
	 * Returns the jQuery APIs removed in jQuery 4.0.
	 *
	 * The list is the one jQuery Migrate reports as removed. Deprecated APIs that
	 * jQuery 4 still ships, such as `.bind()` and `jQuery.proxy()`, are left out.
	 *
	 * @since 2.3.0
	 *
	 * @return array[] Removed APIs, each with the pattern matching a use of it
	 *                 (following the reference to jQuery) and the message to report.
	 */
	private function get_removed_apis() {
		/* translators: 1: removed jQuery API, 2: replacement */
		$replace = __( '%1$s is removed in jQuery 4.0. Use %2$s instead.', 'plugin-check' );

		/* translators: %s: removed jQuery API */
		$no_replacement = __( '%s is removed in jQuery 4.0 and has no replacement.', 'plugin-check' );

		return array(
			array(
				'pattern' => 'isArray\s*\)?\s*\(',
				'message' => sprintf( $replace, 'jQuery.isArray()', 'Array.isArray()' ),
			),
			array(
				'pattern' => 'parseJSON\s*\)?\s*\(',
				'message' => sprintf( $replace, 'jQuery.parseJSON()', 'JSON.parse()' ),
			),
			array(
				'pattern' => 'trim\s*\)?\s*\(',
				'message' => sprintf( $replace, 'jQuery.trim()', 'String.prototype.trim()' ),
			),
			array(
				'pattern' => 'now\s*\)?\s*\(',
				'message' => sprintf( $replace, 'jQuery.now()', 'Date.now()' ),
			),
			array(
				'pattern' => 'unique\s*\)?\s*\(',
				'message' => sprintf( $replace, 'jQuery.unique()', 'jQuery.uniqueSort()' ),
			),
			array(
				'pattern' => 'isFunction\s*\)?\s*\(',
				'message' => sprintf( $replace, 'jQuery.isFunction()', 'typeof value === "function"' ),
			),
			array(
				'pattern' => 'nodeName\s*\)?\s*\(',
				'message' => sprintf( $replace, 'jQuery.nodeName()', 'element.nodeName.toLowerCase()' ),
			),
			array(
				'pattern' => 'isNumeric\s*\)?\s*\(',
				'message' => __( 'jQuery.isNumeric() is removed in jQuery 4.0. Use Number.isFinite() or a custom check instead.', 'plugin-check' ),
			),
			array(
				'pattern' => 'isWindow\s*\)?\s*\(',
				'message' => __( 'jQuery.isWindow() is removed in jQuery 4.0. If it is needed, use obj != null && obj === obj.window instead.', 'plugin-check' ),
			),
			array(
				'pattern' => 'type\s*\)?\s*\(',
				'message' => __( 'jQuery.type() is removed in jQuery 4.0. Use typeof, Array.isArray(), instanceof or Object.prototype.toString.call() instead.', 'plugin-check' ),
			),
			array(
				'pattern' => 'camelCase\s*\)?\s*\(',
				'message' => __( 'jQuery.camelCase() is removed in jQuery 4.0. Use a custom utility instead.', 'plugin-check' ),
			),
			array(
				'pattern' => 'cssProps\b',
				'message' => sprintf( $no_replacement, 'jQuery.cssProps' ),
			),
			array(
				'pattern' => 'fx\s*\.\s*interval\b',
				'message' => sprintf( $no_replacement, 'jQuery.fx.interval' ),
			),
			array(
				'pattern' => 'event\s*\.\s*global\b',
				'message' => sprintf( $no_replacement, 'jQuery.event.global' ),
			),
			array(
				'pattern' => 'Deferred\s*\.\s*getStackHook\b',
				'message' => sprintf( $replace, 'jQuery.Deferred.getStackHook', 'jQuery.Deferred.getErrorHook' ),
			),
		);
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 2.3.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects jQuery APIs that are removed in jQuery 4.0.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 2.3.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return self::DOCS_URL;
	}
}
