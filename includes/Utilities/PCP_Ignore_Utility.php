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
 * @since 2.2.0
 */
class PCP_Ignore_Utility {

	/**
	 * Marker prefix used to flag an exclusion entry as anchored to the plugin root.
	 *
	 * Entries parsed from a `.pcpignore` file are always anchored to the plugin
	 * root, as documented. This is distinct from the unanchored matching used
	 * for built-in default exclusions and the `--exclude-directories` /
	 * `--exclude-files` CLI options, which intentionally match at any depth.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	const ROOT_ANCHOR = '/';

	/**
	 * The most recent warning generated while parsing a `.pcpignore` file, if any.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	private static $warning = '';

	/**
	 * Gets the custom file and directory exclusions from a .pcpignore file.
	 *
	 * Each non-empty, non-comment line is treated as a path relative to the
	 * plugin root. A trailing slash denotes a directory; all other entries
	 * denote files. Entries may include `*` and `?` wildcards. All entries
	 * are anchored to the plugin root, meaning `docs/` only excludes a
	 * top-level `docs` directory, not any directory named `docs` at another
	 * depth.
	 *
	 * If the file exists but cannot be read or parsed, a warning is recorded
	 * and can be retrieved via {@see self::get_warning()}. The scan itself is
	 * never interrupted by an invalid or unreadable `.pcpignore` file.
	 *
	 * @since 2.2.0
	 *
	 * @param string $plugin_path Plugin directory or main plugin file path.
	 * @return array{directories: array, files: array} Custom exclusions, anchored to the plugin root.
	 */
	public static function get_exclusions( $plugin_path ) {
		self::$warning = '';

		$empty_exclusions = array(
			'directories' => array(),
			'files'       => array(),
		);

		$plugin_directory = self::resolve_plugin_directory( $plugin_path );

		if ( null === $plugin_directory ) {
			return $empty_exclusions;
		}

		$lines = self::read_ignore_file_lines( trailingslashit( $plugin_directory ) . '.pcpignore' );

		if ( null === $lines ) {
			return $empty_exclusions;
		}

		return self::parse_exclusions( $lines );
	}

	/**
	 * Resolves the plugin directory that may hold a `.pcpignore` file.
	 *
	 * @since 2.2.0
	 *
	 * @param string $plugin_path Plugin directory or main plugin file path.
	 * @return string|null The normalized, untrailingslashed plugin directory, or null
	 *                      if the plugin is a single-file plugin, which is not supported.
	 */
	private static function resolve_plugin_directory( $plugin_path ) {
		$plugin_directory = is_dir( $plugin_path ) ? $plugin_path : dirname( $plugin_path );
		$plugin_directory = untrailingslashit( wp_normalize_path( $plugin_directory ) );

		// Single-file plugins live directly inside the shared plugins directory
		// and have no dedicated directory of their own to hold a .pcpignore
		// file. Resolving to WP_PLUGIN_DIR would incorrectly apply exclusions
		// meant for one plugin to every other plugin scanned from that shared
		// location, so .pcpignore is not supported for single-file plugins.
		if ( untrailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) ) === $plugin_directory ) {
			return null;
		}

		return $plugin_directory;
	}

	/**
	 * Reads and returns the non-empty lines of a `.pcpignore` file.
	 *
	 * Records a warning, retrievable via {@see self::get_warning()}, if the
	 * file exists but could not be read or parsed.
	 *
	 * @since 2.2.0
	 *
	 * @param string $ignore_file Absolute path to the `.pcpignore` file.
	 * @return array|null The file lines, or null if the file does not exist,
	 *                     could not be read, or could not be parsed.
	 */
	private static function read_ignore_file_lines( $ignore_file ) {
		if ( ! file_exists( $ignore_file ) ) {
			return null;
		}

		if ( ! is_readable( $ignore_file ) ) {
			self::$warning = sprintf(
				/* translators: %s: Path to the .pcpignore file. */
				__( 'The .pcpignore file at %s could not be read and was ignored.', 'plugin-check' ),
				$ignore_file
			);

			return null;
		}

		$lines = file( $ignore_file, FILE_IGNORE_NEW_LINES );

		if ( false === $lines ) {
			self::$warning = sprintf(
				/* translators: %s: Path to the .pcpignore file. */
				__( 'The .pcpignore file at %s could not be parsed and was ignored.', 'plugin-check' ),
				$ignore_file
			);

			return null;
		}

		return $lines;
	}

	/**
	 * Parses `.pcpignore` file lines into directory and file exclusions.
	 *
	 * @since 2.2.0
	 *
	 * @param array $lines The `.pcpignore` file lines.
	 * @return array{directories: array, files: array} Custom exclusions, anchored to the plugin root.
	 */
	private static function parse_exclusions( array $lines ) {
		$exclusions = array(
			'directories' => array(),
			'files'       => array(),
		);

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line || '#' === substr( $line, 0, 1 ) ) {
				continue;
			}

			$is_directory = '/' === substr( $line, -1 );
			$line         = self::ROOT_ANCHOR . ltrim( wp_normalize_path( $line ), '/' );

			if ( $is_directory ) {
				$exclusions['directories'][] = untrailingslashit( $line );
			} else {
				$exclusions['files'][] = $line;
			}
		}

		$exclusions['directories'] = array_unique( $exclusions['directories'] );
		$exclusions['files']       = array_unique( array_merge( $exclusions['files'], array( self::ROOT_ANCHOR . '.pcpignore' ) ) );

		return $exclusions;
	}

	/**
	 * Gets the warning generated by the most recent call to {@see self::get_exclusions()}.
	 *
	 * @since 2.2.0
	 *
	 * @return string The warning message, or an empty string if none was generated.
	 */
	public static function get_warning() {
		return self::$warning;
	}

	/**
	 * Adds .pcpignore exclusions to the current scan.
	 *
	 * This method must only be called by an explicit local or CI opt-in. It
	 * does not run automatically, so WordPress.org scans retain every file.
	 *
	 * @since 2.2.0
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
