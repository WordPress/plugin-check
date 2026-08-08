<?php
/**
 * Class Menu_Image_Icon_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check to detect raster image files used as admin menu icons.
 *
 * This check detects when plugins use raster image files (such as PNG, JPG, or GIF)
 * as the icon parameter in the add_menu_page() function. Raster images do not adapt
 * to the different WordPress admin color schemes, so a dashicon or an SVG data: URI
 * should be used instead.
 *
 * @since 2.1.0
 */
class Menu_Image_Icon_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * List of raster image file extensions that do not adapt to admin color schemes.
	 *
	 * SVGs are intentionally not listed as they can be used via a data: URI and adapt
	 * to the active admin color scheme.
	 *
	 * @since 2.1.0
	 * @var array
	 */
	protected $image_extensions = array(
		'png',
		'jpg',
		'jpeg',
		'gif',
		'webp',
		'ico',
		'bmp',
	);

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 2.1.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );

		$this->look_for_menu_image_icons( $result, $php_files );
	}

	/**
	 * Looks for raster image files used as admin menu icons and amends the result with a warning if found.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_menu_image_icons( Check_Result $result, array $php_files ) {
		$matches = self::file_scan_add_menu_page_icons( $php_files );

		if ( empty( $matches ) ) {
			return;
		}

		foreach ( $matches as $match ) {
			if ( ! $this->is_raster_image_icon( $match['icon'] ) ) {
				continue;
			}

			$this->add_result_warning_for_file(
				$result,
				__(
					'<strong>Raster image used as admin menu icon.</strong><br>Plugins should use a dashicon or an SVG data: URI as the admin menu icon, as raster image files do not adapt to the WordPress admin color schemes.',
					'plugin-check'
				),
				'menu_image_icon',
				$match['file'],
				$match['line'],
				$match['column'],
				'https://developer.wordpress.org/resource/dashicons/',
				4
			);
		}
	}

	/**
	 * Scans PHP files for add_menu_page() calls with a quoted string icon parameter.
	 *
	 * The icon URL is the sixth parameter of add_menu_page(). A regex is used to count
	 * to the sixth parameter, capturing the icon string along with the file position.
	 *
	 * @since 2.1.0
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 *
	 * @param array $php_files List of absolute PHP file paths.
	 * @return array List of matches containing the file, line, column, and icon string.
	 */
	private static function file_scan_add_menu_page_icons( array $php_files ) {
		$results = array();

		foreach ( $php_files as $file ) {
			$contents = self::file_contents( $file );
			$tokens   = token_get_all( $contents );
			$offset   = 0;
			$scanned  = array();

			foreach ( $tokens as $token ) {
				$text      = is_array( $token ) ? $token[1] : $token;
				$scanned[] = array(
					'id'     => is_array( $token ) ? $token[0] : null,
					'text'   => $text,
					'offset' => $offset,
				);
				$offset   += strlen( $text );
			}

			$count = count( $scanned );
			for ( $index = 0; $index < $count; $index++ ) {
				if ( T_STRING !== $scanned[ $index ]['id'] || 'add_menu_page' !== strtolower( $scanned[ $index ]['text'] ) ) {
					continue;
				}

				$open = $index + 1;
				while ( $open < $count && in_array( $scanned[ $open ]['id'], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					$open += 1;
				}
				if ( $open >= $count || '(' !== $scanned[ $open ]['text'] ) {
					continue;
				}

				$args  = array( '' );
				$depth = 1;
				for ( $cursor = $open + 1; $cursor < $count; $cursor++ ) {
					$text = $scanned[ $cursor ]['text'];
					if ( '(' === $text ) {
						$depth += 1;
					} elseif ( ')' === $text ) {
						$depth -= 1;
						if ( $depth < 1 ) {
							break;
						}
					} elseif ( ',' === $text && 1 === $depth ) {
						$args[] = '';
						continue;
					}
					$args[ count( $args ) - 1 ] .= $text;
				}

				if ( isset( $args[5] ) && preg_match( '/^\s*([\'"])(.*?)\1\s*$/s', $args[5], $match ) ) {
					$results[] = array(
						'file'   => $file,
						'line'   => self::offset_to_line( $contents, $scanned[ $index ]['offset'] ),
						'column' => self::offset_to_column( $contents, $scanned[ $index ]['offset'] ),
						'icon'   => $match[2],
					);
				}
			}
		}

		return $results;
	}

	/**
	 * Determines whether the given icon value is a raster image file.
	 *
	 * A value is considered a raster image icon when it is a path or URL whose
	 * basename ends with a flagged image extension. Dashicon classes, SVG data:
	 * URIs, the 'none' value, and empty strings are all valid and skipped.
	 *
	 * @since 2.1.0
	 *
	 * @param string $icon The icon parameter value.
	 * @return bool True if the icon is a raster image file, false otherwise.
	 */
	private function is_raster_image_icon( $icon ) {
		if ( '' === $icon || 'none' === $icon ) {
			return false;
		}

		if ( 0 === strpos( $icon, 'dashicons-' ) ) {
			return false;
		}

		// SVG data: URIs adapt to the admin color scheme and are valid.
		// Only SVG data: URIs adapt to the admin color scheme.
		if ( 0 === strpos( $icon, 'data:' ) ) {
			return 1 === preg_match( '/^data:image\/(?:png|jpe?g|gif|webp|bmp|x-icon|vnd\.microsoft\.icon)(?:[;,]|$)/i', $icon );
		}

		// Strip any query string or fragment before checking the extension.
		$path = preg_split( '/[?#]/', $icon, 2 );
		$path = $path[0];

		foreach ( $this->image_extensions as $extension ) {
			if ( preg_match( '/\.' . preg_quote( $extension, '/' ) . '$/i', $path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the contents of the given file.
	 *
	 * This is a caching wrapper around the native file_get_contents() function.
	 *
	 * @since 2.1.0
	 *
	 * @param string $file The file name.
	 * @return string The file contents.
	 */
	private static function file_contents( $file ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return file_get_contents( $file );
	}

	/**
	 * Converts a byte offset into a line number.
	 *
	 * @since 2.1.0
	 *
	 * @param string $contents The file contents.
	 * @param int    $offset   The byte offset of the match.
	 * @return int The line number (1-based).
	 */
	private static function offset_to_line( $contents, $offset ) {
		return substr_count( $contents, "\n", 0, $offset ) + 1;
	}

	/**
	 * Converts a byte offset into a column number.
	 *
	 * @since 2.1.0
	 *
	 * @param string $contents The file contents.
	 * @param int    $offset   The byte offset of the match.
	 * @return int The column number (1-based).
	 */
	private static function offset_to_column( $contents, $offset ) {
		$last_newline = strrpos( substr( $contents, 0, $offset ), "\n" );

		if ( false === $last_newline ) {
			return $offset + 1;
		}

		return $offset - $last_newline;
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 2.1.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects the use of raster image files as admin menu icons, which do not adapt to the WordPress admin color schemes. Use a dashicon or an SVG data: URI instead.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 2.1.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/resource/dashicons/', 'plugin-check' );
	}
}
