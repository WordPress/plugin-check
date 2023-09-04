<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Readme extends Check_Base {
	/**
	 * Checks for the presence of a readme.txt file and throw an error if it's missing.
	 *
	 * @since 1.0.0
	 *
	 * @return void|Error
	 */
	public function check_readme_exists() {
		if ( $this->readme ) {
			return;
		}

		return new Error(
			'readme_missing',
			__( 'No readme.txt or readme.md was not found. readme.txt/readme.md is a required file.', 'wporg-plugins' )
		);
	}

	public function check_license_present() {
		if ( $this->readme && empty( $this->readme->license ) ) {
			return new Error(
				'no_license',
				__( 'No license defined.', 'wporg-plugins' ) . ' ' . sprintf(
					/* translators: 1: readme.txt */
					__( 'Your plugin has no license declared. Please update your %1$s with a GPLv2 (or later) compatible license.', 'wporg-plugins' ),
					'<code>readme.txt</code>'
				)
			);
		}
	}

	public function check_license_meets_requirements() {
		$license = $this->readme->license ?? '';

		// Cleanup the license identifier a bit.
		$license = str_ireplace( [ 'License URI:', 'License:' ], '', $license );
		$license = trim( $license, ' .' );

		if ( ! $license ) {
			return;
		}

		// Check for a valid SPDX license identifier.
		if ( ! preg_match( '/^([a-z0-9\-\+\.]+)(\sor\s([a-z0-9\-\+\.]+))*$/i', $license ) ) {
			return new Warning(
				'invalid_license',
				__( 'Invalid license specified.', 'wporg-plugins' ) . ' ' . sprintf(
					/* translators: 1: readme.txt */
					__( 'Your plugin has an invalid license declared. Please update your %1$s with a valid SPDX license identifier.', 'wporg-plugins' ),
					'<code>readme.txt</code>'
				)
			);
		}
	}

	function check_for_default_text() {
		if ( ! $this->readme ) {
			return;
		}

		if (
			str_contains( $this->readme->short_description, 'Here is a short description of the plugin.' ) ||
			in_array( 'tag1', $this->readme->tags ) ||
			str_contains( $this->readme->donate_link, 'example' )
		) {
			return new Notice(
				'default_readme_text',
				'The readme.txt appears to contain default text.'
			);
		}
	}

	function check_for_warnings() {
		if ( ! empty( $this->readme->warnings ) ) {
			return new Warning(
				'readme_parser_warnings',
				sprintf(
					'The following readme parser warnings were detected: %s',
					esc_html( implode( ', ',  array_keys( $this->readme->warnings ) ) )
				)
			);
		}
	}


	public function check_stable_tag() {
		if ( ! $this->readme ) {
			return;
		}

		$stable_tag = $this->readme->stable_tag ?? '';

		if ( 'trunk' === $stable_tag ) {
			return new Error(
				'trunk_stable_tag',
				"It's recommended not to use 'Stable Tag: trunk'."
			);
		}

		if (
			$stable_tag && ! empty( $this->headers['Version'] ) &&
			$stable_tag != $this->headers['Version']
		) {
			return new Error(
				'stable_tag_mismatch',
				__( 'Stable tag does not match the plugin version.', 'wporg-plugins' ) . ' ' . sprintf(
					/* translators: 1: readme.txt */
					__( 'The Stable Tag in your %1$s file does not match the version in your main plugin file.', 'wporg-plugins' ),
					'<code>readme.txt</code>'
				)
			);
		}
	}
}
