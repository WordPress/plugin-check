<?php
/**
 * Tests for the Readme_Utils trait.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Traits\Readme_Utils;

/**
 * Test class for Readme_Utils trait.
 */
class Readme_Utils_Tests extends WP_UnitTestCase {

	use Readme_Utils;

	/**
	 * Test that an empty array of files returns an empty array.
	 */
	public function test_filter_files_for_readme_with_empty_files_array() {
		$result = $this->filter_files_for_readme( array(), '/path/to/plugin/' );
		$this->assertEmpty( $result );
	}

	/**
	 * Test that files containing no readme files return an empty array.
	 */
	public function test_filter_files_for_readme_with_no_readme_files() {
		$files = array(
			'/path/to/plugin/plugin.php',
			'/path/to/plugin/index.php',
			'/path/to/plugin/style.css',
			'/path/to/plugin/includes/class-main.php',
		);

		$result = $this->filter_files_for_readme( $files, '/path/to/plugin/' );
		$this->assertEmpty( $result );
	}

	/**
	 * Test that a root readme.txt file is correctly filtered and returned.
	 */
	public function test_filter_files_for_readme_with_root_readme_txt() {
		$files = array(
			'/path/to/plugin/plugin.php',
			'/path/to/plugin/readme.txt',
			'/path/to/plugin/includes/class-main.php',
		);

		$result = $this->filter_files_for_readme( $files, '/path/to/plugin/' );
		$this->assertSame( array( 1 => '/path/to/plugin/readme.txt' ), $result );
	}

	/**
	 * Test that a root readme.md file is returned when no readme.txt exists.
	 */
	public function test_filter_files_for_readme_with_root_readme_md() {
		$files = array(
			'/path/to/plugin/plugin.php',
			'/path/to/plugin/readme.md',
			'/path/to/plugin/includes/class-main.php',
		);

		$result = $this->filter_files_for_readme( $files, '/path/to/plugin/' );
		$this->assertSame( array( 1 => '/path/to/plugin/readme.md' ), $result );
	}

	/**
	 * Test that readme.txt is prioritized over readme.md when both exist at root.
	 */
	public function test_filter_files_for_readme_prefers_readme_txt_over_readme_md() {
		$files = array(
			'/path/to/plugin/plugin.php',
			'/path/to/plugin/readme.md',
			'/path/to/plugin/readme.txt',
		);

		$result = $this->filter_files_for_readme( $files, '/path/to/plugin/' );
		$this->assertSame( array( 2 => '/path/to/plugin/readme.txt' ), $result );
	}

	/**
	 * Test case-insensitivity of readme file detection.
	 *
	 * @dataProvider data_case_variations
	 *
	 * @param string $readme_file The readme file name to test.
	 */
	public function test_filter_files_for_readme_case_insensitivity( $readme_file ) {
		$files = array(
			'/path/to/plugin/plugin.php',
			'/path/to/plugin/' . $readme_file,
		);

		$result = $this->filter_files_for_readme( $files, '/path/to/plugin/' );
		$this->assertSame( array( 1 => '/path/to/plugin/' . $readme_file ), $result );
	}

	/**
	 * Data provider for readme filename case variations.
	 *
	 * @return array
	 */
	public function data_case_variations() {
		return array(
			'uppercase TXT'  => array( 'README.TXT' ),
			'uppercase MD'   => array( 'README.MD' ),
			'mixed case txt' => array( 'Readme.Txt' ),
			'mixed case md'  => array( 'Readme.Md' ),
		);
	}

	/**
	 * Test that readme files located in subdirectories are ignored.
	 */
	public function test_filter_files_for_readme_ignores_nested_readmes() {
		$files = array(
			'/path/to/plugin/plugin.php',
			'/path/to/plugin/assets/readme.txt',
			'/path/to/plugin/vendor/package/readme.md',
			'/path/to/plugin/docs/readme.txt',
		);

		$result = $this->filter_files_for_readme( $files, '/path/to/plugin/' );
		$this->assertEmpty( $result );
	}

	/**
	 * Test that root readme is returned while nested readme files are filtered out.
	 */
	public function test_filter_files_for_readme_with_root_and_nested_readmes() {
		$files = array(
			'/path/to/plugin/plugin.php',
			'/path/to/plugin/assets/readme.txt',
			'/path/to/plugin/readme.txt',
			'/path/to/plugin/vendor/package/readme.md',
		);

		$result = $this->filter_files_for_readme( $files, '/path/to/plugin/' );
		$this->assertSame( array( 2 => '/path/to/plugin/readme.txt' ), $result );
	}

	/**
	 * Test that files with non-matching extensions are ignored.
	 */
	public function test_filter_files_for_readme_ignores_non_matching_extensions() {
		$files = array(
			'/path/to/plugin/readme.html',
			'/path/to/plugin/readme.doc',
			'/path/to/plugin/readme.rst',
			'/path/to/plugin/readme.php',
			'/path/to/plugin/readme',
		);

		$result = $this->filter_files_for_readme( $files, '/path/to/plugin/' );
		$this->assertEmpty( $result );
	}

	/**
	 * Test various realistic file lists with different plugin paths via data provider.
	 *
	 * @dataProvider data_filter_files_for_readme_scenarios
	 *
	 * @param array  $files                File list.
	 * @param string $plugin_relative_path Plugin relative path.
	 * @param array  $expected             Expected filtered array.
	 */
	public function test_filter_files_for_readme_scenarios( array $files, $plugin_relative_path, array $expected ) {
		$result = $this->filter_files_for_readme( $files, $plugin_relative_path );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Data provider for filter_files_for_readme scenarios.
	 *
	 * @return array
	 */
	public function data_filter_files_for_readme_scenarios() {
		return array(
			'standard plugin with readme.txt'      => array(
				array(
					'/var/www/html/wp-content/plugins/sample-plugin/sample-plugin.php',
					'/var/www/html/wp-content/plugins/sample-plugin/readme.txt',
					'/var/www/html/wp-content/plugins/sample-plugin/uninstall.php',
				),
				'/var/www/html/wp-content/plugins/sample-plugin/',
				array(
					1 => '/var/www/html/wp-content/plugins/sample-plugin/readme.txt',
				),
			),
			'plugin with only readme.md'           => array(
				array(
					'/var/www/html/wp-content/plugins/sample-plugin/sample-plugin.php',
					'/var/www/html/wp-content/plugins/sample-plugin/readme.md',
				),
				'/var/www/html/wp-content/plugins/sample-plugin/',
				array(
					1 => '/var/www/html/wp-content/plugins/sample-plugin/readme.md',
				),
			),
			'plugin with both text and md at root' => array(
				array(
					'/var/www/html/wp-content/plugins/sample-plugin/readme.md',
					'/var/www/html/wp-content/plugins/sample-plugin/readme.txt',
				),
				'/var/www/html/wp-content/plugins/sample-plugin/',
				array(
					1 => '/var/www/html/wp-content/plugins/sample-plugin/readme.txt',
				),
			),
			'deeply nested path structure'         => array(
				array(
					'/app/web/wp/wp-content/plugins/advanced-tool/advanced-tool.php',
					'/app/web/wp/wp-content/plugins/advanced-tool/readme.txt',
					'/app/web/wp/wp-content/plugins/advanced-tool/sub/readme.txt',
				),
				'/app/web/wp/wp-content/plugins/advanced-tool/',
				array(
					1 => '/app/web/wp/wp-content/plugins/advanced-tool/readme.txt',
				),
			),
		);
	}
}
