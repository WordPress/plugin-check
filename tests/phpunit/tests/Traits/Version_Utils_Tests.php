<?php
/**
 * Tests for the Version_Utils trait.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Traits\Version_Utils;

class Version_Utils_Tests extends WP_UnitTestCase {
	use Version_Utils;

	protected $info_transient_key = 'wp_plugin_check_latest_version_info';

	/**
	 * Data provider for version info tests.
	 */
	public function version_info_provider() {
		return array(
			'single-digit-version' => array( '6.7.1', '6.7' ),
			'double-digit-version' => array( '11.8.3', '11.8' ),
		);
	}

	/**
	 * @dataProvider version_info_provider
	 */
	public function test_wordpress_latest_version( $full_version, $expected_major ) {
		$this->set_test_version_data( $full_version );
		$this->assertSame( $full_version, $this->get_wordpress_latest_version() );
	}

	/**
	 * @dataProvider version_info_provider
	 */
	public function test_wordpress_stable_version( $full_version, $expected_major ) {
		$this->set_test_version_data( $full_version );
		$this->assertSame( $expected_major, $this->get_wordpress_stable_version() );
	}

	/**
	 * @dataProvider data_wordpress_version_items
	 */
	public function test_wordpress_relative_major_version( $version, $steps, $new_version ) {
		$this->assertSame( $new_version, $this->get_wordpress_relative_major_version( $version, $steps ) );
	}

	protected function set_test_version_data( $version ) {
		$major_version = substr( $version, 0, strrpos( $version, '.' ) );

		set_transient(
			$this->info_transient_key,
			array(
				'version'       => $version,
				'new_bundled'   => $major_version,
				'current'       => $version,
				'response'      => 'upgrade',
				'download'      => "https://downloads.wordpress.org/release/wordpress-{$version}.zip",
				'php_version'   => '7.2.24',
				'mysql_version' => '5.5.5',
				'packages'      => array(
					'full'        => "https://downloads.wordpress.org/release/wordpress-{$version}.zip",
					'no_content'  => "https://downloads.wordpress.org/release/wordpress-{$version}-no-content.zip",
					'new_bundled' => "https://downloads.wordpress.org/release/wordpress-{$version}-new-bundled.zip",
					'partial'     => false,
					'rollback'    => false,
				),
			)
		);
	}

	public function data_wordpress_version_items() {
		return array(
			// Single-digit versions
			array( '6.7', 1, '6.8' ),
			array( '6.7', -1, '6.6' ),
			array( '6.7', 2, '6.9' ),
			array( '6.7', -2, '6.5' ),

			// Version boundary crossings
			array( '5.9', 1, '6.0' ),  // Not 5.10
			array( '6.0', -1, '5.9' ),  // Not 6.-1
			array( '5.9', 2, '6.1' ),
			array( '6.0', -2, '5.8' ),
			array( '5.8', 2, '6.0' ),
			array( '6.1', -2, '5.9' ),

			// Double-digit major versions
			array( '11.2', 1, '11.3' ),
			array( '11.2', -1, '11.1' ),
			array( '10.9', 1, '11.0' ),  // Not 10.10
			array( '11.0', -1, '10.9' ), // Not 11.-1

		// Edge cases
			array( '0.9', 1, '1.0' ),    // First version boundary
			array( '1.0', -1, '0.9' ),
			array( '99.9', 1, '100.0' ), // Triple-digit boundary
			array( '100.0', -1, '99.9' ),

			// Larger steps
			array( '6.5', 5, '7.0' ),
			array( '7.0', -5, '6.5' ),
			array( '10.5', 5, '11.0' ),
			array( '11.0', -5, '10.5' ),
		);
	}

	public function tear_down() {
		delete_transient( $this->info_transient_key );
		parent::tear_down();
	}
}
