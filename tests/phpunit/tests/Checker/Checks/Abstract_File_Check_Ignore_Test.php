<?php
/**
 * Tests for the Abstract_File_Check class include logic in isolation.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Tests\Checker\Checks;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

// Ensure bootstrap is loaded if running this file directly without xml config
require_once __DIR__ . '/../../../includes/isolated-bootstrap.php';

/**
 * Concrete implementation of Abstract_File_Check for testing.
 */
class Isolated_Test_File_Check extends Abstract_File_Check {
	public $files_checked = array();

	// Make public for testing if needed, or rely on run()
	public function get_files_public( $context ) {
		return parent::get_files( $context );
	}

	protected function check_files( Check_Result $result, array $files ) {
		$this->files_checked = $files;
	}

	public function get_stability() {
		return self::STABILITY_STABLE;
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_description(): string {
		return 'Test check description';
	}

	public function get_documentation_url(): string {
		return 'http://example.com/doc';
	}
}

/**
 * Tests for Abstract_File_Check include options.
 *
 * @group checks
 * @group include-options
 */
class Abstract_File_Check_Ignore_Test extends TestCase {

	protected $plugin_root;
	protected $plugin_slug = 'test-plugin';

	public function setUp(): void {
		parent::setUp();
		
		// Setup temp directory structure inside the mocked WP_PLUGIN_DIR
		$this->plugin_root = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
		
		if ( ! file_exists( WP_PLUGIN_DIR ) ) {
			mkdir( WP_PLUGIN_DIR, 0777, true );
		}
		if ( file_exists( $this->plugin_root ) ) {
			$this->recursive_rmdir( $this->plugin_root );
		}
		mkdir( $this->plugin_root, 0777, true );

		// Create file structure
		$files = [
			'plugin.php' => '<?php // Plugin Name: Test',
			'src/Plugin.php' => '<?php',
			'src/Admin/Admin.php' => '<?php',
			'src/Frontend/Frontend.php' => '<?php',
			'vendor/autoload.php' => '<?php',
		];

		foreach ( $files as $path => $content ) {
			$full_path = $this->plugin_root . '/' . $path;
			$dir = dirname( $full_path );
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir, 0777, true );
			}
			file_put_contents( $full_path, $content );
		}

		$this->clear_cache();
		$this->clear_filters();
	}

	public function tearDown(): void {
		$this->recursive_rmdir( $this->plugin_root );
		$this->clear_cache();
		$this->clear_filters();
		parent::tearDown();
	}

	protected function recursive_rmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
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
		global $wp_filters;
		$wp_filters = [];
	}

	public function test_it_includes_only_specified_files() {
		add_filter( 'wp_plugin_check_include_files', function() {
			return array( 'src/Plugin.php' );
		} );

		$context = new Check_Context( $this->plugin_root . '/plugin.php' );
		$result  = new Check_Result( $context );
		$check   = new Isolated_Test_File_Check();
		$check->run( $result );

		$files = $check->files_checked;
		$basenames = array_map( 'basename', $files );

		$this->assertCount( 1, $files, 'Should include exactly one file.' );
		$this->assertContains( 'Plugin.php', $basenames );
	}

	public function test_it_includes_specified_directories_recursively() {
		add_filter( 'wp_plugin_check_include_directories', function() {
			return array( 'src/Admin' );
		} );

		$context = new Check_Context( $this->plugin_root . '/plugin.php' );
		$result  = new Check_Result( $context );
		$check   = new Isolated_Test_File_Check();
		$check->run( $result );

		$files = $check->files_checked;
		$basenames = array_map( 'basename', $files );
		
		$this->assertNotEmpty( $files );
		$this->assertContains( 'Admin.php', $basenames );
		$this->assertNotContains( 'Frontend.php', $basenames );
	}

	public function test_it_combines_include_files_and_directories() {
		add_filter( 'wp_plugin_check_include_files', function() {
			return array( 'src/Plugin.php' );
		} );
		add_filter( 'wp_plugin_check_include_directories', function() {
			return array( 'src/Frontend' );
		} );

		$context = new Check_Context( $this->plugin_root . '/plugin.php' );
		$result  = new Check_Result( $context );
		$check   = new Isolated_Test_File_Check();
		$check->run( $result );

		$files = $check->files_checked;
		$basenames = array_map( 'basename', $files );

		$this->assertContains( 'Plugin.php', $basenames );
		$this->assertContains( 'Frontend.php', $basenames );
		$this->assertNotContains( 'Admin.php', $basenames );
	}

	public function test_it_respects_exclusions_within_included_directories() {
		add_filter( 'wp_plugin_check_include_directories', function() {
			return array( 'src' );
		} );
		// Exclude Admin directory which is inside src
		add_filter( 'wp_plugin_check_ignore_directories', function( $dirs ) {
			$dirs[] = 'src/Admin'; 
			return $dirs;
		} );

		$context = new Check_Context( $this->plugin_root . '/plugin.php' );
		$result  = new Check_Result( $context );
		$check   = new Isolated_Test_File_Check();
		$check->run( $result );

		$files = $check->files_checked;
		$basenames = array_map( 'basename', $files );
		
		// src/Plugin.php, src/Frontend/Frontend.php included. 
		// src/Admin/Admin.php excluded.
		
		$this->assertContains( 'Frontend.php', $basenames );
		$this->assertNotContains( 'Admin.php', $basenames );
	}

	public function test_it_respects_distignore_patterns() {
		// Create structure
		mkdir( $this->plugin_root . '/src/node_modules', 0777, true ); // Deep node_modules
		
		file_put_contents( $this->plugin_root . '/src/main.php', '<?php' );
		file_put_contents( $this->plugin_root . '/src/README.md', '# Readme' );
		file_put_contents( $this->plugin_root . '/vendor/autoload.php', '<?php' );
		file_put_contents( $this->plugin_root . '/src/node_modules/pkg.json', '{}' );

		// Create .distignore
		file_put_contents( $this->plugin_root . '/.distignore', "vendor\n*.md" );

		// Load filters using our utility
		Plugin_Request_Utility::load_distignore_filters( $this->plugin_root );

		// Run check
		$context = new Check_Context( $this->plugin_root . '/plugin.php' );
		$result  = new Check_Result( $context );
		$check   = new Isolated_Test_File_Check();
		$check->run( $result );

		$files_checked = $check->files_checked;
		$basenames = array_map( 'basename', $files_checked );

		// Verify main.php is checked
		$this->assertContains( 'main.php', $basenames, 'main.php should be checked' );
		// Verify vendor excluded
		$this->assertNotContains( 'autoload.php', $basenames, 'vendor/autoload.php should be ignored' );
		// Verify *.md excluded
		$this->assertNotContains( 'README.md', $basenames, '*.md should be ignored' );
	}

	public function test_default_exclusions_are_respected() {
		// By default, vendor and node_modules are executed.
		// Note: In isolated test environment, we rely on Plugin_Request_Utility default lists.
		// We need to ensure no other filters are interfering (tearDown/setUp does this).
		
		// Create default ignored structure
		// vendor already created in setUp
		file_put_contents( $this->plugin_root . '/vendor/lib.php', '<?php' );
		
		if ( ! file_exists( $this->plugin_root . '/node_modules' ) ) {
			mkdir( $this->plugin_root . '/node_modules', 0777, true );
		}
		file_put_contents( $this->plugin_root . '/node_modules/tool.js', '{}' );
		
		file_put_contents( $this->plugin_root . '/main.php', '<?php' );

		$context = new Check_Context( $this->plugin_root . '/plugin.php' );
		$result  = new Check_Result( $context );
		$check   = new Isolated_Test_File_Check();
		$check->run( $result );

		$files_checked = $check->files_checked;
		$basenames = array_map( 'basename', $files_checked );

		$this->assertContains( 'main.php', $basenames );
		
		// vendor and node_modules are in the default exclude list in Plugin_Request_Utility.
		$this->assertNotContains( 'lib.php', $basenames, 'Default vendor directory should be excluded' );
		$this->assertNotContains( 'tool.js', $basenames, 'Default node_modules should be excluded' );
	}
}
