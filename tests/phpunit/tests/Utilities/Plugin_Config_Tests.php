<?php
/**
 * Tests for the Plugin_Config class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Utilities\Plugin_Config;

class Plugin_Config_Tests extends WP_UnitTestCase {

	public function test_get_third_party_paths_returns_configured_paths() {
		$paths = Plugin_Config::get_third_party_paths( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-check-info' );

		$this->assertSame( array( 'vendor/phpseclib', 'libraries/legacy' ), $paths );
	}

	public function test_get_third_party_paths_ignores_missing_config() {
		$this->assertSame( array(), Plugin_Config::get_third_party_paths( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-wp-functions-compatibility-with-errors' ) );
	}

	public function test_get_third_party_paths_ignores_invalid_config() {
		$this->assertSame( array(), Plugin_Config::get_third_party_paths( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-plugin-check-info-invalid' ) );
	}

	/**
	 * @dataProvider third_party_file_provider
	 */
	public function test_is_third_party_file( $file, $expected ) {
		$this->assertSame( $expected, Plugin_Config::is_third_party_file( $file, array( 'vendor/phpseclib' ) ) );
	}

	public function third_party_file_provider() {
		return array(
			'in declared directory'   => array( 'vendor/phpseclib/Crypt/Hash.php', true ),
			'declared file'           => array( 'vendor/phpseclib', true ),
			'near matching directory' => array( 'vendor/phpseclib2/Crypt/Hash.php', false ),
			'outside directory'       => array( 'includes/Plugin.php', false ),
			'normalizes backslashes'  => array( 'vendor\\phpseclib\\Crypt\\Hash.php', true ),
			'rejects traversal'       => array( 'vendor/phpseclib/../other.php', false ),
		);
	}
}
