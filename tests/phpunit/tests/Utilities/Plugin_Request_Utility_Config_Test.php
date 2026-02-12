<?php
/**
 * Test for Plugin_Request_Utility config methods.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

use PHPUnit\Framework\TestCase;

/**
 * Test for Plugin_Request_Utility config methods.
 */
class Plugin_Request_Utility_Config_Test extends TestCase {

	private $plugin_dir;

	public function setUp(): void {
		parent::setUp();
		$this->plugin_dir = sys_get_temp_dir() . '/pcp_test_config_' . uniqid();
		mkdir( $this->plugin_dir );
	}

	public function tearDown(): void {
		$this->recursive_rmdir( $this->plugin_dir );
		parent::tearDown();
	}

	private function recursive_rmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			( is_dir( "$dir/$file" ) ) ? $this->recursive_rmdir( "$dir/$file" ) : unlink( "$dir/$file" );
		}
		rmdir( $dir );
	}

	public function test_get_plugin_configuration_returns_empty_if_no_file() {
		$config = Plugin_Request_Utility::get_plugin_configuration( $this->plugin_dir );
		$this->assertIsArray( $config );
		$this->assertEmpty( $config );
	}

	public function test_get_plugin_configuration_loads_valid_json() {
		$data = array(
			'exclude-files' => array( 'foo.php' ),
			'categories'    => array( 'security' ),
		);
		file_put_contents( $this->plugin_dir . '/.plugin-check.json', json_encode( $data ) );

		$config = Plugin_Request_Utility::get_plugin_configuration( $this->plugin_dir );
		$this->assertEquals( $data, $config );
	}

	public function test_get_plugin_configuration_ignores_invalid_json() {
		file_put_contents( $this->plugin_dir . '/.plugin-check.json', '{invalid json' );

		$config = Plugin_Request_Utility::get_plugin_configuration( $this->plugin_dir );
		$this->assertIsArray( $config );
		$this->assertEmpty( $config );
	}

	public function test_get_plugin_configuration_normalizes_keys() {
		// Future proofing: ensure we handle keys consistently if needed?
		// For now just raw data.
		$data = array( 'foo' => 'bar' );
		file_put_contents( $this->plugin_dir . '/.plugin-check.json', json_encode( $data ) );

		$config = Plugin_Request_Utility::get_plugin_configuration( $this->plugin_dir );
		$this->assertEquals( 'bar', $config['foo'] );
	}
}
