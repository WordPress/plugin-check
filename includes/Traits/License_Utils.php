<?php
/**
 * Trait WordPress\Plugin_Check\Traits\License_Utils
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for license utilities.
 *
 * @since 1.0.0
 */
trait License_Utils {

	/**
	 * Normalize licenses to compare them.
	 *
	 * @since 1.0.2
	 *
	 * @param string $license The license to normalize.
	 * @return string
	 */
	protected function normalize_licenses( $license ) {
		$license = trim( $license );
		$license = str_replace( '  ', ' ', $license );

		// Remove some strings at the end.
		$strings_to_remove = array(
			'.',
			'http://www.gnu.org/licenses/old-licenses/gpl-2.0.html',
			'https://www.gnu.org/licenses/old-licenses/gpl-2.0.html',
			'https://www.gnu.org/licenses/gpl-3.0.html',
			' or later',
			'-or-later',
			'+',
		);
		foreach ( $strings_to_remove as $string_to_remove ) {
			$position = strrpos( $license, $string_to_remove );

			if ( false !== $position ) {
				// To remove from the end, the string to remove must be at the end.
				if ( $position + strlen( $string_to_remove ) === strlen( $license ) ) {
					$license = trim( substr( $license, 0, $position ) );
				}
			}
		}

		// Versions.
		$license = str_replace( '-', '', $license );
		$license = str_replace( 'GNU General Public License (GPL)', 'GPL', $license );
		$license = str_replace( 'GNU General Public License', 'GPL', $license );
		$license = str_replace( ' version ', 'v', $license );
		$license = preg_replace( '/GPL\s*[-|\.]*\s*[v]?([0-9])(\.[0])?/i', 'GPL$1', $license, 1 );
		$license = str_replace( '.', '', $license );

		return $license;
	}
}
