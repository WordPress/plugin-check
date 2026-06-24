<?php
/**
 * Tests for Plugin_Request_Utility config, distignore, and regex methods.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

class Plugin_Request_Utility_Config_Tests extends WP_UnitTestCase {

	protected $plugin_dir;

	public function set_up() {
		parent::set_up();
		$this->plugin_dir = sys_get_temp_dir() . '/pcp_test_config_' . uniqid();
		mkdir( $this->plugin_dir, 0777, true );
	}

	public function tear_down() {
		$this->recursive_rmdir( $this->plugin_dir );
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

	public function test_get_plugin_configuration_loads_valid_json() {
		$config = array(
			'exclude-directories' => array( 'vendor' ),
			'exclude-files'       => array( 'readme.md' ),
		);
		file_put_contents(
			$this->plugin_dir . '/.plugin-check.json',
			wp_json_encode( $config )
		);

		$result = Plugin_Request_Utility::get_plugin_configuration( $this->plugin_dir );

		$this->assertSame( $config, $result );
	}

	public function test_get_plugin_configuration_returns_empty_for_missing_file() {
		$result = Plugin_Request_Utility::get_plugin_configuration( $this->plugin_dir );
		$this->assertSame( array(), $result );
	}

	public function test_get_plugin_configuration_returns_empty_for_invalid_json() {
		file_put_contents( $this->plugin_dir . '/.plugin-check.json', '{invalid' );

		$result = Plugin_Request_Utility::get_plugin_configuration( $this->plugin_dir );
		$this->assertSame( array(), $result );
	}

	public function test_get_plugin_configuration_returns_empty_for_empty_file() {
		file_put_contents( $this->plugin_dir . '/.plugin-check.json', '' );

		$result = Plugin_Request_Utility::get_plugin_configuration( $this->plugin_dir );
		$this->assertSame( array(), $result );
	}

	public function test_get_distignore_entries_parses_lines() {
		file_put_contents(
			$this->plugin_dir . '/.distignore',
			"tests\n*.md\n# comment\n\nvendor\n"
		);

		$entries = Plugin_Request_Utility::get_distignore_entries( $this->plugin_dir );

		$this->assertSame( array( 'tests', '*.md', 'vendor' ), $entries );
	}

	public function test_get_distignore_entries_returns_empty_for_missing_file() {
		$entries = Plugin_Request_Utility::get_distignore_entries( $this->plugin_dir );
		$this->assertSame( array(), $entries );
	}

	public function test_get_distignore_entries_returns_empty_for_empty_file() {
		file_put_contents( $this->plugin_dir . '/.distignore', '' );

		$entries = Plugin_Request_Utility::get_distignore_entries( $this->plugin_dir );
		$this->assertSame( array(), $entries );
	}

	public function test_convert_gitignore_pattern_to_regex_matches_simple_filename() {
		$regex = Plugin_Request_Utility::convert_gitignore_pattern_to_regex( 'readme.md' );

		$this->assertMatchesRegularExpression( $regex, 'readme.md' );
		$this->assertDoesNotMatchRegularExpression( $regex, 'other.md' );
	}

	public function test_convert_gitignore_pattern_to_regex_matches_wildcard_extension() {
		$regex = Plugin_Request_Utility::convert_gitignore_pattern_to_regex( '*.md' );

		$this->assertMatchesRegularExpression( $regex, 'README.md' );
		$this->assertMatchesRegularExpression( $regex, 'docs/readme.md' );
		$this->assertDoesNotMatchRegularExpression( $regex, 'readme.txt' );
	}

	public function test_convert_gitignore_pattern_to_regex_matches_directory() {
		$regex = Plugin_Request_Utility::convert_gitignore_pattern_to_regex( 'vendor/' );

		$this->assertMatchesRegularExpression( $regex, 'vendor/' );
		$this->assertDoesNotMatchRegularExpression( $regex, 'vendorlib' );
	}

	public function test_convert_gitignore_pattern_to_regex_matches_double_star() {
		$regex = Plugin_Request_Utility::convert_gitignore_pattern_to_regex( '**/test' );

		$this->assertMatchesRegularExpression( $regex, 'deep/nested/test' );
		$this->assertDoesNotMatchRegularExpression( $regex, 'testing' );
	}

	public function test_convert_gitignore_pattern_to_regex_matches_question_mark() {
		$regex = Plugin_Request_Utility::convert_gitignore_pattern_to_regex( 'file?.txt' );

		$this->assertMatchesRegularExpression( $regex, 'file1.txt' );
		$this->assertDoesNotMatchRegularExpression( $regex, 'file12.txt' );
	}

	public function test_convert_gitignore_pattern_to_regex_root_anchored() {
		$regex = Plugin_Request_Utility::convert_gitignore_pattern_to_regex( '/build' );

		$this->assertMatchesRegularExpression( $regex, 'build' );
		$this->assertDoesNotMatchRegularExpression( $regex, 'src/build' );
	}

	public function data_gitignore_patterns() {
		return array(
			'simple file'   => array( 'readme.md', '#(?:^|/)readme\.md(?:/|$)#' ),
			'wildcard ext'  => array( '*.md', '#(?:^|/)[^/]*\.md(?:/|$)#' ),
			'directory'     => array( 'vendor/', '#(?:^|/)vendor/#' ),
			'double star'   => array( '**/test', '#(?:^|/).*/test(?:/|$)#' ),
			'question mark' => array( 'file?.txt', '#(?:^|/)file[^/]\.txt(?:/|$)#' ),
			'root anchored' => array( '/build', '#^build(?:/|$)#' ),
		);
	}

	/**
	 * @dataProvider data_gitignore_patterns
	 */
	public function test_convert_gitignore_pattern_to_regex_structure( $pattern, $expected ) {
		$regex = Plugin_Request_Utility::convert_gitignore_pattern_to_regex( $pattern );
		$this->assertSame( $expected, $regex );
	}

	public function test_load_filters_from_config_registers_distignore_filter() {
		file_put_contents( $this->plugin_dir . '/.distignore', "*.md\nvendor\n" );

		Plugin_Request_Utility::load_filters_from_config( $this->plugin_dir );

		$this->assertTrue( has_filter( 'wp_plugin_check_ignore_patterns' ) !== false );
	}

	public function test_load_filters_from_config_registers_config_filters() {
		$config = array(
			'exclude-directories' => array( 'build' ),
			'exclude-files'       => array( 'changelog.txt' ),
			'include-directories' => array( 'src' ),
			'include-files'       => array( 'index.php' ),
		);
		file_put_contents(
			$this->plugin_dir . '/.plugin-check.json',
			wp_json_encode( $config )
		);

		Plugin_Request_Utility::load_filters_from_config( $this->plugin_dir );

		$this->assertTrue( has_filter( 'wp_plugin_check_ignore_directories' ) !== false );
		$this->assertTrue( has_filter( 'wp_plugin_check_ignore_files' ) !== false );
		$this->assertTrue( has_filter( 'wp_plugin_check_include_directories' ) !== false );
		$this->assertTrue( has_filter( 'wp_plugin_check_include_files' ) !== false );
	}

	public function test_load_filters_from_config_with_no_config_files() {
		Plugin_Request_Utility::load_filters_from_config( $this->plugin_dir );

		$this->assertFalse( has_filter( 'wp_plugin_check_ignore_patterns' ) );
		$this->assertFalse( has_filter( 'wp_plugin_check_ignore_directories' ) );
	}
}
