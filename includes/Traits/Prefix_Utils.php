<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Prefix_Utils
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Scanner\Prefix_Scanner;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

/**
 * Trait for prefix utilities.
 *
 * @since 1.7.0
 */
trait Prefix_Utils {

	/**
	 * Internal cache for plugin-specific file lists.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private static $file_list_cache = array();

	/**
	 * Returns potential prefixes.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result The check result to amend.
	 * @return array An array of potential prefixes.
	 */
	protected function get_potential_prefixes( Check_Result $result ) {
		$files = self::get_files( $result->plugin() );

		$obj = new Prefix_Scanner();

		$obj->load_files( $files );

		$potential_prefixes = $obj->final_prefixes;

		return $potential_prefixes;
	}

	/**
	 * Gets the list of all files that are part of the given plugin.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Context $plugin Context for the plugin to check.
	 * @return array List of absolute file paths.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	private static function get_files( Check_Context $plugin ) {
		$location = wp_normalize_path( $plugin->location() );

		if ( isset( self::$file_list_cache[ $location ] ) ) {
			return self::$file_list_cache[ $location ];
		}

		self::$file_list_cache[ $location ] = array();

		// If the location is a plugin folder, get all its files.
		// Otherwise, it is a single-file plugin.
		if ( $plugin->is_single_file_plugin() ) {
			self::$file_list_cache[ $location ][] = $location;
		} else {
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $location ) );
			foreach ( $iterator as $file ) {
				if ( ! $file->isFile() ) {
					continue;
				}

				// Only .php files.
				$file_extension = pathinfo( $file->getFilename(), PATHINFO_EXTENSION );
				if ( empty( $file_extension ) || ! in_array( $file_extension, array( 'php', 'phtml' ), true ) ) {
					continue;
				}

				$file_path = wp_normalize_path( $file->getPathname() );

				$directories_to_ignore = Plugin_Request_Utility::get_directories_to_ignore();

				// Flag to check if the file should be included or not.
				$include_file = true;

				foreach ( $directories_to_ignore as $directory ) {
					// Check if the current file belongs to the directory you want to ignore.
					if ( false !== strpos( $file_path, '/' . $directory . '/' ) ) {
						$include_file = false;
						break; // Skip the file if it matches any ignored directory.
					}
				}

				$files_to_ignore = Plugin_Request_Utility::get_files_to_ignore();

				foreach ( $files_to_ignore as $ignore_file ) {
					if ( str_ends_with( $file_path, "/$ignore_file" ) ) {
						$include_file = false;
						break;
					}
				}

				if ( $include_file ) {
					self::$file_list_cache[ $location ][] = $file_path;
				}
			}
		}

		return self::$file_list_cache[ $location ];
	}
}
