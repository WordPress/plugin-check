<?php
/**
 * Tests for the Checker class.
 *
 * @package plugin-check
 */

namespace SVN;

use WordPress\Plugin_Check\SVN\Checker;
use WordPress\Plugin_Check\SVN\Section;
use WP_UnitTestCase;

class Checker_Tests extends WP_UnitTestCase {

	/**
	 * Map of relative SVN path (e.g. 'trunk/readme.txt') to a canned response.
	 *
	 * @var array<string, array{code: int, body: string}>
	 */
	protected $svn_mock_responses = array();

	public function set_up() {
		parent::set_up();
		$this->svn_mock_responses = array();
		add_filter( 'pre_http_request', array( $this, 'intercept_http_request' ), 10, 3 );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'intercept_http_request' ) );
		parent::tear_down();
	}

	/**
	 * Intercepts wp_remote_get() calls and returns a canned response based on
	 * the requested URL's suffix, so no real network request is made.
	 * Unmocked URLs default to a 404, so a missing stub fails loudly.
	 */
	public function intercept_http_request( $preempt, $args, $url ) {
		foreach ( $this->svn_mock_responses as $relative_path => $response ) {
			if ( substr( $url, -strlen( $relative_path ) ) === $relative_path ) {
				return array(
					'response' => array( 'code' => $response['code'] ),
					'body'     => $response['body'] ?? '',
				);
			}
		}

		return array(
			'response' => array( 'code' => 404 ),
			'body'     => '',
		);
	}

	private function get_section_by_id( array $sections, string $id ): ?Section {
		foreach ( $sections as $section ) {
			if ( $id === $section->id ) {
				return $section;
			}
		}

		return null;
	}

	private function get_check_by_key( array $checks, string $key ): ?array {
		foreach ( $checks as $check ) {
			if ( $key === $check['key'] ) {
				return $check;
			}
		}

		return null;
	}

	public function test_run_returns_not_found_when_trunk_does_not_exist() {
		// No mocked responses at all — every request defaults to a 404.
		$checker = new Checker( 'nonexistent-plugin' );
		$report  = $checker->run();

		$this->assertSame( 'nonexistent-plugin', $report['slug'] );
		$this->assertSame( 'not_found', $report['meta']['error'] );
		$this->assertSame( array(), $report['sections'] );
	}

	public function test_run_happy_path_with_matching_stable_tag() {
		$this->svn_mock_responses = array(
			'trunk/readme.txt'       => array(
				'code' => 200,
				'body' => "=== Example Plugin ===\nStable tag: 1.0.0\nRequires PHP: 7.4\nTested up to: 6.4\n",
			),
			'trunk/'                 => array(
				'code' => 200,
				'body' => '<a href="example.php">example.php</a>',
			),
			'trunk/example.php'      => array(
				'code' => 200,
				'body' => "Plugin Name: Example Plugin\nVersion: 1.0.0\n",
			),
			'tags/'                  => array( 'code' => 200 ),
			'assets/'                => array(
				'code' => 200,
				'body' => '<a href="banner-772x250.png">banner-772x250.png</a><a href="icon-128x128.png">icon-128x128.png</a>',
			),
			'tags/1.0.0/'            => array( 'code' => 200 ),
			'tags/1.0.0/readme.txt'  => array(
				'code' => 200,
				'body' => "=== Example Plugin ===\nStable tag: 1.0.0\n",
			),
			'tags/1.0.0/example.php' => array(
				'code' => 200,
				'body' => "Plugin Name: Example Plugin\nVersion: 1.0.0\n",
			),
		);

		$checker = new Checker( 'example' );
		$report  = $checker->run();

		$this->assertSame( 'example', $report['slug'] );
		$this->assertSame(
			array(
				'plugin_name'   => 'Example Plugin',
				'plugin_file'   => 'example.php',
				'stable_tag'    => '1.0.0',
				'trunk_version' => '1.0.0',
				'requires_php'  => '7.4',
				'tested_up_to'  => '6.4',
				'svn_url'       => 'https://plugins.svn.wordpress.org/example/',
			),
			$report['meta']
		);

		$root_section = $this->get_section_by_id( $report['sections'], 'root' );
		$this->assertSame( 'pass', $this->get_check_by_key( $root_section->get_checks(), 'root_tags_exists' )['status'] );
		$this->assertSame( 'pass', $this->get_check_by_key( $root_section->get_checks(), 'root_assets_exists' )['status'] );

		$trunk_section = $this->get_section_by_id( $report['sections'], 'trunk' );
		$this->assertSame( 'pass', $this->get_check_by_key( $trunk_section->get_checks(), 'trunk_stable_tag_matches_version' )['status'] );

		$stable_tag_section = $this->get_section_by_id( $report['sections'], 'stable_tag' );
		$this->assertNotNull( $stable_tag_section );
		$this->assertSame( 'pass', $this->get_check_by_key( $stable_tag_section->get_checks(), 'tag_readme_stable_tag_matches_trunk' )['status'] );
		$this->assertSame( 'pass', $this->get_check_by_key( $stable_tag_section->get_checks(), 'tag_php_version_matches_trunk' )['status'] );

		$assets_section = $this->get_section_by_id( $report['sections'], 'assets' );
		$this->assertSame( 'pass', $this->get_check_by_key( $assets_section->get_checks(), 'assets_banner_present' )['status'] );
		$this->assertSame( 'pass', $this->get_check_by_key( $assets_section->get_checks(), 'assets_icon_present' )['status'] );
	}

	public function test_run_without_stable_tag_skips_stable_tag_section() {
		$this->svn_mock_responses = array(
			'trunk/readme.txt' => array(
				'code' => 200,
				'body' => "=== Example Plugin ===\nRequires PHP: 7.4\n",
			),
			'trunk/'           => array(
				'code' => 200,
				'body' => '',
			),
		);

		$checker = new Checker( 'example' );
		$report  = $checker->run();

		$this->assertNull( $this->get_section_by_id( $report['sections'], 'stable_tag' ) );

		$trunk_section = $this->get_section_by_id( $report['sections'], 'trunk' );
		$this->assertSame( 'fail', $this->get_check_by_key( $trunk_section->get_checks(), 'trunk_stable_tag_declared' )['status'] );
		$this->assertSame( 'warn', $this->get_check_by_key( $trunk_section->get_checks(), 'trunk_main_php_file_found' )['status'] );
		$this->assertSame( 'warn', $this->get_check_by_key( $trunk_section->get_checks(), 'trunk_version_declared' )['status'] );
	}
}
