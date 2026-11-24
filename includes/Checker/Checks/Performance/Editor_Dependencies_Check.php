<?php
/**
 * Class Editor_Dependencies_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Performance;

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\Experimental_Check;
use WP_Scripts;

/**
 * Warns about admin editor entry points loaded through frontend plugin scripts.
 *
 * Reuses the frontend URL discovery and preparation used by the script scope check.
 */
class Editor_Dependencies_Check extends Enqueued_Scripts_Scope_Check {

	use Experimental_Check;

	/**
	 * Handles already reported during the current run.
	 *
	 * @var array
	 */
	private $reported_handles = array();

	/**
	 * Runs the check with fresh deduplication state for each plugin.
	 *
	 * @param Check_Result $result Results to amend.
	 */
	public function run( Check_Result $result ) {
		$this->reported_handles = array();
		$this->run_for_urls(
			$this->get_urls(),
			function () use ( $result ) {
				$this->check_url( $result );
			}
		);
	}

	/**
	 * Collects scripts actually printed for a frontend URL.
	 *
	 * @param Check_Result $result Results to amend.
	 */
	protected function check_url( Check_Result $result ) {
		unset( $GLOBALS['wp_scripts'] );
		$scripts      = wp_scripts();
		$buffer_level = ob_get_level();
		ob_start();
		try {
			wp_enqueue_scripts();
			$scripts->do_head_items();
			$scripts->do_footer_items();
		} finally {
			while ( ob_get_level() > $buffer_level ) {
				if ( false === ob_end_clean() ) {
					break;
				}
			}
		}
		$this->check_scripts( $result, $scripts );
	}

	/**
	 * Checks printed plugin scripts and their transitive dependencies.
	 *
	 * The reusable wp-block-editor and wp-editor packages can be legitimate on the
	 * frontend. Only admin application entry points are considered here.
	 *
	 * @param Check_Result $result  Results to amend.
	 * @param WP_Scripts   $scripts Script registry for the current frontend URL.
	 */
	protected function check_scripts( Check_Result $result, WP_Scripts $scripts ) {
		foreach ( $scripts->done as $handle ) {
			if ( isset( $this->reported_handles[ $handle ] ) || ! isset( $scripts->registered[ $handle ] ) ) {
				continue;
			}
			$script = $scripts->registered[ $handle ];
			$file   = $this->get_plugin_script_file( $result, $script->src );
			if ( false === $file ) {
				continue;
			}
			$entry_point = $this->find_admin_entry_point( $scripts, $script->deps );
			if ( false === $entry_point ) {
				continue;
			}
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: %s: WordPress admin editor application script handle. */
					__( 'This frontend script depends on the admin editor application %s. Review whether it should use enqueue_block_editor_assets instead of enqueue_block_assets. Reusable editor packages alone do not trigger this advisory.', 'plugin-check' ),
					$entry_point
				),
				'EditorDependencies.AdminEntryPoint',
				$file
			);
			$this->reported_handles[ $handle ] = true;
		}
	}

	/**
	 * Finds an admin editor entry point without looping through cyclic dependencies.
	 *
	 * @param WP_Scripts $scripts      Script registry.
	 * @param array      $dependencies Initial dependencies.
	 * @return string|false Entry point handle, or false if none is found.
	 */
	private function find_admin_entry_point( WP_Scripts $scripts, array $dependencies ) {
		$seen = array();
		while ( $dependencies ) {
			$handle = array_pop( $dependencies );
			if ( isset( $seen[ $handle ] ) ) {
				continue;
			}
			$seen[ $handle ] = true;
			if ( in_array( $handle, array( 'wp-edit-post', 'wp-edit-site' ), true ) ) {
				return $handle;
			}
			if ( isset( $scripts->registered[ $handle ] ) ) {
				$dependencies = array_merge( $dependencies, $scripts->registered[ $handle ]->deps );
			}
		}
		return false;
	}

	/**
	 * Maps a script URL to a plugin file, enforcing origin and directory boundaries.
	 *
	 * @param Check_Result $result Results containing the plugin context.
	 * @param string|bool  $source Script source, which may be an alias.
	 * @return string|false Absolute plugin file path, or false for other sources.
	 */
	private function get_plugin_script_file( Check_Result $result, $source ) {
		if ( ! is_string( $source ) || '' === $source ) {
			return false;
		}
		$plugin = wp_parse_url( $result->plugin()->url() );
		$script = wp_parse_url( $source );
		if ( false === $plugin || false === $script || empty( $script['path'] ) ) {
			return false;
		}
		if ( isset( $script['host'] ) ) {
			$plugin_scheme = strtolower( $plugin['scheme'] ?? '' );
			$script_scheme = strtolower( $script['scheme'] ?? $plugin_scheme );
			$plugin_port   = $plugin['port'] ?? ( 'https' === $plugin_scheme ? 443 : 80 );
			$script_port   = $script['port'] ?? ( 'https' === $script_scheme ? 443 : 80 );
			if ( strtolower( $script['host'] ) !== strtolower( $plugin['host'] ?? '' ) || $script_scheme !== $plugin_scheme || $script_port !== $plugin_port ) {
				return false;
			}
		}
		$prefix = rtrim( $plugin['path'] ?? '', '/' ) . '/';
		if ( 0 !== strpos( $script['path'], $prefix ) ) {
			return false;
		}
		$relative = substr( $script['path'], strlen( $prefix ) );
		if ( '' === $relative || preg_match( '~(?:^|/)\\.\\.?(?:/|$)|%2e|%2f|%5c|\\\\\\\\~i', $relative ) ) {
			return false;
		}
		return trailingslashit( $result->plugin()->path() ) . $relative;
	}

	/**
	 * Gets the description of the check.
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Checks frontend plugin scripts for dependencies on admin editor applications.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL.
	 *
	 * @return string Documentation URL.
	 */
	public function get_documentation_url(): string {
		return 'https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/#editor-scripts-and-styles';
	}
}
