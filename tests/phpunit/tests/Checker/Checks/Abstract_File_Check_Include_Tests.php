<?php
/**
 * Tests for Abstract_File_Check include/exclude file filtering logic.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Test_Data\Include_Test_File_Check;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

class Abstract_File_Check_Include_Tests extends WP_UnitTestCase {

	protected $plugin_root;

	public function set_up() {
		parent::set_up();

		$this->plugin_root = sys_get_temp_dir() . '/pcp_test_include_' . uniqid();
		mkdir( $this->plugin_root, 0777, true );

		// Create file structure.
		$files = array(
			'plugin.php'               => '<?php // Plugin Name: Test',
			'includes/main.php'        => '<?php',
			'includes/admin/admin.php' => '<?php',
			'includes/views/view.php'  => '<?php',
			'vendor/autoload.php'      => '<?php',
			'README.md'                => '# Readme',
		);

		foreach ( $files as $path => $content ) {
			$full_path = $this->plugin_root . '/' . $path;
			$dir       = dirname( $full_path );
			if ( ! is_dir( $dir ) ) {
				mkdir( $dir, 0777, true );
			}
			file_put_contents( $full_path, $content );
		}

		$this->clear_cache();
		$this->clear_filters();
	}

	public function tear_down() {
		$this->recursive_rmdir( $this->plugin_root );
		$this->clear_cache();
		$this->clear_filters();
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

	protected function clear_cache() {
		$ref  = new ReflectionClass( Abstract_File_Check::class );
		$prop = $ref->getProperty( 'file_list_cache' );
		$prop->setAccessible( true );
		$prop->setValue( array() );
	}

	protected function clear_filters() {
		remove_all_filters( 'wp_plugin_check_include_files' );
		remove_all_filters( 'wp_plugin_check_include_directories' );
		remove_all_filters( 'wp_plugin_check_ignore_files' );
		remove_all_filters( 'wp_plugin_check_ignore_directories' );
		remove_all_filters( 'wp_plugin_check_ignore_patterns' );
	}

	protected function get_basenames( array $files ) {
		return array_map( 'basename', $files );
	}

	protected function run_check() {
		$context = new Check_Context( $this->plugin_root . '/plugin.php' );
		$result  = new Check_Result( $context );
		$check   = new Include_Test_File_Check();
		$check->run( $result );

		return $check->files_checked;
	}

	public function test_includes_only_specified_files() {
		add_filter(
			'wp_plugin_check_include_files',
			function () {
				return array( 'includes/main.php' );
			}
		);

		$files     = $this->run_check();
		$basenames = $this->get_basenames( $files );

		$this->assertCount( 1, $files, 'Should include exactly one file.' );
		$this->assertContains( 'main.php', $basenames );
	}

	public function test_includes_specified_directories_recursively() {
		add_filter(
			'wp_plugin_check_include_directories',
			function () {
				return array( 'includes/admin' );
			}
		);

		$files     = $this->run_check();
		$basenames = $this->get_basenames( $files );

		$this->assertNotEmpty( $files );
		$this->assertContains( 'admin.php', $basenames );
		$this->assertNotContains( 'view.php', $basenames );
	}

	public function test_combines_include_files_and_directories() {
		add_filter(
			'wp_plugin_check_include_files',
			function () {
				return array( 'includes/main.php' );
			}
		);
		add_filter(
			'wp_plugin_check_include_directories',
			function () {
				return array( 'includes/views' );
			}
		);

		$files     = $this->run_check();
		$basenames = $this->get_basenames( $files );

		$this->assertContains( 'main.php', $basenames );
		$this->assertContains( 'view.php', $basenames );
		$this->assertNotContains( 'admin.php', $basenames );
	}

	public function test_respects_exclusions_within_included_directories() {
		add_filter(
			'wp_plugin_check_include_directories',
			function () {
				return array( 'includes' );
			}
		);
		add_filter(
			'wp_plugin_check_ignore_directories',
			function ( $dirs ) {
				$dirs[] = 'includes/admin';
				return $dirs;
			}
		);

		$files     = $this->run_check();
		$basenames = $this->get_basenames( $files );

		$this->assertContains( 'main.php', $basenames );
		$this->assertContains( 'view.php', $basenames );
		$this->assertNotContains( 'admin.php', $basenames );
	}

	public function test_respects_distignore_patterns() {
		file_put_contents( $this->plugin_root . '/.distignore', "vendor\n*.md" );

		Plugin_Request_Utility::load_distignore_filters( $this->plugin_root );

		$files     = $this->run_check();
		$basenames = $this->get_basenames( $files );

		$this->assertContains( 'main.php', $basenames, 'main.php should be checked.' );
		$this->assertNotContains( 'autoload.php', $basenames, 'vendor/autoload.php should be ignored.' );
		$this->assertNotContains( 'README.md', $basenames, '*.md files should be ignored.' );
	}

	public function test_default_exclusions_are_respected() {
		$files     = $this->run_check();
		$basenames = $this->get_basenames( $files );

		$this->assertContains( 'main.php', $basenames );
		// vendor is in default exclude list.
		$this->assertNotContains( 'autoload.php', $basenames, 'Default vendor directory should be excluded.' );
	}
}
