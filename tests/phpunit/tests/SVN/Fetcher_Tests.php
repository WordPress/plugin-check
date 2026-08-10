<?php
/**
 * Tests for the Fetcher class.
 *
 * @package plugin-check
 */

namespace SVN;

use WordPress\Plugin_Check\SVN\Fetcher;
use WP_UnitTestCase;

class Fetcher_Tests extends WP_UnitTestCase {

	protected $fetcher;

	public function set_up() {
		parent::set_up();
		$this->fetcher = new Fetcher( 'https://plugins.svn.wordpress.org/example/' );
	}

	public function test_parse_readme_extracts_fields() {
		$content = <<<README
=== My Awesome Plugin ===
Contributors: someone
Tags: foo, bar
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.2.3

Description here.
README;

		$this->assertSame(
			array(
				'stable_tag'        => '1.2.3',
				'requires_at_least' => '5.0',
				'tested_up_to'      => '6.4',
				'requires_php'      => '7.4',
				'name'              => 'My Awesome Plugin',
			),
			$this->fetcher->parse_readme( $content )
		);
	}

	public function test_parse_readme_supports_markdown_name_header() {
		$content = <<<README
# My Awesome Plugin

Stable tag: 2.0.0
README;

		$data = $this->fetcher->parse_readme( $content );

		$this->assertSame( 'My Awesome Plugin', $data['name'] );
		$this->assertSame( '2.0.0', $data['stable_tag'] );
	}

	public function test_parse_readme_returns_empty_array_for_no_matches() {
		$this->assertSame( array(), $this->fetcher->parse_readme( 'Just some plain text.' ) );
	}

	public function test_parse_plugin_headers_extracts_fields() {
		$content = <<<PHP
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Version: 1.2.3
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Author: John Doe
 */
PHP;

		$this->assertSame(
			array(
				'Plugin Name'       => 'My Awesome Plugin',
				'Version'           => '1.2.3',
				'Requires at least' => '5.0',
				'Tested up to'      => '6.4',
				'Requires PHP'      => '7.4',
				'Author'            => 'John Doe',
			),
			$this->fetcher->parse_plugin_headers( $content )
		);
	}

	public function test_parse_plugin_headers_returns_empty_array_for_no_matches() {
		$content = <<<PHP
<?php
echo 'Hello world';
PHP;

		$this->assertSame( array(), $this->fetcher->parse_plugin_headers( $content ) );
	}

	public function test_fetch_directory_strips_trailing_slash_from_directory_names() {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => '<a href="trunk/">trunk/</a><a href="tags/">tags/</a><a href="readme.txt">readme.txt</a>',
				);
			}
		);

		$result = $this->fetcher->fetch_directory( '' );

		$this->assertSame(
			array( 'trunk', 'tags', 'readme.txt' ),
			array_column( $result['items'], 'name' )
		);
		$this->assertTrue( $result['items'][0]['is_dir'] );
		$this->assertFalse( $result['items'][2]['is_dir'] );
	}
}
