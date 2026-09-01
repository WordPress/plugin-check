<?php
/**
 * Class WordPress\Plugin_Check\Utilities\PCP_Ignore_Utility
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Class providing opt-in .pcpignore exclusions for local and CI scans.
 *
 * @since n.e.x.t
 */
class PCP_Ignore_Utility {

	/**
	 * Gets the custom file and directory exclusions from a .pcpignore file.
	 *
	 * Each non-empty, non-comment line is treated as a path relative to the
	 * plugin root. A trailing slash denotes a directory; all other entries
	 * denote files.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $plugin_path Plugin directory or main plugin file path.
	 * @return array{directories: array, files: array} Custom exclusions.
	 */
	public static function get_exclusions( $plugin_path ) {
		$plugin_directory = is_dir( $plugin_path ) ? $plugin_path : dirname( $plugin_path );
		$ignore_file      = trailingslashit( $plugin_directory ) . '.pcpignore';
		$exclusions       = array(
			'directories' => array(),
			'files'       => array(),
		);

		if ( ! is_readable( $ignore_file ) ) {
			return $exclusions;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$lines = file( $ignore_file, FILE_IGNORE_NEW_LINES );

		if ( false === $lines ) {
			return $exclusions;
		}

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line || '#' === substr( $line, 0, 1 ) ) {
				continue;
			}

			$is_directory = '/' === substr( $line, -1 );
			$line         = ltrim( wp_normalize_path( $line ), '/' );

			if ( $is_directory ) {
				$exclusions['directories'][] = untrailingslashit( $line );
			} else {
				$exclusions['files'][] = $line;
			}
		}

		$exclusions['directories'] = array_unique( $exclusions['directories'] );
		$exclusions['files']       = array_unique( array_merge( $exclusions['files'], array( '.pcpignore' ) ) );

		return $exclusions;
	}

	/**
	 * Adds .pcpignore exclusions to the current scan.
	 *
	 * This method must only be called by an explicit local or CI opt-in. It
	 * does not run automatically, so WordPress.org scans retain every file.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $plugin_path Plugin directory or main plugin file path.
	 */
	public static function apply_exclusions( $plugin_path ) {
		$exclusions = self::get_exclusions( $plugin_path );

		add_filter(
			'wp_plugin_check_ignore_directories',
			static function ( $directories ) use ( $exclusions ) {
				return array_unique( array_merge( $directories, $exclusions['directories'] ) );
			}
		);

		add_filter(
			'wp_plugin_check_ignore_files',
			static function ( $files ) use ( $exclusions ) {
				return array_unique( array_merge( $files, $exclusions['files'] ) );
			}
		);
	}
}
