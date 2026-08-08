<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Plugin_Config
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Reads optional plugin configuration.
 *
 * @since 2.1.0
 */
final class Plugin_Config {

	/**
	 * Configuration file name.
	 *
	 * @var string
	 */
	const FILE_NAME = 'plugin-check-info.json';

	/**
	 * Returns paths declared as third-party code.
	 *
	 * @since 2.1.0
	 *
	 * @param string $plugin_path Absolute plugin path.
	 * @return string[] Relative third-party paths.
	 */
	public static function get_third_party_paths( $plugin_path ) {
		$config_file = trailingslashit( $plugin_path ) . self::FILE_NAME;

		if ( ! is_readable( $config_file ) ) {
			return array();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read plugin-local JSON configuration.
		$contents = file_get_contents( $config_file );
		if ( false === $contents ) {
			return array();
		}

		$config = json_decode( $contents, true );

		if ( ! is_array( $config ) || empty( $config['third_parties'] ) || ! is_array( $config['third_parties'] ) ) {
			return array();
		}

		$paths = array();
		foreach ( $config['third_parties'] as $path ) {
			if ( ! is_string( $path ) ) {
				continue;
			}

			$path = self::normalize_path( $path );
			if ( '' !== $path ) {
				$paths[] = $path;
			}
		}

		return array_values( array_unique( $paths ) );
	}

	/**
	 * Checks whether a relative file is inside a declared path.
	 *
	 * @since 2.1.0
	 *
	 * @param string   $file Relative file path.
	 * @param string[] $paths Relative third-party paths.
	 * @return bool Whether the file is inside a declared path.
	 */
	public static function is_third_party_file( $file, array $paths ) {
		$file = self::normalize_path( $file );

		if ( '' === $file ) {
			return false;
		}

		foreach ( $paths as $path ) {
			if ( $file === $path || 0 === strpos( $file, $path . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalizes and validates a relative plugin path.
	 *
	 * @param string $path Plugin-relative path.
	 * @return string Normalized path, or empty string when invalid.
	 */
	private static function normalize_path( $path ) {
		$path = trim( str_replace( '\\', '/', $path ), " /\t\n\r\0\x0B" );

		if ( '' === $path || '/' === substr( $path, 0, 1 ) || preg_match( '#^[A-Za-z]:/#', $path ) ) {
			return '';
		}

		$segments = explode( '/', $path );
		foreach ( $segments as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return '';
			}
		}

		return implode( '/', $segments );
	}
}
