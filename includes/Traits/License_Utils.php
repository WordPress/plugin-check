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
 * @since 1.3.0
 */
trait License_Utils {

	/**
	 * Returns normalized license.
	 *
	 * @since 1.3.0
	 *
	 * @param string $license The license to normalize.
	 * @return string Normalized license.
	 */
	protected function get_normalized_license( $license ) {
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
		$license = preg_replace( '/Apache.*?([0-9])(\.[0])?/i', 'Apache$1', $license );
		$license = str_replace( '.', '', $license );

		return $license;
	}

	/**
	 * Checks if the license is valid identifier.
	 *
	 * @since 1.3.0
	 *
	 * @param string $license License text.
	 * @return bool true if the license is valid identifier, otherwise false.
	 */
	protected function is_license_valid_identifier( $license ) {
		$match = preg_match( '/^([a-z0-9\-\+\.]+)(\sor\s([a-z0-9\-\+\.]+))*$/i', $license );

		return ( false === $match || 0 === $match ) ? false : true;
	}

	/**
	 * Checks if the license is GPL compatible.
	 *
	 * @since 1.3.0
	 *
	 * @param string $license License text.
	 * @return bool true if the license is GPL compatible, otherwise false.
	 */
	protected function is_license_gpl_compatible( $license ) {
		$match = preg_match( '/GPL|GNU|MIT|FreeBSD|New BSD|BSD-3-Clause|BSD 3 Clause|OpenLDAP|Expat|Apache2|MPL20|ISC/im', $license );

		return ( false === $match || 0 === $match ) ? false : true;
	}
}
