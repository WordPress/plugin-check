<?php
/**
 * Tests for the WordPress.org directory lookup in the AI_Check_Names trait.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Traits\AI_Check_Names;

/**
 * Test class for directory matching in the AI_Check_Names trait.
 */
class AI_Check_Names_Directory_Tests extends WP_UnitTestCase {

	use AI_Check_Names;

	/**
	 * Fake directory, keyed by slug.
	 *
	 * @var array
	 */
	private $directory = array();

	/**
	 * Fake search results, keyed by search term.
	 *
	 * @var array
	 */
	private $search_results = array();

	/**
	 * Number of `plugins_api` calls made, keyed by action.
	 *
	 * @var array
	 */
	private $api_calls = array();

	public function set_up() {
		parent::set_up();

		$this->api_calls = array(
			'plugin_information' => 0,
			'query_plugins'      => 0,
		);
		$this->directory = array(
			// Mirrors the real API response, which HTML-encodes the name.
			'less-flux' => array(
				'slug'            => 'less-flux',
				'name'            => 'LessFlux Image &amp; Video Optimizer &#8211; WebP, AVIF, CDN &amp; Lazy Load',
				'author'          => '<a href="https://profiles.wordpress.org/lessflux/">LessFlux</a>',
				'author_profile'  => 'https://profiles.wordpress.org/lessflux/',
				'active_installs' => 100,
			),
		);

		$unrelated = array(
			'slug'            => 'ewww-image-optimizer',
			'name'            => 'EWWW Image Optimizer',
			'author'          => 'Exactly WWW',
			'author_profile'  => 'https://profiles.wordpress.org/nosilver4u/',
			'active_installs' => 1000000,
		);

		$this->search_results = array(
			'LessFlux Image & Video Optimizer – WebP, AVIF, CDN & Lazy Load' => array( $this->directory['less-flux'], $unrelated ),
			'lessflux'  => array( $this->directory['less-flux'] ),
			'optimizer' => array( $unrelated ),
			'webp'      => array( $unrelated ),
			'avif'      => array( $unrelated ),
		);

		add_filter( 'plugins_api', array( $this, 'mock_plugins_api' ), 10, 3 );
	}

	public function tear_down() {
		remove_filter( 'plugins_api', array( $this, 'mock_plugins_api' ), 10 );
		parent::tear_down();
	}

	/**
	 * Mocks `plugins_api()` responses.
	 *
	 * @param false|object $result Default result.
	 * @param string       $action API action.
	 * @param object       $args   API arguments.
	 * @return object|WP_Error Mocked response.
	 */
	public function mock_plugins_api( $result, $action, $args ) {
		++$this->api_calls[ $action ];

		if ( 'plugin_information' === $action ) {
			if ( isset( $this->directory[ $args->slug ] ) ) {
				return (object) $this->directory[ $args->slug ];
			}
			return new WP_Error( 'plugins_api_failed', 'Plugin not found.' );
		}

		if ( 'query_plugins' === $action ) {
			$plugins = isset( $this->search_results[ $args->search ] ) ? $this->search_results[ $args->search ] : array();
			return (object) array( 'plugins' => $plugins );
		}

		return $result;
	}

	/**
	 * Returns the directory match for a slug.
	 *
	 * @param array  $matches Matches.
	 * @param string $slug    Slug.
	 * @return array|null Match, or null if missing.
	 */
	private function find_match( $matches, $slug ) {
		foreach ( $matches as $match ) {
			if ( $slug === $match['slug'] ) {
				return $match;
			}
		}
		return null;
	}

	/**
	 * The example from issue #1386 is detected as an exact match despite the encoded API name.
	 */
	public function test_issue_1386_example_is_exact_match() {
		$matches = $this->query_wordpress_org_directory( 'LessFlux Image & Video Optimizer – WebP, AVIF, CDN & Lazy Load' );
		$match   = $this->find_match( $matches, 'less-flux' );

		$this->assertNotNull( $match );
		$this->assertTrue( $match['is_exact_match'] );
		$this->assertSame( 'Exact Match', $match['similarity_level'] );
		$this->assertSame( 'LessFlux Image & Video Optimizer – WebP, AVIF, CDN & Lazy Load', $match['name'] );
	}

	/**
	 * Unrelated search results are not surfaced.
	 */
	public function test_unrelated_search_results_are_filtered_out() {
		$matches = $this->query_wordpress_org_directory( 'LessFlux Image & Video Optimizer – WebP, AVIF, CDN & Lazy Load' );

		$this->assertNull( $this->find_match( $matches, 'ewww-image-optimizer' ) );
		$this->assertCount( 1, $matches );
	}

	/**
	 * A different name that starts with an existing distinctive slug is flagged as High, not exact.
	 */
	public function test_leading_slug_is_high_similarity() {
		$matches = $this->query_wordpress_org_directory( 'LessFlux Booster' );
		$match   = $this->find_match( $matches, 'less-flux' );

		$this->assertNotNull( $match );
		$this->assertFalse( $match['is_exact_match'] );
		$this->assertSame( 'High', $match['similarity_level'] );
	}

	/**
	 * Similarity levels for direct comparisons.
	 */
	public function test_get_directory_similarity_level() {
		$this->assertSame( 'Exact Match', $this->get_directory_similarity_level( 'less-flux', 'Whatever', 'lessflux', 'LessFlux' ) );
		$this->assertSame( 'Exact Match', $this->get_directory_similarity_level( 'my-plugin', 'My Plugin', 'my-plugin', 'My Plugin' ) );
		$this->assertSame( 'High', $this->get_directory_similarity_level( 'contact-form-7', 'Contact Form 7', 'contact-form-8', 'Contact Form 8' ) );
		$this->assertSame( '', $this->get_directory_similarity_level( 'ewww-image-optimizer', 'EWWW Image Optimizer', 'lessflux-image-video-optimizer', 'LessFlux Image Video Optimizer' ) );
		// Generic leading words do not count as a match.
		$this->assertSame( '', $this->get_directory_similarity_level( 'image', 'Image', 'image-gallery-pro-max', 'Image Gallery Pro Max' ) );
	}

	/**
	 * Plugins by the same author are marked and do not force a naming issue.
	 */
	public function test_same_author_does_not_force_naming_issue() {
		$matches = $this->query_wordpress_org_directory( 'LessFlux Image & Video Optimizer – WebP, AVIF, CDN & Lazy Load', 'LessFlux' );
		$match   = $this->find_match( $matches, 'less-flux' );

		$this->assertTrue( $match['is_same_author'] );

		$parsed = array(
			'possible_naming_issues' => false,
			'naming_explanation'     => '',
		);
		$this->enforce_directory_match_verdict( $parsed, array( 'confusion_existing_plugins' => $matches ) );

		$this->assertFalse( $parsed['possible_naming_issues'] );
	}

	/**
	 * Exact matches by a different author force a naming issue.
	 */
	public function test_other_author_exact_match_forces_naming_issue() {
		$matches = $this->query_wordpress_org_directory( 'LessFlux Image & Video Optimizer – WebP, AVIF, CDN & Lazy Load', 'Someone Else' );
		$match   = $this->find_match( $matches, 'less-flux' );

		$this->assertFalse( $match['is_same_author'] );

		$parsed = array(
			'possible_naming_issues' => false,
			'naming_explanation'     => '',
		);
		$this->enforce_directory_match_verdict( $parsed, array( 'confusion_existing_plugins' => $matches ) );

		$this->assertTrue( $parsed['possible_naming_issues'] );
		$this->assertStringContainsString( 'https://wordpress.org/plugins/less-flux/', $parsed['naming_explanation'] );
	}

	/**
	 * Same author matching also works against the profile username.
	 */
	public function test_is_same_directory_author_matches_profile() {
		$plugin = (object) $this->directory['less-flux'];

		$this->assertTrue( $this->is_same_directory_author( $plugin, 'lessflux' ) );
		$this->assertTrue( $this->is_same_directory_author( $plugin, 'Less Flux' ) );
		$this->assertFalse( $this->is_same_directory_author( $plugin, 'Other Brand' ) );
		$this->assertFalse( $this->is_same_directory_author( $plugin, '' ) );
	}

	/**
	 * Directory lookups are cached.
	 */
	public function test_directory_lookups_are_cached() {
		$name = 'LessFlux Image & Video Optimizer – WebP, AVIF, CDN & Lazy Load';

		$this->query_wordpress_org_directory( $name );
		$this->assertGreaterThan( 0, $this->api_calls['query_plugins'] );
		$this->assertGreaterThan( 0, $this->api_calls['plugin_information'] );

		$first_search_calls = $this->api_calls['query_plugins'];

		// Successful lookups come from the transient cache on the second run.
		$this->query_wordpress_org_directory( $name );
		$this->assertSame( $first_search_calls, $this->api_calls['query_plugins'] );
		$this->assertNotFalse(
			get_transient(
				'wp_plugin_check_namer_dir_' . md5(
					'query_plugins' . wp_json_encode(
						array(
							'search'   => 'lessflux',
							'per_page' => 5,
						)
					)
				)
			)
		);
	}

	/**
	 * Merged plugin lists are deduplicated by slug, preferring directory matches.
	 */
	public function test_merge_existing_plugins_dedupes_by_slug() {
		$directory = array(
			array(
				'name'           => 'LessFlux Image & Video Optimizer',
				'slug'           => 'less-flux',
				'link'           => 'https://wordpress.org/plugins/less-flux/',
				'is_exact_match' => true,
			),
		);
		$ai        = array(
			array(
				'name' => 'LessFlux',
				'link' => 'https://wordpress.org/plugins/less-flux/',
			),
			array(
				'name' => 'Other Plugin',
				'link' => 'https://wordpress.org/plugins/other-plugin/',
			),
			array(
				'name' => 'Other Plugin',
			),
		);

		$merged = $this->merge_existing_plugins( $directory, $ai );

		$this->assertCount( 2, $merged );
		$this->assertTrue( $merged[0]['is_exact_match'] );
		$this->assertSame( 'Other Plugin', $merged[1]['name'] );
	}
}
