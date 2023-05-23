<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Static_Check;

/**
 * Base class for a check that inspects the plugin's files and contents.
 *
 * @since n.e.x.t
 */
abstract class Abstract_File_Check implements Static_Check {

	/**
	 * Internal cache for plugin-specific file lists.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private static $file_list_cache = array();

	/**
	 * Internal cache for file contents.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private static $file_contents_cache = array();

	/**
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	final public function run( Check_Result $result ) {
		$files = self::get_files( $result->plugin() );
		$this->check_files( $result, $files );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	abstract protected function check_files( Check_Result $result, array $files );

	/**
	 * Filters a given list of files to only contain those with specific extension.
	 *
	 * @since n.e.x.t
	 *
	 * @param array  $files     List of absolute file paths.
	 * @param string $extension File extension to match.
	 * @return array Filtered $files list.
	 */
	final protected static function filter_files_by_extension( array $files, $extension ) {
		return self::filter_files_by_extensions( $files, array( $extension ) );
	}

	/**
	 * Filters a given list of files to only contain those with specific extensions.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $files      List of absolute file paths.
	 * @param array $extensions List of file extensions to match.
	 * @return array Filtered $files list.
	 */
	final protected static function filter_files_by_extensions( array $files, array $extensions ) {
		// Inverse the array to speed up lookup.
		$lookup = array_flip( $extensions );

		return array_values(
			array_filter(
				$files,
				static function( $file ) use ( $lookup ) {
					return isset( $lookup[ pathinfo( $file, PATHINFO_EXTENSION ) ] );
				}
			)
		);
	}

	/**
	 * Filters a given list of files to only contain those where the file name matches the given regular expression.
	 *
	 * @since n.e.x.t
	 *
	 * @param array  $files List of absolute file paths.
	 * @param string $regex Regular expression for file paths to match.
	 * @return array Filtered $files list.
	 */
	final protected static function filter_files_by_regex( array $files, $regex ) {
		return preg_grep( $regex, $files );
	}

	/**
	 * Performs a regular expression match on the file contents of the given list of files.
	 *
	 * This is a wrapper around the native `preg_match()` function that will match the first occurrence within the
	 * list of files.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $pattern The pattern to search for.
	 * @param array  $files   List of absolute file paths.
	 * @param array  $matches Optional. Array to store the matches, passed by reference. Similar to `preg_match()`,
	 *                        `$matches[0]` will contain the text that matched the full pattern, `$matches[1]` will
	 *                        have the text that matched the first captured parenthesized subpattern, and so on.
	 * @return string|bool File path if a match was found, false otherwise.
	 */
	final protected static function file_preg_match( $pattern, array $files, array &$matches = null ) {
		foreach ( $files as $file ) {
			$contents = self::file_get_contents( $file );
			if ( preg_match( $regex, $contents, $m ) ) {
				$matches = $m;
				return $file;
			}
		}
		return false;
	}

	/**
	 * Performs a check indicating if the needle is contained in the file contents of the given list of files.
	 *
	 * This is a wrapper around the native `str_contains()` function that will find the needle within the list of
	 * files.
	 *
	 * @since n.e.x.t
	 *
	 * @param array  $files  List of absolute file paths.
	 * @param string $needle The substring to search for.
	 * @return string|bool File path if needle was found, false otherwise.
	 */
	final protected static function file_str_contains( array $files, string $needle ) {
		// Backward compatibility prior to PHP 8.
		$find_needle = 'str_contains';
		if ( ! function_exists( 'str_contains' ) ) {
			$find_needle = function( $haystack, $needle ) {
				return false !== strpos( $haystack, $needle );
			};
		}

		foreach ( $files as $file ) {
			$contents = self::file_get_contents( $file );
			if ( call_user_func( $find_needle, $contents, $needle ) ) {
				return $file;
			}
		}
		return false;
	}

	/**
	 * Gets the contents of the given file.
	 *
	 * This is effectively a caching wrapper around the native `file_get_contents()` function.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $file The file name.
	 * @return string The file contents.
	 */
	private static function file_get_contents( $file ) {
		if ( isset( self::$file_contents_cache[ $file ] ) ) {
			return self::$file_contents_cache[ $file ];
		}

		self::$file_contents_cache[ $file ] = file_get_contents( $file );

		return self::$file_contents_cache[ $file ];
	}

	/**
	 * Gets the list of all files that are part of the given plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Context $plugin Context for the plugin to check.
	 * @return array List of absolute file paths.
	 */
	private static function get_files( Check_Context $plugin ) {
		$location = $plugin->location();

		if ( isset( self::$file_list_cache[ $location ] ) ) {
			return self::$file_list_cache[ $location ];
		}

		self::$file_list_cache[ $location ] = array();

		// If the location is a plugin folder, get all its files.
		// Otherwise, it is a single-file plugin.
		if ( $plugin->path() === $location ) {
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $location ) );
			foreach ( $iterator as $file ) {
				if ( ! $file->isFile() ) {
					continue;
				}

				self::$file_list_cache[ $location ][] = $file->getPathname();
			}
		} else {
			self::$file_list_cache[ $location ][] = $location;
		}

		return self::$file_list_cache[ $location ];
	}
}
