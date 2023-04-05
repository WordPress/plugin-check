<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class License extends Check_Base {
	public function check_license_present() {
		if ( empty( $this->readme->license ) ) {
			return new Error(
				'no_license',
				__( 'Error: No license defined.', 'wporg-plugins' ) . ' ' . sprintf(
					/* translators: 1: readme.txt */
					__( 'Your plugin has no license declared. Please update your %1$s with a GPLv2 (or later) compatible license.', 'wporg-plugins' ),
					'<code>readme.txt</code>'
				)
			);
		}
	}

	public function check_license_meets_requirements() {
		$license = $this->readme->license ?? '';
		if ( ! $license ) {
			return;
		}

		// Check for a valid SPDX license identifier.
		if ( ! preg_match( '/^([a-z0-9\-\+\.]+)(\sor\s([a-z0-9\-\+\.]+))*$/i', $license ) ) {
			return new Warning(
				'invalid_license',
				__( 'Error: Invalid license.', 'wporg-plugins' ) . ' ' . sprintf(
					/* translators: 1: readme.txt */
					__( 'Your plugin has an invalid license declared. Please update your %1$s with a valid SPDX license identifier.', 'wporg-plugins' ),
					'<code>readme.txt</code>'
				)
			);
		}
	}
}
