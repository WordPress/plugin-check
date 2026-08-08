<?php
/**
 * Checker class.
 *
 * @package Plugin_Check
 */

namespace WordPress\Plugin_Check\SVN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Checker.
 *
 * Fetches data from a WordPress.org plugin SVN repo and builds
 * structured report data.
 *
 * @since 2.1.0
 */
class Checker {

	const WP_SVN_BASE = 'https://plugins.svn.wordpress.org/';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Trailing-slash base URL for this plugin's SVN repo.
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Fetcher instance.
	 *
	 * @var Fetcher
	 */
	private Fetcher $fetcher;

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 *
	 * @param string $input Plugin slug.
	 */
	public function __construct( string $input ) {
		$this->slug     = sanitize_title( trim( $input ) );
		$this->base_url = self::WP_SVN_BASE . $this->slug . '/';
		$this->fetcher  = new Fetcher( $this->base_url );
	}

	/**
	 * Run all checks and return the report data.
	 *
	 * Each remote directory is fetched exactly once; results are reused
	 * for both existence checks and content analysis.
	 *
	 * @since 2.1.0
	 *
	 * @return array{slug: string, meta: array<string, mixed>, sections: Section[]}
	 */
	public function run(): array {
		// Attempt trunk/readme.txt, then README.txt, readme.md, README.md.
		$readme_resp = $this->fetcher->fetch_raw( 'trunk/readme.txt' );
		if ( 200 !== $readme_resp['code'] ) {
			$readme_resp = $this->fetcher->fetch_raw( 'trunk/README.txt' );
		}
		if ( 200 !== $readme_resp['code'] ) {
			$readme_resp = $this->fetcher->fetch_raw( 'trunk/readme.md' );
		}
		if ( 200 !== $readme_resp['code'] ) {
			$readme_resp = $this->fetcher->fetch_raw( 'trunk/README.md' );
		}
		$readme_ok   = 200 === $readme_resp['code'];
		$readme_data = $readme_ok ? $this->fetcher->parse_readme( $readme_resp['body'] ) : array();

		// Fetch trunk/ listing for existence check and PHP file discovery (one request).
		$trunk_dir    = $this->fetcher->fetch_directory( 'trunk/' );
		$trunk_exists = $trunk_dir['exists'] || $readme_ok;

		// Bail early if the slug doesn't resolve to a valid SVN repo.
		if ( ! $trunk_exists ) {
			return array(
				'slug'     => $this->slug,
				'meta'     => array(
					'error'   => 'not_found',
					'svn_url' => $this->base_url,
				),
				'sections' => array(),
			);
		}

		// Find main PHP file from the listing; returns content to avoid a second fetch.
		[ 'file' => $plugin_file, 'content' => $php_content ] =
			$this->find_main_plugin_file_with_content( $trunk_dir['items'] );
		$php_data = ! empty( $php_content )
			? $this->fetcher->parse_plugin_headers( $php_content )
			: array();

		// Check tags/ existence only — no need to parse the full listing.
		$tags_exists = 200 === $this->fetcher->fetch_raw( 'tags/' )['code'];

		// Fetch assets/ listing for existence check and asset file scan (one request).
		$assets_dir    = $this->fetcher->fetch_directory( 'assets/' );
		$assets_exists = $assets_dir['exists'];

		$stable_tag    = $readme_data['stable_tag'] ?? null;
		$trunk_version = $php_data['Version'] ?? null;

		$meta = array(
			'plugin_name'   => $readme_data['name'] ?? null,
			'plugin_file'   => $plugin_file,
			'stable_tag'    => $stable_tag,
			'trunk_version' => $trunk_version,
			'requires_php'  => $readme_data['requires_php'] ?? null,
			'tested_up_to'  => $readme_data['tested_up_to'] ?? null,
			'svn_url'       => $this->base_url,
		);

		$sections = array();

		// Section: root (no additional requests).
		$root_section = new Section( 'root', __( 'Main SVN Folder', 'plugin-check' ) );
		$root_section->add_check( 'root_trunk_exists', __( 'trunk/ exists', 'plugin-check' ), 'pass', __( 'Found', 'plugin-check' ) );
		$root_section->add_check( 'root_tags_exists', __( 'tags/ exists', 'plugin-check' ), $tags_exists ? 'pass' : 'fail', $tags_exists ? __( 'Found', 'plugin-check' ) : __( 'Missing', 'plugin-check' ) );
		$root_section->add_check( 'root_assets_exists', __( 'assets/ exists', 'plugin-check' ), $assets_exists ? 'pass' : 'warn', $assets_exists ? __( 'Found', 'plugin-check' ) : __( 'Missing — optional but recommended', 'plugin-check' ) );
		$sections[] = $root_section;

		// Section: trunk (no additional requests).
		$trunk_section = new Section( 'trunk', __( 'Trunk', 'plugin-check' ) );

		$trunk_section->add_check(
			'trunk_readme_found',
			__( 'readme.txt found', 'plugin-check' ),
			$readme_ok ? 'pass' : 'fail',
			$readme_ok
				? 'trunk/readme.txt'
				/* translators: %s: file path. */
				: sprintf( __( '%s is missing', 'plugin-check' ), 'trunk/readme.txt' )
		);

		$trunk_section->add_check(
			'trunk_stable_tag_declared',
			__( 'Stable tag declared', 'plugin-check' ),
			! empty( $stable_tag ) ? 'pass' : 'fail',
			! empty( $stable_tag )
				? $stable_tag
				/* translators: %s: file path. */
				: sprintf( __( '"Stable tag:" not found in %s', 'plugin-check' ), 'trunk/readme.txt' )
		);

		$trunk_section->add_check(
			'trunk_main_php_file_found',
			__( 'Main plugin PHP file found', 'plugin-check' ),
			! empty( $plugin_file ) ? 'pass' : 'warn',
			! empty( $plugin_file ) ? "trunk/{$plugin_file}" : __( 'No PHP file with "Plugin Name:" header found in trunk/', 'plugin-check' )
		);

		$trunk_section->add_check(
			'trunk_version_declared',
			__( 'Version declared in PHP header', 'plugin-check' ),
			! empty( $trunk_version ) ? 'pass' : ( $plugin_file ? 'fail' : 'warn' ),
			! empty( $trunk_version )
				? $trunk_version
				: (
					$plugin_file
						/* translators: %s: file name. */
						? sprintf( __( 'No "Version:" header in trunk/%s', 'plugin-check' ), $plugin_file )
						: __( 'Skipped — plugin PHP file not found', 'plugin-check' )
				)
		);

		if ( ! empty( $stable_tag ) && ! empty( $trunk_version ) ) {
			$match = ( $stable_tag === $trunk_version );
			$trunk_section->add_check(
				'trunk_stable_tag_matches_version',
				__( 'Stable tag matches PHP version', 'plugin-check' ),
				$match ? 'pass' : 'fail',
				$match ? "{$stable_tag} === {$trunk_version}" : "readme={$stable_tag}, php={$trunk_version}"
			);
		} else {
			$trunk_section->add_check( 'trunk_stable_tag_matches_version', __( 'Stable tag matches PHP version', 'plugin-check' ), 'warn', __( 'Cannot compare — one or both values missing', 'plugin-check' ) );
		}

		$sections[] = $trunk_section;

		// Section: stable_tag.
		if ( $stable_tag ) {
			$stable_tag_section = new Section( 'stable_tag', "tags/{$stable_tag}/" );

			$tag_exists = 200 === $this->fetcher->fetch_raw( "tags/{$stable_tag}/" )['code'];
			$stable_tag_section->add_check(
				'stable_tag_dir_exists',
				/* translators: %s: SVN tag directory path. */
				sprintf( __( '%s exists', 'plugin-check' ), "tags/{$stable_tag}/" ),
				$tag_exists ? 'pass' : 'fail',
				$tag_exists
					? __( 'Found', 'plugin-check' )
					/* translators: %s: SVN tag directory path. */
					: sprintf( __( '%s not found', 'plugin-check' ), "tags/{$stable_tag}/" )
			);

			if ( $tag_exists && $plugin_file ) {
				$this->add_stable_tag_checks( $stable_tag_section, $stable_tag, $plugin_file, $stable_tag, $trunk_version );
			}

			$sections[] = $stable_tag_section;
		}

		// Section: assets (pass already-fetched listing).
		$assets_section = new Section( 'assets', __( 'Assets', 'plugin-check' ) );
		$this->add_assets_checks( $assets_section, $assets_dir['items'] );
		$sections[] = $assets_section;

		return array(
			'slug'     => $this->slug,
			'meta'     => $meta,
			'sections' => $sections,
		);
	}

	/**
	 * Add checks for the tags/{stable}/ folder.
	 *
	 * @since 2.1.0
	 *
	 * @param Section     $section       Section to add checks to.
	 * @param string      $tag           Stable tag version string.
	 * @param string      $plugin_file   Main plugin PHP filename.
	 * @param string|null $trunk_stable  Stable tag from trunk/readme.txt.
	 * @param string|null $trunk_version Version from trunk PHP header.
	 */
	private function add_stable_tag_checks(
		Section $section,
		string $tag,
		string $plugin_file,
		?string $trunk_stable,
		?string $trunk_version
	): void {
		// Attempt readme.txt, then README.txt, readme.md, README.md in tag.
		$readme_resp = $this->fetcher->fetch_raw( "tags/{$tag}/readme.txt" );
		if ( 200 !== $readme_resp['code'] ) {
			$readme_resp = $this->fetcher->fetch_raw( "tags/{$tag}/README.txt" );
		}
		if ( 200 !== $readme_resp['code'] ) {
			$readme_resp = $this->fetcher->fetch_raw( "tags/{$tag}/readme.md" );
		}
		if ( 200 !== $readme_resp['code'] ) {
			$readme_resp = $this->fetcher->fetch_raw( "tags/{$tag}/README.md" );
		}
		$readme_ok = 200 === $readme_resp['code'];

		$section->add_check(
			'tag_readme_found',
			__( 'readme.txt found', 'plugin-check' ),
			$readme_ok ? 'pass' : 'fail',
			$readme_ok
				? "tags/{$tag}/readme.txt"
				/* translators: %s: file path. */
				: sprintf( __( '%s is missing', 'plugin-check' ), "tags/{$tag}/readme.txt" )
		);

		if ( $readme_ok ) {
			$readme_data = $this->fetcher->parse_readme( $readme_resp['body'] );
			$tag_stable  = $readme_data['stable_tag'] ?? null;

			$section->add_check(
				'tag_stable_tag_declared',
				__( 'Stable tag declared', 'plugin-check' ),
				! empty( $tag_stable ) ? 'pass' : 'fail',
				! empty( $tag_stable ) ? $tag_stable : __( '"Stable tag:" not found', 'plugin-check' )
			);

			if ( $trunk_stable && $tag_stable ) {
				$match = ( $tag_stable === $trunk_stable );
				$section->add_check(
					'tag_readme_stable_tag_matches_trunk',
					__( 'readme stable tag matches trunk', 'plugin-check' ),
					$match ? 'pass' : 'fail',
					"{$tag_stable} === {$trunk_stable}"
				);
			}
		}

		// Plugin PHP file in tag.
		$php_resp = $this->fetcher->fetch_raw( "tags/{$tag}/{$plugin_file}" );
		$php_ok   = 200 === $php_resp['code'];

		$section->add_check(
			'tag_main_php_file_found',
			__( 'Main PHP file found', 'plugin-check' ),
			$php_ok ? 'pass' : 'fail',
			$php_ok
				? "tags/{$tag}/{$plugin_file}"
				/* translators: %s: file path. */
				: sprintf( __( '%s is missing', 'plugin-check' ), "tags/{$tag}/{$plugin_file}" )
		);

		if ( $php_ok ) {
			$php_data    = $this->fetcher->parse_plugin_headers( $php_resp['body'] );
			$php_version = $php_data['Version'] ?? null;

			$section->add_check(
				'tag_version_declared',
				__( 'Version declared', 'plugin-check' ),
				! empty( $php_version ) ? 'pass' : 'fail',
				! empty( $php_version ) ? $php_version : __( '"Version:" header not found', 'plugin-check' )
			);

			if ( $trunk_version && $php_version ) {
				$match = ( $php_version === $trunk_version );
				$section->add_check(
					'tag_php_version_matches_trunk',
					__( 'PHP version matches trunk', 'plugin-check' ),
					$match ? 'pass' : 'fail',
					"{$php_version} === {$trunk_version}"
				);
			}
		}
	}

	/**
	 * Add asset presence checks (banner, icon).
	 *
	 * @since 2.1.0
	 *
	 * @param Section                                                     $section Section to add checks to.
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $items   Pre-fetched assets/ listing.
	 */
	private function add_assets_checks( Section $section, array $items ): void {
		$filenames = array_column( $items, 'name' );

		$banner_file = null;
		$icon_file   = null;

		foreach ( $filenames as $name ) {
			if ( ! $banner_file && preg_match( '/^banner-\d+x\d+\.(png|jpg)$/i', $name ) ) {
				$banner_file = $name;
			}
			if ( ! $icon_file && preg_match( '/^icon(-\d+x\d+\.(png|jpg)|\.svg)$/i', $name ) ) {
				$icon_file = $name;
			}
		}

		$section->add_check(
			'assets_banner_present',
			__( 'Banner image present', 'plugin-check' ),
			$banner_file ? 'pass' : 'info',
			/* translators: %s: file name pattern. */
			$banner_file ?? sprintf( __( '%s not found — optional', 'plugin-check' ), 'banner-772x250.(png|jpg)' )
		);

		$section->add_check(
			'assets_icon_present',
			__( 'Icon image present', 'plugin-check' ),
			$icon_file ? 'pass' : 'info',
			/* translators: %s: file name pattern. */
			$icon_file ?? sprintf( __( '%s not found — optional', 'plugin-check' ), 'icon-128x128.(png|jpg) or icon.svg' )
		);
	}

	/**
	 * Find the main plugin PHP file from a pre-fetched trunk/ listing.
	 *
	 * Uses the directory listing so no extra HTTP request is needed.
	 * Prioritises {slug}.php (WP.org convention) but works for any filename.
	 * Returns both the filename and its content so the caller never re-fetches.
	 *
	 * @since 2.1.0
	 *
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $trunk_items Pre-fetched trunk/ items.
	 * @return array{file: string|null, content: string}
	 */
	private function find_main_plugin_file_with_content( array $trunk_items ): array {
		$skip     = array( 'uninstall.php' );
		$slug_php = $this->slug . '.php';
		$first    = array();
		$rest     = array();

		foreach ( $trunk_items as $item ) {
			if ( $item['is_dir'] ) {
				continue;
			}

			$name = $item['name'];

			if ( 'php' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
				continue;
			}

			if ( in_array( $name, $skip, true ) ) {
				continue;
			}

			// Keep slug.php as first candidate (convention), everything else after.
			if ( $name === $slug_php ) {
				$first[] = $name;
			} else {
				$rest[] = $name;
			}
		}

		foreach ( array_merge( $first, $rest ) as $name ) {
			$r = $this->fetcher->fetch_raw( "trunk/{$name}" );
			if ( 200 === $r['code'] && false !== strpos( $r['body'], 'Plugin Name:' ) ) {
				return array(
					'file'    => $name,
					'content' => $r['body'],
				);
			}
		}

		return array(
			'file'    => null,
			'content' => '',
		);
	}
}
