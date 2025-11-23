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
		list( $version, ) = explode( '-', (string) $version );

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

		$new_version = floatval( $version ) + ( 0.1 * $steps );

		return (string) number_format( $new_version, 1 );
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
			$info = array();

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
