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
	 * @dataProvider data_version_test_cases
	 */
	public function test_wordpress_latest_version( $full_version, $expected_major ) {
		$this->set_test_version_data( $full_version );
		$this->assertSame( $full_version, $this->get_wordpress_latest_version() );
	}

	/**
	 * @dataProvider data_version_test_cases
	 */
	public function test_wordpress_stable_version( $full_version, $expected_major ) {
		$this->set_test_version_data( $full_version );
		$this->assertSame( $expected_major, $this->get_wordpress_stable_version() );
	}

	/**
	 * @dataProvider data_wordpress_version_items
	 */
	public function test_wordpress_relative_major_version( $version, $steps, $new_version ) {
		$result = $this->get_wordpress_relative_major_version( $version, $steps );
		$this->assertSame( $new_version, $result );
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

	public function data_version_test_cases() {
		return array(
			'single-digit-version' => array( '6.7.1', '6.7' ),
			'double-digit-version' => array( '11.8.3', '11.8' ),
		);
	}

	public function data_wordpress_version_items() {
		return array(
			array( '6.7', 1, '6.8' ),
			array( '6.7', -1, '6.6' ),
			array( '6.7', 2, '6.9' ),
			array( '6.7', -2, '6.5' ),
			array( '5.9', 1, '6.0' ),
			array( '6.0', -1, '5.9' ),
			array( '5.9', 2, '6.1' ),
			array( '6.0', -2, '5.8' ),
			array( '5.8', 2, '6.0' ),
			array( '6.1', -2, '5.9' ),
			array( '11.2', 1, '11.3' ),
			array( '11.2', -1, '11.1' ),
			array( '10.9', 1, '11.0' ),
			array( '11.0', -1, '10.9' ),
			array( '0.9', 1, '1.0' ),
			array( '1.0', -1, '0.9' ),
			array( '99.9', 1, '100.0' ),
			array( '100.0', -1, '99.9' ),
		);
	}

	public function tear_down() {
		delete_transient( $this->info_transient_key );
		parent::tear_down();
	}
}
