<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Version_Utils
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for version utilities.
 *
 * @since 1.3.1
 */
trait Version_Utils {

	/**
	 * Returns current major WordPress version.
	 *
	 * @since 1.3.1
	 *
	 * @return string Stable WordPress version.
	 */
	protected function get_wordpress_stable_version(): string {
		$version = $this->get_latest_version_info( 'current' );

		// Strip off any -alpha, -RC, -beta suffixes.
		list( $version, ) = explode( '-', $version );

		if ( preg_match( '#^\d+\.\d#', $version, $matches ) ) {
			$version = $matches[0];
		}

		return $version;
	}

	/**
	 * Returns WordPress latest version.
	 *
	 * @since 1.3.1
	 *
	 * @return string WordPress latest version.
	 */
	protected function get_wordpress_latest_version(): string {
		$version = $this->get_latest_version_info( 'current' );

		return $version ?? get_bloginfo( 'version' );
	}

	/**
	 * Returns relative WordPress major version.
	 *
	 * @since 1.3.1
	 *
	 * @param string $version WordPress major version.
	 * @param int    $steps   Steps to find relative version. Defaults to 1 for next major version.
	 * @return string Relative WordPress major version.
	 */
	protected function get_wordpress_relative_major_version( string $version, int $steps = 1 ): string {

		if ( 0 === $steps ) {
			return $version;
		}

		$parts        = explode( '.', $version );
		$major        = (int) ( $parts[0] ?? 0 );
		$minor        = (int) ( $parts[1] ?? 0 );
		$minor_digits = isset( $parts[1] ) ? strlen( $parts[1] ) : 1;

		// Convert to total "version units" (each major version = 10 minor units)
		$total_units = $major * 10 + $minor;
		$new_total   = $total_units + $steps;

		// Calculate new major and minor versions
		$new_major = (int) ( $new_total / 10 );
		$new_minor = $new_total % 10;

		// Special case: When crossing from x.9 to (x+1).0
		if ( $steps > 0 && $minor + $steps >= 10 ) {
			$new_major = $major + floor( ( $minor + $steps ) / 10 );
			$new_minor = ( $minor + $steps ) % 10;
		}
		// Special case: When crossing downward from x.0 to (x-1).9
		elseif ( $steps < 0 && $minor + $steps < 0 ) {
			$new_major = $major + ceil( ( $minor + $steps ) / 10 ) - 1;
			$new_minor = 10 + ( ( $minor + $steps ) % 10 );
		}

		// Format minor version to maintain at least 1 digit
		$formatted_minor = (string) $new_minor;

		return $new_major . '.' . $formatted_minor;
	}

	/**
	 * Returns specific information.
	 *
	 * @since 1.3.1
	 *
	 * @param string $key The information key to retrieve.
	 * @return mixed The requested information.
	 */
	private function get_latest_version_info( string $key ) {
		$info = get_transient( 'wp_plugin_check_latest_version_info' );

		if ( false === $info ) {
			$response = wp_remote_get( 'https://api.wordpress.org/core/version-check/1.7/' );

			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $body['offers'] ) && ! empty( $body['offers'] ) ) {
					$info = reset( $body['offers'] );
					set_transient( 'wp_plugin_check_latest_version_info', $info, DAY_IN_SECONDS );
				}
			}
		}

		return array_key_exists( $key, $info ) ? $info[ $key ] : null;
	}
}
