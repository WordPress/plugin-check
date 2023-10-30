<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Find_Readme
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for find readme.
 *
 * @since n.e.x.t
 */
trait Find_Readme {

	/**
	 * Filter the given array of files for readme files (readme.txt or readme.md).
	 *
	 * @since n.e.x.t
	 *
	 * @param array  $files                Array of file files to be filtered.
	 * @param string $plugin_relative_path Plugin relative path.
	 * @return array An array containing readme.txt or readme.md files, or an empty array if none are found.
	 */
	protected function filter_files_for_readme( array $files, $plugin_relative_path ) {
		// Find the readme file.
		$readme_list = self::filter_files_by_regex( $files, '/readme\.(txt|md)$/i' );

		// Filter the readme files located at root.
		$potential_readme_files = array_filter(
			$readme_list,
			function ( $file ) use ( $plugin_relative_path ) {
				$file = str_replace( $plugin_relative_path, '', $file );
				return ! str_contains( $file, '/' );
			}
		);

		// If the readme file does not exist, then return empty array.
		if ( empty( $potential_readme_files ) ) {
			return array();
		}

		// Find the .txt versions of the readme files.
		$readme_txt = array_filter(
			$potential_readme_files,
			function ( $file ) {
				return preg_match( '/^readme\.txt$/i', basename( $file ) );
			}
		);

		return $readme_txt ? $readme_txt : $potential_readme_files;
	}
}
