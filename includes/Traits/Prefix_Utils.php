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

			$plugin_root           = untrailingslashit( $location );
			$directories_to_ignore = Plugin_Request_Utility::get_directories_to_ignore();
			$files_to_ignore       = Plugin_Request_Utility::get_files_to_ignore();

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

				if ( Plugin_Request_Utility::is_file_in_ignored_directory( $file_path, $plugin_root, $directories_to_ignore ) ) {
					continue;
				}

				if ( Plugin_Request_Utility::is_file_ignored( $file_path, $plugin_root, $files_to_ignore ) ) {
					continue;
				}

				self::$file_list_cache[ $location ][] = $file_path;
			}
		}

		return self::$file_list_cache[ $location ];
	}
}
