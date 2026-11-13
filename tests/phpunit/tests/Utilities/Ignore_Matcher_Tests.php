<?php
/**
 * Tests for the Ignore_Matcher class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Utilities\Ignore_Matcher;

class Ignore_Matcher_Tests extends WP_UnitTestCase {

	public function test_split_anchored_entries_with_empty_array() {
		$result = Ignore_Matcher::split_anchored_entries( array() );

		$this->assertSame( array( array(), array() ), $result );
	}

	public function test_split_anchored_entries_skips_empty_string_entries() {
		$entries = array( '', '/anchored-dir', '', 'unanchored-dir', '' );
		$result  = Ignore_Matcher::split_anchored_entries( $entries );

		$this->assertSame(
			array(
				array( '/anchored-dir' ),
				array( 'unanchored-dir' ),
			),
			$result
		);
	}

	public function test_split_anchored_entries_separates_anchored_and_unanchored() {
		$entries = array(
			'/docs',
			'vendor',
			'/build/*.map',
			'*.log',
			'/src/file?.js',
			'node_modules',
		);

		$result = Ignore_Matcher::split_anchored_entries( $entries );

		$expected_anchored   = array( '/docs', '/build/*.map', '/src/file?.js' );
		$expected_unanchored = array( 'vendor', '*.log', 'node_modules' );

		$this->assertSame( array( $expected_anchored, $expected_unanchored ), $result );
	}

	public function test_split_anchored_entries_with_only_anchored() {
		$entries = array( '/dist', '/assets/*', '/readme.md' );
		$result  = Ignore_Matcher::split_anchored_entries( $entries );

		$this->assertSame( array( $entries, array() ), $result );
	}

	public function test_split_anchored_entries_with_only_unanchored() {
		$entries = array( 'tests', 'cache', 'temp.txt' );
		$result  = Ignore_Matcher::split_anchored_entries( $entries );

		$this->assertSame( array( array(), $entries ), $result );
	}

	public function test_is_file_in_ignored_directory_with_unanchored_directory() {
		$plugin_root = '/var/www/plugin';
		$directories = array( 'vendor', 'cache' );

		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/vendor/autoload.php', $plugin_root, $directories )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/assets/vendor/lib.js', $plugin_root, $directories )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/nested/deep/cache/item.json', $plugin_root, $directories )
		);
	}

	public function test_is_file_in_ignored_directory_unanchored_prevents_partial_name_match() {
		$plugin_root = '/var/www/plugin';
		$directories = array( 'vendor' );

		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/my-vendor/lib.php', $plugin_root, $directories )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/vendor-assets/lib.php', $plugin_root, $directories )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/vendors/lib.php', $plugin_root, $directories )
		);
	}

	public function test_is_file_in_ignored_directory_with_anchored_directory() {
		$plugin_root = '/var/www/plugin';
		$directories = array( '/docs' );

		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/docs/readme.txt', $plugin_root, $directories )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/docs/sub/guide.md', $plugin_root, $directories )
		);
	}

	public function test_is_file_in_ignored_directory_anchored_rejects_nested_directory() {
		$plugin_root = '/var/www/plugin';
		$directories = array( '/docs' );

		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/sub/docs/readme.txt', $plugin_root, $directories )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/assets/docs/guide.md', $plugin_root, $directories )
		);
	}

	public function test_is_file_in_ignored_directory_with_glob_wildcard_asterisk() {
		$plugin_root = '/var/www/plugin';
		$directories = array( '/build*' );

		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/build/app.js', $plugin_root, $directories )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/build-v1/app.js', $plugin_root, $directories )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/build-prod/app.js', $plugin_root, $directories )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/assets/build-v1/app.js', $plugin_root, $directories )
		);
	}

	public function test_is_file_in_ignored_directory_with_glob_wildcard_question_mark() {
		$plugin_root = '/var/www/plugin';
		$directories = array( '/temp-?' );

		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/temp-1/log.txt', $plugin_root, $directories )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/temp-a/log.txt', $plugin_root, $directories )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/temp-12/log.txt', $plugin_root, $directories )
		);
	}

	public function test_is_file_in_ignored_directory_handles_empty_entries_and_non_matches() {
		$plugin_root = '/var/www/plugin';

		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/src/index.php', $plugin_root, array() )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/src/index.php', $plugin_root, array( '' ) )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_in_ignored_directory( '/var/www/plugin/src/index.php', $plugin_root, array( 'vendor', '/build' ) )
		);
	}

	public function test_is_file_ignored_with_unanchored_file_suffix() {
		$plugin_root = '/var/www/plugin';
		$files       = array( 'app.min.js' );

		$this->assertTrue(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/app.min.js', $plugin_root, $files )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/dist/app.min.js', $plugin_root, $files )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/assets/js/app.min.js', $plugin_root, $files )
		);
	}

	public function test_is_file_ignored_unanchored_prevents_partial_name_match() {
		$plugin_root = '/var/www/plugin';
		$files       = array( 'app.min.js' );

		$this->assertFalse(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/my-app.min.js', $plugin_root, $files )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/app.min.js.map', $plugin_root, $files )
		);
	}

	public function test_is_file_ignored_with_anchored_exact_file() {
		$plugin_root = '/var/www/plugin';
		$files       = array( '/package.json' );

		$this->assertTrue(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/package.json', $plugin_root, $files )
		);
	}

	public function test_is_file_ignored_anchored_rejects_nested_file() {
		$plugin_root = '/var/www/plugin';
		$files       = array( '/package.json' );

		$this->assertFalse(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/assets/package.json', $plugin_root, $files )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/sub/package.json', $plugin_root, $files )
		);
	}

	public function test_is_file_ignored_with_glob_wildcard_asterisk() {
		$plugin_root = '/var/www/plugin';
		$files       = array( '/*.map' );

		$this->assertTrue(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/bundle.js.map', $plugin_root, $files )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/style.css.map', $plugin_root, $files )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/assets/bundle.js.map', $plugin_root, $files )
		);
	}

	public function test_is_file_ignored_with_glob_wildcard_question_mark() {
		$plugin_root = '/var/www/plugin';
		$files       = array( '/data-?.json' );

		$this->assertTrue(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/data-1.json', $plugin_root, $files )
		);
		$this->assertTrue(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/data-a.json', $plugin_root, $files )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/data-10.json', $plugin_root, $files )
		);
	}

	public function test_is_file_ignored_handles_empty_entries_and_non_matches() {
		$plugin_root = '/var/www/plugin';

		$this->assertFalse(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/main.php', $plugin_root, array() )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/main.php', $plugin_root, array( '' ) )
		);
		$this->assertFalse(
			Ignore_Matcher::is_file_ignored( '/var/www/plugin/main.php', $plugin_root, array( 'app.js', '/config.json' ) )
		);
	}

	public function test_get_php_codesniffer_directory_ignore_pattern() {
		$plugin_root = '/var/www/plugin';

		$pattern1 = Ignore_Matcher::get_php_codesniffer_directory_ignore_pattern( $plugin_root, '/docs*' );
		$this->assertSame( '^/var/www/plugin/docs[^/]{0,}/*', $pattern1 );

		$pattern2 = Ignore_Matcher::get_php_codesniffer_directory_ignore_pattern( $plugin_root, '/temp-?' );
		$this->assertSame( '^/var/www/plugin/temp\-[^/]/*', $pattern2 );

		$pattern3 = Ignore_Matcher::get_php_codesniffer_directory_ignore_pattern( $plugin_root, '/data[1]' );
		$this->assertSame( '^/var/www/plugin/data\\[1\\]/*', $pattern3 );

		$pattern4 = Ignore_Matcher::get_php_codesniffer_directory_ignore_pattern( $plugin_root, '/v1.0' );
		$this->assertSame( '^/var/www/plugin/v1\\.0/*', $pattern4 );
	}

	public function test_get_php_codesniffer_file_ignore_pattern() {
		$plugin_root = '/var/www/plugin';

		$pattern1 = Ignore_Matcher::get_php_codesniffer_file_ignore_pattern( $plugin_root, '/*.map' );
		$this->assertSame( '^/var/www/plugin/[^/]{0,}\\.map$', $pattern1 );

		$pattern2 = Ignore_Matcher::get_php_codesniffer_file_ignore_pattern( $plugin_root, '/file?.php' );
		$this->assertSame( '^/var/www/plugin/file[^/]\\.php$', $pattern2 );

		$pattern3 = Ignore_Matcher::get_php_codesniffer_file_ignore_pattern( $plugin_root, '/data[1].php' );
		$this->assertSame( '^/var/www/plugin/data\\[1\\]\\.php$', $pattern3 );

		$pattern4 = Ignore_Matcher::get_php_codesniffer_file_ignore_pattern( $plugin_root, '/v1.0+beta/*.js' );
		$this->assertSame( '^/var/www/plugin/v1\\.0\\+beta/[^/]{0,}\\.js$', $pattern4 );
	}
}
