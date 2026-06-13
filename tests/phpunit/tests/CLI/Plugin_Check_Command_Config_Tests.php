<?php
/**
 * Tests for the Plugin_Check_Command::maybe_load_plugin_config gate.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\CLI\Plugin_Check_Command;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

class Plugin_Check_Command_Config_Tests extends WP_UnitTestCase {

	protected $plugin_dir;

	public function set_up() {
		parent::set_up();

		$this->plugin_dir = sys_get_temp_dir() . '/pcp_test_cmd_' . uniqid();
		mkdir( $this->plugin_dir, 0777, true );

		$this->clear_filters();
	}

	public function tear_down() {
		$this->recursive_rmdir( $this->plugin_dir );
		$this->clear_filters();
		Plugin_Request_Utility::destroy_runner();
		parent::tear_down();
	}

	protected function recursive_rmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $files as $fileinfo ) {
			$todo = ( $fileinfo->isDir() ? 'rmdir' : 'unlink' );
			$todo( $fileinfo->getRealPath() );
		}
		rmdir( $dir );
	}

	protected function clear_filters() {
		remove_all_filters( 'wp_plugin_check_ignore_files' );
		remove_all_filters( 'wp_plugin_check_ignore_directories' );
		remove_all_filters( 'wp_plugin_check_ignore_patterns' );
		remove_all_filters( 'wp_plugin_check_include_files' );
		remove_all_filters( 'wp_plugin_check_include_directories' );
	}

	public function test_returns_empty_when_flag_false() {
		file_put_contents(
			$this->plugin_dir . '/.plugin-check.json',
			wp_json_encode( array( 'exclude-files' => array( 'secret.php' ) ) )
		);
		file_put_contents( $this->plugin_dir . '/.distignore', "*.md\n" );

		$result = Plugin_Check_Command::maybe_load_plugin_config( $this->plugin_dir, false );

		$this->assertSame( array(), $result );
		$this->assertFalse( has_filter( 'wp_plugin_check_ignore_patterns' ) );
		$this->assertFalse( has_filter( 'wp_plugin_check_ignore_files' ) );
	}

	public function test_returns_empty_when_plugin_path_empty() {
		$result = Plugin_Check_Command::maybe_load_plugin_config( '', true );
		$this->assertSame( array(), $result );
	}

	public function test_returns_empty_when_plugin_path_missing() {
		$result = Plugin_Check_Command::maybe_load_plugin_config( '/nonexistent/path/xyz', true );
		$this->assertSame( array(), $result );
	}

	public function test_loads_distignore_when_flag_set() {
		file_put_contents( $this->plugin_dir . '/.distignore', "*.md\nvendor\n" );

		$result = Plugin_Check_Command::maybe_load_plugin_config( $this->plugin_dir, true );

		$this->assertSame( array(), $result, 'Only .distignore present yields empty config array.' );
		$this->assertNotFalse( has_filter( 'wp_plugin_check_ignore_patterns' ) );

		$patterns = Plugin_Request_Utility::get_files_to_ignore_patterns();
		$this->assertNotEmpty( $patterns );
	}

	public function test_loads_plugin_check_json_when_flag_set() {
		$config = array(
			'exclude-directories' => array( 'build' ),
			'exclude-files'       => array( 'changelog.txt' ),
		);
		file_put_contents(
			$this->plugin_dir . '/.plugin-check.json',
			wp_json_encode( $config )
		);

		$result = Plugin_Check_Command::maybe_load_plugin_config( $this->plugin_dir, true );

		$this->assertSame( $config, $result, 'Config array should be returned for defaults merge.' );
		$this->assertNotFalse( has_filter( 'wp_plugin_check_ignore_directories' ) );
		$this->assertNotFalse( has_filter( 'wp_plugin_check_ignore_files' ) );
	}

	public function test_no_config_files_no_error() {
		$result = Plugin_Check_Command::maybe_load_plugin_config( $this->plugin_dir, true );

		$this->assertSame( array(), $result );
		$this->assertFalse( has_filter( 'wp_plugin_check_ignore_patterns' ) );
		$this->assertFalse( has_filter( 'wp_plugin_check_ignore_directories' ) );
		$this->assertFalse( has_filter( 'wp_plugin_check_ignore_files' ) );
		$this->assertFalse( has_filter( 'wp_plugin_check_include_directories' ) );
		$this->assertFalse( has_filter( 'wp_plugin_check_include_files' ) );
	}
}
