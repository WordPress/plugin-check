<?php
/**
 * Class Inlined_React_Runtime_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Performance;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check to detect a bundled, outdated React runtime that breaks under React 19.
 *
 * WordPress is moving from React 18 to React 19. Testing has shown that the vast
 * majority of plugin breakages come from a single cause: the plugin inlines the
 * `react/jsx-runtime` library into its build output instead of externalizing it
 * (i.e. relying on the copy shipped with WordPress). The element object shape
 * changed between React 18 and 19, so elements produced by an inlined pre-19
 * runtime are rejected by the React 19 bundled with WordPress.
 *
 * Detection is based on the high confidence signal `Symbol.for( 'react.element' )`,
 * which only appears when a pre-19 JSX runtime is inlined. React 19 uses a
 * different marker (`react.transitional.element`), so this does not match builds
 * that already externalize the runtime. As an additional signal, usages of React
 * APIs that were removed in React 19 are reported as well.
 *
 * @since 2.0.0
 */
class Inlined_React_Runtime_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 2.0.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PERFORMANCE );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 2.0.0
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

			$this->look_for_inlined_jsx_runtime( $result, $file, $contents );
			$this->look_for_removed_react_apis( $result, $file, $contents );
		}
	}

	/**
	 * Reports an inlined pre-19 JSX runtime, unless the runtime is externalized.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result $result   The check result to amend.
	 * @param string       $file     Absolute path to the JavaScript file.
	 * @param string       $contents Contents of the JavaScript file.
	 */
	private function look_for_inlined_jsx_runtime( Check_Result $result, $file, $contents ) {
		// `Symbol.for( 'react.element' )` is only emitted by an inlined pre-19 JSX runtime.
		$position = $this->find_first_match( '/Symbol\.for\(\s*[\'"]react\.element[\'"]\s*\)/', $contents );

		if ( false === $position ) {
			return;
		}

		// Do not warn when the runtime is externalized to the copy shipped with WordPress.
		if ( $this->is_jsx_runtime_externalized( $file, $contents ) ) {
			return;
		}

		$this->add_result_warning_for_file(
			$result,
			__( 'This file appears to inline the React JSX runtime instead of externalizing it. Bundled pre-React 19 runtimes break when WordPress upgrades to React 19. Use the dependency extraction webpack plugin so that "react-jsx-runtime" is loaded from WordPress instead.', 'plugin-check' ),
			'inlined_jsx_runtime',
			$file,
			$position['line'],
			$position['column'],
			'https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dependency-extraction-webpack-plugin/',
			6
		);
	}

	/**
	 * Reports usage of React APIs that were removed in React 19.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result $result   The check result to amend.
	 * @param string       $file     Absolute path to the JavaScript file.
	 * @param string       $contents Contents of the JavaScript file.
	 */
	private function look_for_removed_react_apis( Check_Result $result, $file, $contents ) {
		// These identifiers are React-specific and were removed in React 19.
		$position = $this->find_first_match( '/\b(?:unmountComponentAtNode|findDOMNode|ReactCurrentOwner)\b/', $contents, $matched );

		if ( false === $position ) {
			return;
		}

		$this->add_result_warning_for_file(
			$result,
			sprintf(
				/* translators: %s: the removed React API name */
				__( 'This file references "%s", a React API that was removed in React 19 and will stop working once WordPress upgrades React. Update the bundled code to a React 19 compatible version.', 'plugin-check' ),
				$matched
			),
			'react_removed_api',
			$file,
			$position['line'],
			$position['column'],
			'https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dependency-extraction-webpack-plugin/',
			5
		);
	}

	/**
	 * Determines whether the JSX runtime is externalized rather than inlined.
	 *
	 * A build that externalizes the runtime references the global
	 * `window.ReactJSXRuntime`, or declares `react-jsx-runtime` as a dependency in
	 * its sibling `*.asset.php` file generated by the dependency extraction plugin.
	 *
	 * @since 2.0.0
	 *
	 * @param string $file     Absolute path to the JavaScript file.
	 * @param string $contents Contents of the JavaScript file.
	 * @return bool True if the runtime is externalized, false otherwise.
	 */
	private function is_jsx_runtime_externalized( $file, $contents ) {
		if ( str_contains( $contents, 'window.ReactJSXRuntime' ) ) {
			return true;
		}

		$asset_file = preg_replace( '/\.js$/', '.asset.php', $file );

		if ( is_string( $asset_file ) && $asset_file !== $file && file_exists( $asset_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$asset_contents = file_get_contents( $asset_file );
			if ( false !== $asset_contents && str_contains( $asset_contents, 'react-jsx-runtime' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Finds the first occurrence of a pattern and returns its line and column.
	 *
	 * @since 2.0.0
	 *
	 * @param string      $pattern  The regular expression pattern to search for.
	 * @param string      $contents The contents to search.
	 * @param string|null $matched  Optional. Populated with the matched text, passed by reference.
	 * @return array|false Array with `line` and `column` keys, or false if no match was found.
	 */
	private function find_first_match( $pattern, $contents, &$matched = null ) {
		if ( ! preg_match( $pattern, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
			return false;
		}

		$matched = $matches[0][0];
		$offset  = $matches[0][1];

		if ( 0 === $offset ) {
			return array(
				'line'   => 1,
				'column' => 1,
			);
		}

		$before   = substr( $contents, 0, $offset );
		$exploded = explode( PHP_EOL, $before );

		return array(
			'line'   => count( $exploded ),
			'column' => strlen( (string) end( $exploded ) ) + 1,
		);
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 2.0.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects a bundled, outdated React runtime that is incompatible with React 19.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 2.0.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dependency-extraction-webpack-plugin/', 'plugin-check' );
	}
}
