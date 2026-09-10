<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Ignore_Matcher
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Class providing helpers to match files against directory and file ignore entries.
 *
 * Ignore entries beginning with a forward slash are anchored to the plugin
 * root, such as entries sourced from a `.pcpignore` file, and may include
 * `*` and `?` wildcards. All other entries are unanchored, preserving the
 * historical, any-depth matching behavior of the default exclusions and the
 * `--exclude-directories` / `--exclude-files` CLI options.
 *
 * @since 2.2.0
 */
class Ignore_Matcher {

	/**
	 * Splits ignore entries into those anchored to the plugin root and those that are not.
	 *
	 * @since 2.2.0
	 *
	 * @param array $entries Ignore entries.
	 * @return array An indexed array with the anchored entries first, followed by the unanchored entries.
	 */
	public static function split_anchored_entries( array $entries ) {
		$anchored   = array();
		$unanchored = array();

		foreach ( $entries as $entry ) {
			if ( '' === $entry ) {
				continue;
			}

			if ( '/' === $entry[0] ) {
				$anchored[] = $entry;
			} else {
				$unanchored[] = $entry;
			}
		}

		return array( $anchored, $unanchored );
	}

	/**
	 * Determines whether a file is inside a directory that should be ignored.
	 *
	 * Anchored directories only match a directory at that exact location
	 * relative to the plugin root. Unanchored directories match at any depth.
	 *
	 * @since 2.2.0
	 *
	 * @param string $file_path             Absolute, normalized path to the file being checked.
	 * @param string $plugin_root           Absolute, normalized path to the plugin root directory, without a trailing slash.
	 * @param array  $directories_to_ignore Directories to ignore.
	 * @return bool True if the file is inside an ignored directory.
	 */
	public static function is_file_in_ignored_directory( $file_path, $plugin_root, array $directories_to_ignore ) {
		foreach ( $directories_to_ignore as $directory ) {
			if ( '' === $directory ) {
				continue;
			}

			if ( '/' === $directory[0] ) {
				$anchored_directory = $plugin_root . $directory;

				if ( false !== strpbrk( $directory, '*?' ) ) {
					if ( preg_match( self::glob_to_regex( $anchored_directory . '/*' ), $file_path ) ) {
						return true;
					}
				} elseif ( 0 === strpos( $file_path, $anchored_directory . '/' ) ) {
					return true;
				}

				continue;
			}

			if ( false !== strpos( $file_path, '/' . $directory . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines whether a file should be ignored.
	 *
	 * Anchored files may include `*` and `?` wildcards and only match at
	 * that exact location relative to the plugin root. Unanchored files
	 * match at any depth, by filename suffix.
	 *
	 * @since 2.2.0
	 *
	 * @param string $file_path       Absolute, normalized path to the file being checked.
	 * @param string $plugin_root     Absolute, normalized path to the plugin root directory, without a trailing slash.
	 * @param array  $files_to_ignore Files to ignore.
	 * @return bool True if the file should be ignored.
	 */
	public static function is_file_ignored( $file_path, $plugin_root, array $files_to_ignore ) {
		foreach ( $files_to_ignore as $file ) {
			if ( '' === $file ) {
				continue;
			}

			if ( '/' === $file[0] ) {
				$anchored_file = $plugin_root . $file;

				if ( false !== strpbrk( $file, '*?' ) ) {
					if ( preg_match( self::glob_to_regex( $anchored_file ), $file_path ) ) {
						return true;
					}
				} elseif ( $file_path === $anchored_file ) {
					return true;
				}

				continue;
			}

			if ( str_ends_with( $file_path, '/' . $file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Converts a glob-style pattern (using `*` and `?` wildcards) to a regular expression.
	 *
	 * @since 2.2.0
	 *
	 * @param string $pattern The glob-style pattern.
	 * @return string The equivalent case-insensitive, fully anchored regular expression.
	 */
	private static function glob_to_regex( $pattern ) {
		$regex = preg_quote( $pattern, '#' );
		$regex = str_replace( array( '\*', '\?' ), array( '.*', '.' ), $regex );

		return '#^' . $regex . '$#i';
	}
}
