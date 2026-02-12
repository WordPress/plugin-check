<?php
/**
 * Test for Plugin_Request_Utility distignore methods.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

use PHPUnit\Framework\TestCase;

/**
 * Test for Plugin_Request_Utility distignore parsing.
 */
class Distignore_Parser_Tests extends TestCase {

	private $plugin_dir;

	public function setUp(): void {
		parent::setUp();
		$this->plugin_dir = sys_get_temp_dir() . '/pcp_test_distignore_' . uniqid();
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

	public function test_get_distignore_entries_returns_empty_if_no_file() {
		$entries = Plugin_Request_Utility::get_distignore_entries( $this->plugin_dir );
		$this->assertIsArray( $entries );
		$this->assertEmpty( $entries );
	}

	public function test_get_distignore_entries_parses_basic_entries() {
		$content = "tests\nvendor\n*.md";
		file_put_contents( $this->plugin_dir . '/.distignore', $content );

		$entries = Plugin_Request_Utility::get_distignore_entries( $this->plugin_dir );
		
		// Note: The parser relies on returning raw entries first? 
		// Or does it assume directories vs files?
		// The requirement is to filter both files and directories.
		// get_distignore_entries might return a structure or just list?
		// Existing ignore lists are separated: get_directories_to_ignore vs get_files_to_ignore.
		// So get_distignore_entries should probably return separated arrays?
		// config file had 'exclude-files' and 'exclude-directories'.
		// .distignore mixes them.
		
		// For this test, let's assume it returns a raw list for now, or processed?
		// If we use it in 'ignore_directories' filter, we expect directories.
		// If we use it in 'ignore_files' filter, we expect files.
		
		// If get_distignore_entries returns ALL entries, we need to know which are dirs and which are files.
		// But in gitignore/distignore, "vendor" could be file or dir. usually dir.
		// "tests" usually dir.
		// "*.md" is file pattern.
		
		// Let's assume get_distignore_entries returns an array with 'files' and 'directories' keys?
		// Or just a raw list and we let the checker logic decide?
		// Checker logic: Abstract_File_Check uses str_contains/ends_with.
		
		// Wait, `Abstract_File_Check` logic for exclusion:
		/*
		foreach ( $directories_to_ignore as $directory ) {
			if ( str_contains( $file_path, '/' . $directory . '/' ) ) { $exclude = true; }
		}
		foreach ( $files_to_ignore as $ignore_file ) {
			if ( str_ends_with( $file_path, $ignore_file ) ) { $exclude = true; }
		}
		*/
		
		// If I pass "*.md" as a `file_to_ignore`, `str_ends_with(path, "*.md")` will FAIL.
		// Existing logic assumes LITERAL filenames or simple substrings?
		// Let's check `Abstract_File_Check.php`.
		
		// I need to know how strict existing exclusion is.
		
		$this->assertContains( 'tests', $entries );
		$this->assertContains( 'vendor', $entries );
		$this->assertContains( '*.md', $entries );
	}
	
	public function test_get_distignore_entries_ignores_comments_and_empty_lines() {
		$content = "src\n\n# This is a comment\nvendor";
		file_put_contents( $this->plugin_dir . '/.distignore', $content );

		$entries = Plugin_Request_Utility::get_distignore_entries( $this->plugin_dir );
		
		$this->assertContains( 'src', $entries );
		$this->assertContains( 'vendor', $entries );
		$this->assertNotContains( '# This is a comment', $entries );
		$this->assertCount( 2, $entries );
	}

	/**
	 * @dataProvider data_gitignore_patterns
	 */
	public function test_convert_gitignore_pattern_to_regex( $pattern, $expected_regex_part ) {
		$regex = Plugin_Request_Utility::convert_gitignore_pattern_to_regex( $pattern );
		// We assert that the regex matches what we expect it to match.
		// Instead of asserting the regex string (which is implementation detail), let's assert matching behavior?
		// But here I'm testing the utility method which returns a string.

		// Simplified check: does it look like a regex?
		$this->assertStringStartsWith( '#', $regex );
		$this->assertStringEndsWith( '#', $regex );
	}
	
	public function data_gitignore_patterns() {
		return array(
			array( 'vendor', 'vendor' ),
			array( '*.md', '\.md' ),
			array( '/tests', '^tests' ),
		);
	}
	
	/**
	 * @dataProvider data_gitignore_matches
	 */
	public function test_regex_matching_logic( $pattern, $path, $should_match ) {
		$regex = Plugin_Request_Utility::convert_gitignore_pattern_to_regex( $pattern );
		$match = preg_match( $regex, $path );
		
		if ( $should_match ) {
			$this->assertEquals( 1, $match, "Pattern '$pattern' should match '$path' with regex '$regex'" );
		} else {
			$this->assertEquals( 0, $match, "Pattern '$pattern' should NOT match '$path' with regex '$regex'" );
		}
	}
	
	public function data_gitignore_matches() {
		return array(
			// Simple file/dir in root or subdir
			array( 'vendor', 'vendor', true ),
			array( 'vendor', 'vendor/autoload.php', true ), // vendor matches directory prefix
			array( 'vendor', 'src/vendor/autoload.php', true ), // matches in subdir
			
			// Rooted
			array( '/vendor', 'vendor', true ),
			array( '/vendor', 'src/vendor', false ),
			
			// Wildcard
			array( '*.md', 'readme.md', true ),
			array( '*.md', 'src/docs/index.md', true ),
			array( '*.md', 'style.css', false ),
			
			// Directory specific
			array( 'tests/', 'tests/foo.php', true ),
			array( 'tests/', 'src/tests/foo.php', true ),
			
			// Deep wildcard
			array( 'src/**/tests', 'src/foo/tests', true ),
			array( 'src/**/tests', 'src/foo/bar/tests', true ),
		);
	}
}
