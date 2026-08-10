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
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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
		$readme_resp = $this->fetch_readme_fallback( 'trunk/' );
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

		// Fetch the SVN base directory listing for the unexpected-files check.
		$root_dir = $this->fetcher->fetch_directory( '' );

		// Find main PHP file from the listing; returns content to avoid a second fetch.
		[ 'file' => $plugin_file, 'content' => $php_content ] =
			$this->find_main_plugin_file_with_content( $trunk_dir['items'] );
		$php_data = ! empty( $php_content )
			? $this->fetcher->parse_plugin_headers( $php_content )
			: array();

		// Fetch tags/ listing for existence check and unexpected-files scan (one request).
		$tags_dir    = $this->fetcher->fetch_directory( 'tags/' );
		$tags_exists = $tags_dir['exists'];

		// Fetch assets/ listing for existence check and asset file scan (one request).
		$assets_dir    = $this->fetcher->fetch_directory( 'assets/' );
		$assets_exists = $assets_dir['exists'];

		$stable_tag    = $readme_data['stable_tag'] ?? null;
		$trunk_version = $php_data['Version'] ?? null;

		$meta = $this->build_meta( $readme_data, $plugin_file, $stable_tag, $trunk_version );

		$sections   = array();
		$sections[] = $this->build_root_section( $root_dir['items'], $tags_dir['items'], $tags_exists, $assets_exists );
		$sections[] = $this->build_trunk_section( $readme_ok, $stable_tag, $plugin_file, $trunk_version, $trunk_dir['items'] );

		$stable_tag_section = $this->build_stable_tag_section( $stable_tag, $plugin_file, $trunk_version );
		if ( $stable_tag_section ) {
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
	 * Fetch a readme file, trying txt then md, uppercase and lowercase.
	 *
	 * @since 2.1.0
	 *
	 * @param string $prefix Path prefix to prepend to the readme filename (e.g. 'trunk/').
	 * @return array{code: int, body: string}
	 */
	private function fetch_readme_fallback( string $prefix ): array {
		$readme_resp = $this->fetcher->fetch_raw( "{$prefix}readme.txt" );
		if ( 200 !== $readme_resp['code'] ) {
			$readme_resp = $this->fetcher->fetch_raw( "{$prefix}README.txt" );
		}
		if ( 200 !== $readme_resp['code'] ) {
			$readme_resp = $this->fetcher->fetch_raw( "{$prefix}readme.md" );
		}
		if ( 200 !== $readme_resp['code'] ) {
			$readme_resp = $this->fetcher->fetch_raw( "{$prefix}README.md" );
		}

		return $readme_resp;
	}

	/**
	 * Build the report meta array.
	 *
	 * @since 2.1.0
	 *
	 * @param array<string, mixed> $readme_data   Parsed trunk readme data.
	 * @param string|null          $plugin_file   Main plugin PHP filename.
	 * @param string|null          $stable_tag    Stable tag from trunk/readme.txt.
	 * @param string|null          $trunk_version Version from trunk PHP header.
	 * @return array<string, mixed>
	 */
	private function build_meta( array $readme_data, ?string $plugin_file, ?string $stable_tag, ?string $trunk_version ): array {
		return array(
			'plugin_name'   => $readme_data['name'] ?? null,
			'plugin_file'   => $plugin_file,
			'stable_tag'    => $stable_tag,
			'trunk_version' => $trunk_version,
			'requires_php'  => $readme_data['requires_php'] ?? null,
			'tested_up_to'  => $readme_data['tested_up_to'] ?? null,
			'svn_url'       => $this->base_url,
		);
	}

	/**
	 * Build the root section (no additional requests).
	 *
	 * @since 2.1.0
	 *
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $root_items    Pre-fetched SVN root listing.
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $tags_items    Pre-fetched tags/ listing.
	 * @param bool                                                        $tags_exists   Whether tags/ exists.
	 * @param bool                                                        $assets_exists Whether assets/ exists.
	 * @return Section
	 */
	private function build_root_section( array $root_items, array $tags_items, bool $tags_exists, bool $assets_exists ): Section {
		$root_section = new Section( 'root', __( 'Root', 'plugin-check' ) );
		$root_section->add_check( 'root_trunk_exists', __( 'trunk/ exists', 'plugin-check' ), 'pass', __( 'Found', 'plugin-check' ) );
		$root_section->add_check( 'root_tags_exists', __( 'tags/ exists', 'plugin-check' ), $tags_exists ? 'pass' : 'fail', $tags_exists ? __( 'Found', 'plugin-check' ) : __( 'Missing', 'plugin-check' ) );
		$root_section->add_check( 'root_assets_exists', __( 'assets/ exists', 'plugin-check' ), $assets_exists ? 'pass' : 'warn', $assets_exists ? __( 'Found', 'plugin-check' ) : __( 'Missing — optional but recommended', 'plugin-check' ) );

		$this->add_root_unexpected_files_check( $root_section, $root_items );
		$this->add_root_tags_unexpected_files_check( $root_section, $tags_items );

		return $root_section;
	}

	/**
	 * Add the root_unexpected_files check.
	 *
	 * @since 2.1.0
	 *
	 * @param Section                                                     $section    Section to add the check to.
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $root_items Pre-fetched SVN root listing.
	 */
	private function add_root_unexpected_files_check( Section $section, array $root_items ): void {
		$allowed    = array( 'assets', 'branches', 'tags', 'trunk' );
		$unexpected = array();

		foreach ( $root_items as $item ) {
			if ( ! in_array( $item['name'], $allowed, true ) ) {
				$unexpected[] = $item['name'];
			}
		}

		$section->add_check(
			'root_unexpected_files',
			__( 'No unexpected files in SVN root', 'plugin-check' ),
			empty( $unexpected ) ? 'pass' : 'fail',
			empty( $unexpected )
				? __( 'None found', 'plugin-check' )
				/* translators: %s: comma-separated list of file/directory names. */
				: sprintf( __( 'Unexpected: %s', 'plugin-check' ), implode( ', ', $unexpected ) )
		);
	}

	/**
	 * Add the root_tags_unexpected_files check.
	 *
	 * @since 2.1.0
	 *
	 * @param Section                                                     $section    Section to add the check to.
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $tags_items Pre-fetched tags/ listing.
	 */
	private function add_root_tags_unexpected_files_check( Section $section, array $tags_items ): void {
		$unexpected = array();

		foreach ( $tags_items as $item ) {
			if ( ! $item['is_dir'] ) {
				$unexpected[] = $item['name'];
			}
		}

		$section->add_check(
			'root_tags_unexpected_files',
			__( 'No unexpected files in tags/', 'plugin-check' ),
			empty( $unexpected ) ? 'pass' : 'fail',
			empty( $unexpected )
				? __( 'None found', 'plugin-check' )
				/* translators: %s: comma-separated list of file names. */
				: sprintf( __( 'Unexpected in tags/: %s', 'plugin-check' ), implode( ', ', $unexpected ) )
		);
	}

	/**
	 * Build the trunk section (no additional requests).
	 *
	 * @since 2.1.0
	 *
	 * @param bool                                                        $readme_ok     Whether a trunk readme was found.
	 * @param string|null                                                 $stable_tag    Stable tag from trunk/readme.txt.
	 * @param string|null                                                 $plugin_file   Main plugin PHP filename.
	 * @param string|null                                                 $trunk_version Version from trunk PHP header.
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $trunk_items   Pre-fetched trunk/ listing.
	 * @return Section
	 */
	private function build_trunk_section( bool $readme_ok, ?string $stable_tag, ?string $plugin_file, ?string $trunk_version, array $trunk_items ): Section {
		$trunk_section = new Section( 'trunk', __( 'Trunk', 'plugin-check' ) );

		$this->add_trunk_readme_check( $trunk_section, $readme_ok );
		$this->add_trunk_stable_tag_check( $trunk_section, $stable_tag );
		$this->add_trunk_main_php_check( $trunk_section, $plugin_file );
		$this->add_trunk_version_check( $trunk_section, $trunk_version, $plugin_file );
		$this->add_trunk_version_match_check( $trunk_section, $stable_tag, $trunk_version );
		$this->add_trunk_unexpected_files_check( $trunk_section, $trunk_items );

		return $trunk_section;
	}

	/**
	 * Add the trunk_unexpected_files check.
	 *
	 * @since 2.1.0
	 *
	 * @param Section                                                     $section     Section to add the check to.
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $trunk_items Pre-fetched trunk/ listing.
	 */
	private function add_trunk_unexpected_files_check( Section $section, array $trunk_items ): void {
		$unexpected = $this->find_files_with_extensions( $trunk_items, array( 'zip' ) );

		$section->add_check(
			'trunk_unexpected_files',
			__( 'No unexpected files in trunk/', 'plugin-check' ),
			empty( $unexpected ) ? 'pass' : 'fail',
			empty( $unexpected )
				? __( 'None found', 'plugin-check' )
				/* translators: %s: comma-separated list of file names. */
				: sprintf( __( 'Unexpected in trunk/: %s', 'plugin-check' ), implode( ', ', $unexpected ) )
		);
	}

	/**
	 * Add the trunk_readme_found check.
	 *
	 * @since 2.1.0
	 *
	 * @param Section $section    Section to add the check to.
	 * @param bool    $readme_ok  Whether a trunk readme was found.
	 */
	private function add_trunk_readme_check( Section $section, bool $readme_ok ): void {
		$section->add_check(
			'trunk_readme_found',
			__( 'readme.txt found', 'plugin-check' ),
			$readme_ok ? 'pass' : 'fail',
			$readme_ok
				? 'trunk/readme.txt'
				/* translators: %s: file path. */
				: sprintf( __( '%s is missing', 'plugin-check' ), 'trunk/readme.txt' )
		);
	}

	/**
	 * Add the trunk_stable_tag_declared check.
	 *
	 * @since 2.1.0
	 *
	 * @param Section     $section    Section to add the check to.
	 * @param string|null $stable_tag Stable tag from trunk/readme.txt.
	 */
	private function add_trunk_stable_tag_check( Section $section, ?string $stable_tag ): void {
		$section->add_check(
			'trunk_stable_tag_declared',
			__( 'Stable tag declared', 'plugin-check' ),
			! empty( $stable_tag ) ? 'pass' : 'fail',
			! empty( $stable_tag )
				? $stable_tag
				/* translators: %s: file path. */
				: sprintf( __( '"Stable tag:" not found in %s', 'plugin-check' ), 'trunk/readme.txt' )
		);
	}

	/**
	 * Add the trunk_main_php_file_found check.
	 *
	 * @since 2.1.0
	 *
	 * @param Section     $section     Section to add the check to.
	 * @param string|null $plugin_file Main plugin PHP filename.
	 */
	private function add_trunk_main_php_check( Section $section, ?string $plugin_file ): void {
		$section->add_check(
			'trunk_main_php_file_found',
			__( 'Main plugin PHP file found', 'plugin-check' ),
			! empty( $plugin_file ) ? 'pass' : 'warn',
			! empty( $plugin_file ) ? "trunk/{$plugin_file}" : __( 'No PHP file with "Plugin Name:" header found in trunk/', 'plugin-check' )
		);
	}

	/**
	 * Add the trunk_version_declared check.
	 *
	 * @since 2.1.0
	 *
	 * @param Section     $section       Section to add the check to.
	 * @param string|null $trunk_version Version from trunk PHP header.
	 * @param string|null $plugin_file   Main plugin PHP filename.
	 */
	private function add_trunk_version_check( Section $section, ?string $trunk_version, ?string $plugin_file ): void {
		if ( ! empty( $trunk_version ) ) {
			$section->add_check( 'trunk_version_declared', __( 'Version declared in PHP header', 'plugin-check' ), 'pass', $trunk_version );
			return;
		}

		if ( $plugin_file ) {
			/* translators: %s: file name. */
			$message = sprintf( __( 'No "Version:" header in trunk/%s', 'plugin-check' ), $plugin_file );
			$section->add_check( 'trunk_version_declared', __( 'Version declared in PHP header', 'plugin-check' ), 'fail', $message );
			return;
		}

		$section->add_check( 'trunk_version_declared', __( 'Version declared in PHP header', 'plugin-check' ), 'warn', __( 'Skipped — plugin PHP file not found', 'plugin-check' ) );
	}

	/**
	 * Add the trunk_stable_tag_matches_version check.
	 *
	 * @since 2.1.0
	 *
	 * @param Section     $section       Section to add the check to.
	 * @param string|null $stable_tag    Stable tag from trunk/readme.txt.
	 * @param string|null $trunk_version Version from trunk PHP header.
	 */
	private function add_trunk_version_match_check( Section $section, ?string $stable_tag, ?string $trunk_version ): void {
		if ( empty( $stable_tag ) || empty( $trunk_version ) ) {
			$section->add_check( 'trunk_stable_tag_matches_version', __( 'Stable tag matches PHP version', 'plugin-check' ), 'warn', __( 'Cannot compare — one or both values missing', 'plugin-check' ) );
			return;
		}

		$match = ( $stable_tag === $trunk_version );
		$section->add_check(
			'trunk_stable_tag_matches_version',
			__( 'Stable tag matches PHP version', 'plugin-check' ),
			$match ? 'pass' : 'fail',
			$match ? "{$stable_tag} === {$trunk_version}" : "readme={$stable_tag}, php={$trunk_version}"
		);
	}

	/**
	 * Build the stable_tag section, or null if there is no stable tag to check.
	 *
	 * @since 2.1.0
	 *
	 * @param string|null $stable_tag    Stable tag from trunk/readme.txt.
	 * @param string|null $plugin_file   Main plugin PHP filename.
	 * @param string|null $trunk_version Version from trunk PHP header.
	 * @return Section|null
	 */
	private function build_stable_tag_section( ?string $stable_tag, ?string $plugin_file, ?string $trunk_version ): ?Section {
		if ( ! $stable_tag ) {
			return null;
		}

		$stable_tag_section = new Section( 'stable_tag', "tags/{$stable_tag}/" );

		$tag_dir    = $this->fetcher->fetch_directory( "tags/{$stable_tag}/" );
		$tag_exists = $tag_dir['exists'];
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

		if ( $tag_exists ) {
			$this->add_tag_unexpected_files_check( $stable_tag_section, $tag_dir['items'] );

			if ( $plugin_file ) {
				$this->add_tag_readme_checks( $stable_tag_section, $stable_tag, $stable_tag );
				$this->add_tag_php_checks( $stable_tag_section, $stable_tag, $plugin_file, $trunk_version );
			}
		}

		return $stable_tag_section;
	}

	/**
	 * Add the tag_unexpected_files check.
	 *
	 * @since 2.1.0
	 *
	 * @param Section                                                     $section   Section to add the check to.
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $tag_items Pre-fetched tags/{tag}/ listing.
	 */
	private function add_tag_unexpected_files_check( Section $section, array $tag_items ): void {
		$unexpected = $this->find_files_with_extensions( $tag_items, array( 'zip' ) );

		$section->add_check(
			'tag_unexpected_files',
			__( 'No unexpected files', 'plugin-check' ),
			empty( $unexpected ) ? 'pass' : 'fail',
			empty( $unexpected )
				? __( 'None found', 'plugin-check' )
				/* translators: %s: comma-separated list of file names. */
				: sprintf( __( 'Unexpected: %s', 'plugin-check' ), implode( ', ', $unexpected ) )
		);
	}

	/**
	 * Add readme-related checks for the tags/{tag}/ folder.
	 *
	 * @since 2.1.0
	 *
	 * @param Section     $section      Section to add checks to.
	 * @param string      $tag          Stable tag version string.
	 * @param string|null $trunk_stable Stable tag from trunk/readme.txt.
	 */
	private function add_tag_readme_checks( Section $section, string $tag, ?string $trunk_stable ): void {
		$readme_resp = $this->fetch_readme_fallback( "tags/{$tag}/" );
		$readme_ok   = 200 === $readme_resp['code'];

		$section->add_check(
			'tag_readme_found',
			__( 'readme.txt found', 'plugin-check' ),
			$readme_ok ? 'pass' : 'fail',
			$readme_ok
				? "tags/{$tag}/readme.txt"
				/* translators: %s: file path. */
				: sprintf( __( '%s is missing', 'plugin-check' ), "tags/{$tag}/readme.txt" )
		);

		if ( ! $readme_ok ) {
			return;
		}

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

	/**
	 * Add PHP-header-related checks for the tags/{tag}/ folder.
	 *
	 * @since 2.1.0
	 *
	 * @param Section     $section       Section to add checks to.
	 * @param string      $tag           Stable tag version string.
	 * @param string      $plugin_file   Main plugin PHP filename.
	 * @param string|null $trunk_version Version from trunk PHP header.
	 */
	private function add_tag_php_checks( Section $section, string $tag, string $plugin_file, ?string $trunk_version ): void {
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

		if ( ! $php_ok ) {
			return;
		}

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

		$this->add_assets_unexpected_files_check( $section, $items );
		$this->add_assets_blueprint_check( $section, $items );
	}

	/**
	 * Add the assets_unexpected_files check.
	 *
	 * Allowed: image files (jpg, png, svg, gif) and the optional blueprints/ folder.
	 *
	 * @since 2.1.0
	 *
	 * @param Section                                                     $section Section to add the check to.
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $items   Pre-fetched assets/ listing.
	 */
	private function add_assets_unexpected_files_check( Section $section, array $items ): void {
		$allowed_extensions = array( 'jpg', 'png', 'svg', 'gif' );
		$unexpected         = array();

		foreach ( $items as $item ) {
			if ( $item['is_dir'] ) {
				if ( 'blueprints' !== strtolower( $item['name'] ) ) {
					$unexpected[] = $item['name'];
				}
				continue;
			}

			$extension = strtolower( pathinfo( $item['name'], PATHINFO_EXTENSION ) );
			if ( ! in_array( $extension, $allowed_extensions, true ) ) {
				$unexpected[] = $item['name'];
			}
		}

		$section->add_check(
			'assets_unexpected_files',
			__( 'No unexpected files', 'plugin-check' ),
			empty( $unexpected ) ? 'pass' : 'fail',
			empty( $unexpected )
				? __( 'None found', 'plugin-check' )
				/* translators: %s: comma-separated list of file/directory names. */
				: sprintf( __( 'Unexpected: %s', 'plugin-check' ), implode( ', ', $unexpected ) )
		);
	}

	/**
	 * Add the assets_blueprint_present check, if a blueprints/ folder exists.
	 *
	 * @since 2.1.0
	 *
	 * @param Section                                                     $section Section to add the check to.
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $items   Pre-fetched assets/ listing.
	 */
	private function add_assets_blueprint_check( Section $section, array $items ): void {
		$has_blueprints_dir = false;
		foreach ( $items as $item ) {
			if ( $item['is_dir'] && 'blueprints' === strtolower( $item['name'] ) ) {
				$has_blueprints_dir = true;
				break;
			}
		}

		if ( ! $has_blueprints_dir ) {
			return;
		}

		$blueprint_ok = 200 === $this->fetcher->fetch_raw( 'assets/blueprints/blueprint.json' )['code'];

		$section->add_check(
			'assets_blueprint_present',
			__( 'Blueprint file present', 'plugin-check' ),
			$blueprint_ok ? 'pass' : 'info',
			$blueprint_ok
				? 'assets/blueprints/blueprint.json'
				/* translators: %s: file path. */
				: sprintf( __( '%s not found — optional, needed for "Live Preview"', 'plugin-check' ), 'assets/blueprints/blueprint.json' )
		);
	}

	/**
	 * Find files (not directories) in a listing matching one of the given extensions.
	 *
	 * @since 2.1.0
	 *
	 * @param array<int, array{name: string, href: string, is_dir: bool}> $items      Directory listing items.
	 * @param array<int, string>                                          $extensions Extensions to match (lowercase, no dot).
	 * @return array<int, string> Matching file names.
	 */
	private function find_files_with_extensions( array $items, array $extensions ): array {
		$matches = array();

		foreach ( $items as $item ) {
			if ( $item['is_dir'] ) {
				continue;
			}

			$extension = strtolower( pathinfo( $item['name'], PATHINFO_EXTENSION ) );
			if ( in_array( $extension, $extensions, true ) ) {
				$matches[] = $item['name'];
			}
		}

		return $matches;
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
