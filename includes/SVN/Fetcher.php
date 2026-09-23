<?php
/**
 * Fetcher class.
 *
 * @package Plugin_Check
 */

namespace WordPress\Plugin_Check\SVN;

use DOMDocument;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Fetcher.
 *
 * HTTP fetch helpers and file parsers for a WordPress.org plugin SVN repo.
 *
 * @since 2.1.0
 */
class Fetcher {

	/**
	 * Trailing-slash base URL of the plugin SVN repo.
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 *
	 * @param string $base_url Trailing-slash base URL.
	 */
	public function __construct( string $base_url ) {
		$this->base_url = $base_url;
	}

	/**
	 * Fetch a file by relative path. Returns HTTP code and body.
	 *
	 * @since 2.1.0
	 *
	 * @param string $relative_path Path relative to base URL.
	 * @return array{code: int, body: string}
	 */
	public function fetch_raw( string $relative_path ): array {
		$url      = $this->base_url . ltrim( $relative_path, '/' );
		$response = wp_remote_get( $url, array( 'timeout' => 20 ) );

		if ( is_wp_error( $response ) ) {
			return array(
				'code' => 0,
				'body' => '',
			);
		}

		return array(
			'code' => (int) wp_remote_retrieve_response_code( $response ),
			'body' => wp_remote_retrieve_body( $response ),
		);
	}

	/**
	 * Fetch a directory and return both existence flag and parsed items.
	 *
	 * @since 2.1.0
	 *
	 * @param string $relative_path Directory path relative to base URL.
	 * @return array{exists: bool, items: array<int, array{name: string, href: string, is_dir: bool}>}
	 */
	public function fetch_directory( string $relative_path ): array {
		$r = $this->fetch_raw( $relative_path );

		return array(
			'exists' => 200 === $r['code'],
			'items'  => 200 === $r['code'] ? $this->parse_html_links( $r['body'] ) : array(),
		);
	}

	/**
	 * Fetch and parse an SVN HTTP directory listing.
	 *
	 * @since 2.1.0
	 *
	 * @param string $relative_path Directory path relative to base URL.
	 * @return array<int, array{name: string, href: string, is_dir: bool}>
	 */
	public function fetch_directory_items( string $relative_path ): array {
		return $this->fetch_directory( $relative_path )['items'];
	}

	/**
	 * Parse <a> links from an SVN HTML directory listing.
	 *
	 * @since 2.1.0
	 *
	 * @param string $html Raw HTML body.
	 * @return array<int, array{name: string, href: string, is_dir: bool}>
	 */
	private function parse_html_links( string $html ): array {
		libxml_use_internal_errors( true );
		$doc = new DOMDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();

		$items = array();
		$seen  = array();

		foreach ( $doc->getElementsByTagName( 'a' ) as $link ) {
			$href = $link->getAttribute( 'href' );
			$text = trim( $link->textContent ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if (
				empty( $href )
				|| '../' === $href
				|| '#' === $href[0]
				|| '?' === $href[0]
				|| false !== strpos( $href, '://' )
				|| isset( $seen[ $href ] )
			) {
				continue;
			}

			$seen[ $href ] = true;
			$items[]       = array(
				'name'   => rtrim( $text ? $text : $href, '/' ),
				'href'   => $href,
				'is_dir' => '/' === substr( $href, -1 ),
			);
		}

		return $items;
	}

	/**
	 * Parse readme.txt content into a metadata array.
	 *
	 * @since 2.1.0
	 *
	 * @param string $content Raw readme.txt content.
	 * @return array<string, string>
	 */
	public function parse_readme( string $content ): array {
		$data   = array();
		$fields = array(
			'stable_tag'        => '/^Stable tag:\s*(.+)$/im',
			'requires_at_least' => '/^Requires at least:\s*(.+)$/im',
			'tested_up_to'      => '/^Tested up to:\s*(.+)$/im',
			'requires_php'      => '/^Requires PHP:\s*(.+)$/im',
		);

		foreach ( $fields as $key => $pattern ) {
			if ( preg_match( $pattern, $content, $m ) ) {
				$data[ $key ] = trim( $m[1] );
			}
		}

		if ( preg_match( '/^===\s*(.+?)\s*===/m', $content, $m ) ) {
			$data['name'] = trim( $m[1] );
		} elseif ( preg_match( '/^#\s+(.+)$/m', $content, $m ) ) {
			$data['name'] = trim( $m[1] );
		}

		return $data;
	}

	/**
	 * Parse WordPress plugin PHP header fields.
	 *
	 * @since 2.1.0
	 *
	 * @param string $content Raw PHP file content.
	 * @return array<string, string>
	 */
	public function parse_plugin_headers( string $content ): array {
		$data   = array();
		$fields = array(
			'Plugin Name'       => '/^\s*[\/*#]*\s*Plugin Name:\s*(.+)$/im',
			'Version'           => '/^\s*[\/*#]*\s*Version:\s*(.+)$/im',
			'Requires at least' => '/^\s*[\/*#]*\s*Requires at least:\s*(.+)$/im',
			'Tested up to'      => '/^\s*[\/*#]*\s*Tested up to:\s*(.+)$/im',
			'Requires PHP'      => '/^\s*[\/*#]*\s*Requires PHP:\s*(.+)$/im',
			'Author'            => '/^\s*[\/*#]*\s*Author:\s*(.+)$/im',
		);

		foreach ( $fields as $key => $pattern ) {
			if ( preg_match( $pattern, $content, $m ) ) {
				$data[ $key ] = trim( $m[1] );
			}
		}

		return $data;
	}
}
