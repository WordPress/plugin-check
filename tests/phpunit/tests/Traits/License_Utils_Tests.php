<?php
/**
 * Tests for the License_Utils trait.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Traits\License_Utils;

class License_Utils_Tests extends WP_UnitTestCase {

	use License_Utils;

	/**
	 * @dataProvider data_licenses_for_normalization
	 */
	public function test_license_normalization( $raw, $normalized ) {
		$result = $this->get_normalized_license( $raw );

		$this->assertSame( $normalized, $result );
	}

	/**
	 * @dataProvider data_license_identifiers
	 */
	public function test_license_identifier( $license, $validity ) {
		$result = $this->is_license_valid_identifier( $license );

		$this->assertSame( $validity, $result );
	}

	/**
	 * @dataProvider data_license_gpl_compatibility
	 */
	public function test_license_gpl_compatibility( $license, $validity ) {
		$result = $this->is_license_gpl_compatible( $license );

		$this->assertSame( $validity, $result );
	}

	public function data_licenses_for_normalization() {
		return array(
			array( 'GPLv2', 'GPL2' ),
			array( 'GPLv2 or later', 'GPL2' ),
			array( 'GPLv2  or  later', 'GPL2' ),
			array( 'GPLv2-or-later', 'GPL2' ),
			array( 'GPL v2 or later', 'GPL2' ),
			array( 'GPL-2.0', 'GPL2' ),
			array( 'GPL-2.0-or-later', 'GPL2' ),
			array( 'GNU General Public License v2.0 or later', 'GPL2' ),
			array( 'GPL-2.0+', 'GPL2' ),
			array( 'GNU General Public License (GPL) 2.0', 'GPL2' ),
			array( 'GPL version 2', 'GPL2' ),
			array( 'GPL version 2 or later', 'GPL2' ),

			array( 'GPLv3', 'GPL3' ),
			array( 'GPLv3 or later', 'GPL3' ),
			array( 'GPLv3  or  later', 'GPL3' ),
			array( 'GPLv3-or-later', 'GPL3' ),
			array( 'GPL v3 or later', 'GPL3' ),
			array( 'GPL-3.0', 'GPL3' ),
			array( 'GPL-3.0-or-later', 'GPL3' ),
			array( 'GNU General Public License v3.0 or later', 'GPL3' ),
			array( 'GPL-3.0+', 'GPL3' ),
			array( 'GNU General Public License (GPL) 3.0', 'GPL3' ),
			array( 'GPL version 3', 'GPL3' ),
			array( 'GPL version 3 or later', 'GPL3' ),

			array( 'Apache License, Version 2.0', 'Apache2' ),
			array( 'Apache License 2.0', 'Apache2' ),
			array( 'Apache License 2', 'Apache2' ),
			array( 'Apache 2.0', 'Apache2' ),
			array( 'Apache-2.0', 'Apache2' ),
			array( 'Apache 2', 'Apache2' ),

			array( 'MPL-1.0', 'MPL10' ),
			array( 'MPL-2.0', 'MPL20' ),
		);
	}

	public function data_license_identifiers() {
		return array(
			array( 'GPLv2', true ),
			array( 'GPL3', true ),
			array( 'mpl-2.0', true ),
			array( 'lgpl-3.0-or-later', true ),
			array( 'artistic-license-2.0 or MIT', true ),
			array( 'cc-by-sa-4.0 or cc-by-nc-4.0', true ),
			array( 'public-domain or unlicense', true ),
			array( 'eupl-1.1+', true ),
			array( 'AGPL-3.0-only', true ),

			array( 'MIT License', false ),
			array( 'GPL (v3)', false ),
			array( 'my_custom_license', false ),
			array( 'LGPL 3.0 or later', false ),
			array( 'GPL-2.0+ with font exception', false ),
		);
	}

	public function data_license_gpl_compatibility() {
		return array(
			array( 'GPL2', true ),
			array( 'GPL3', true ),
			array( 'MPL20', true ),
			array( 'MIT', true ),
			array( 'Apache2', true ),
			array( 'FreeBSD', true ),
			array( 'New BSD', true ),
			array( 'BSD-3-Clause', true ),
			array( 'BSD 3 Clause', true ),
			array( 'OpenLDAP', true ),
			array( 'Expat', true ),
			array( 'ISC', true ),

			array( 'EPL', false ),
			array( 'EUPL', false ),
			array( 'MPL10', false ),
			array( 'YPL', false ),
			array( 'ZPL', false ),
		);
	}
}
