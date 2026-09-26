<?php
/**
 * Class Enqueued_Scripts_Size_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Performance;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_Runtime_Check;
use WordPress\Plugin_Check\Checker\Preparations\Demo_Posts_Creation_Preparation;
use WordPress\Plugin_Check\Checker\With_Shared_Preparations;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPress\Plugin_Check\Traits\URL_Aware;

/**
 * Check for enqueued script sizes.
 *
 * @since 1.0.0
 */
class Enqueued_Scripts_Size_Check extends Abstract_Runtime_Check implements With_Shared_Preparations {

	use Amend_Check_Result;
	use Stable_Check;
	use URL_Aware;

	/**
	 * Threshold for script size to surface a warning for.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $threshold_size;

	/**
	 * List of viewable post types.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $viewable_post_types;

	/**
	 * Set the threshold size for script sizes to surface warnings.
	 *
	 * @since 1.0.0
	 *
	 * @param int $threshold_size The threshold in bytes for script size to surface warnings.
	 */
	public function __construct( $threshold_size = 300000 ) {
		$this->threshold_size = $threshold_size;
	}

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.0.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PERFORMANCE );
	}

	/**
	 * Runs this preparation step for the environment and returns a cleanup function.
	 *
	 * @since 1.0.0
	 *
	 * @return callable Cleanup function to revert any changes made here.
	 *
	 * @throws Exception Thrown when preparation fails.
	 */
	public function prepare() {
		$orig_scripts = isset( $GLOBALS['wp_scripts'] ) ? $GLOBALS['wp_scripts'] : null;

		// Backup the original values for the global state.
		$this->backup_globals();

		return function () use ( $orig_scripts ) {
			if ( is_null( $orig_scripts ) ) {
				unset( $GLOBALS['wp_scripts'] );
			} else {
				$GLOBALS['wp_scripts'] = $orig_scripts;
			}

			$this->restore_globals();
		};
	}

	/**
	 * Returns an array of shared preparations for the check.
	 *
	 * @since 1.0.0
	 *
	 * @return array Returns a map of $class_name => $constructor_args pairs. If the class does not
	 *               need any constructor arguments, it would just be an empty array.
	 */
	public function get_shared_preparations() {
		$demo_posts = array_map(
			static function ( $post_type ) {
				return array(
					'post_title'   => "Demo {$post_type} post",
					'post_content' => 'Test content',
					'post_type'    => $post_type,
					'post_status'  => 'publish',
				);
			},
			$this->get_viewable_post_types()
		);

		return array(
			Demo_Posts_Creation_Preparation::class => array( $demo_posts ),
		);
	}

	/**
	 * Runs the check on the plugin and amends results.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check results to amend and the plugin context.
	 */
	public function run( Check_Result $result ) {
		$this->run_for_urls(
			$this->get_urls(),
			function ( $url ) use ( $result ) {
				$this->check_url( $result, $url );
			}
		);
	}

	/**
	 * Gets the list of URLs to run this check for.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of URL strings (either full URLs or paths).
	 *
	 * @throws Exception Thrown when a post type URL cannot be retrieved.
	 */
	protected function get_urls() {
		$urls = array( home_url() );

		foreach ( $this->get_viewable_post_types() as $post_type ) {
			$posts = get_posts(
				array(
					'posts_per_page' => 1,
					'post_type'      => $post_type,
					'post_status'    => array( 'publish', 'inherit' ),
				)
			);

			if ( ! isset( $posts[0] ) ) {
				throw new Exception(
					sprintf(
						/* translators: %s: The Post Type name. */
						__( 'Unable to retrieve post URL for post type: %s', 'plugin-check' ),
						$post_type
					)
				);
			}

			$urls[] = get_permalink( $posts[0] );
		}

		return $urls;
	}

	/**
	 * Amends the given result by running the check for the given URL.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param string       $url    URL to run the check for.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	protected function check_url( Check_Result $result, $url ) {
		// Reset the WP_Scripts instance.
		unset( $GLOBALS['wp_scripts'] );

		// Run the 'wp_enqueue_script' action, wrapped in an output buffer in case of any callbacks printing scripts
		// directly. This is discouraged, but some plugins or themes are still doing it.
		ob_start();
		wp_enqueue_scripts();
		wp_scripts()->do_head_items();
		wp_scripts()->do_footer_items();
		ob_get_clean();

		$wp_scripts  = wp_scripts();
		$plugin_url  = $result->plugin()->url();
		$plugin_path = $result->plugin()->path();

		$plugin_scripts = array();
		$measured       = array();
		$seen           = array();
		$total_size     = 0;

		foreach ( $wp_scripts->done as $handle ) {
			$script = isset( $wp_scripts->registered[ $handle ] ) ? $wp_scripts->registered[ $handle ] : null;

			if ( ! $script || ! $script->src || strpos( $script->src, $plugin_url ) !== 0 ) {
				continue;
			}

			$plugin_scripts[ $handle ] = true;

			// Count the plugin's own script and every dependency it pulls in (jQuery, shared vendor
			// libraries and so on). Those load because of the plugin, so they add to the page weight
			// even when they are served from outside the plugin directory.
			foreach ( $this->get_handle_with_dependencies( $handle, $wp_scripts, $seen ) as $dep_handle ) {
				if ( isset( $measured[ $dep_handle ] ) ) {
					continue;
				}

				$dep_script = $wp_scripts->registered[ $dep_handle ];
				$dep_path   = $this->get_path_from_src( $dep_script->src, $plugin_url, $plugin_path );
				$dep_size   = $dep_path ? ( $this->get_file_size( $dep_path ) + $this->get_inline_size( $dep_script ) ) : 0;

				$measured[ $dep_handle ] = array(
					'path' => $dep_path,
					'size' => $dep_size,
				);
				$total_size             += $dep_size;
			}
		}

		if ( $total_size > $this->threshold_size ) {
			foreach ( array_keys( $plugin_scripts ) as $handle ) {
				// Only surface warnings for the plugin's own files; dependencies are counted toward
				// the total but are not something the plugin author edits directly.
				if ( empty( $measured[ $handle ]['path'] ) ) {
					continue;
				}

				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: 1: script file size. 2: tested URL. 3: threshold file size. */
						__( 'This script has a size of %1$s which in combination with the other scripts enqueued on %2$s exceeds the script size threshold of %3$s.', 'plugin-check' ),
						size_format( $measured[ $handle ]['size'] ),
						$url,
						size_format( $this->threshold_size )
					),
					'EnqueuedScriptsSize.ScriptSizeGreaterThanThreshold',
					$measured[ $handle ]['path']
				);
			}
		}
	}

	/**
	 * Returns a script handle together with its dependencies, resolved recursively and de-duplicated.
	 *
	 * @since 1.6.0
	 *
	 * @param string             $handle     Script handle to start from.
	 * @param \WP_Scripts        $wp_scripts The scripts registry.
	 * @param array<string,bool> $seen     Handles already collected, passed by reference to de-duplicate.
	 * @return string[] The handle followed by every dependency handle, each appearing once.
	 */
	private function get_handle_with_dependencies( $handle, $wp_scripts, array &$seen ) {
		if ( isset( $seen[ $handle ] ) || ! isset( $wp_scripts->registered[ $handle ] ) ) {
			return array();
		}

		$seen[ $handle ] = true;
		$handles         = array( $handle );

		foreach ( $wp_scripts->registered[ $handle ]->deps as $dep ) {
			$handles = array_merge( $handles, $this->get_handle_with_dependencies( $dep, $wp_scripts, $seen ) );
		}

		return $handles;
	}

	/**
	 * Resolves a script src to a local file path, or false when it can't be measured.
	 *
	 * Handles the plugin's own assets as well as dependencies served from elsewhere on the site
	 * (core scripts under wp-includes, other plugins, the uploads or content directories). Scripts
	 * served from another host, such as a CDN, return false since their size can't be read locally.
	 *
	 * @since 1.6.0
	 *
	 * @param string $src         The script src.
	 * @param string $plugin_url  The plugin's base URL.
	 * @param string $plugin_path The plugin's base path.
	 * @return string|false The local file path, or false if it can't be resolved to a readable file.
	 */
	private function get_path_from_src( $src, $plugin_url, $plugin_path ) {
		if ( ! $src ) {
			return false;
		}

		// The plugin's own assets map directly, which also handles symlinked or mu-plugin locations.
		if ( strpos( $src, $plugin_url ) === 0 ) {
			$path = strtok( str_replace( $plugin_url, $plugin_path, $src ), '?' );

			return ( $path && file_exists( $path ) ) ? $path : false;
		}

		$src = strtok( $src, '?' );

		if ( strpos( $src, '//' ) === 0 ) {
			$src = ( is_ssl() ? 'https:' : 'http:' ) . $src;
		}

		// Core registers its scripts with a root-relative src, e.g. /wp-includes/js/jquery/jquery.min.js.
		if ( strpos( $src, '/' ) === 0 ) {
			$src = site_url( $src );
		}

		// Map known local URL roots to their filesystem paths, most specific first.
		$roots = array(
			array( plugins_url(), WP_PLUGIN_DIR ),
			array( content_url(), WP_CONTENT_DIR ),
			array( includes_url(), ABSPATH . WPINC . '/' ),
			array( site_url( '/' ), ABSPATH ),
		);

		foreach ( $roots as $root ) {
			list( $url_root, $dir_root ) = $root;

			if ( $url_root && strpos( $src, $url_root ) === 0 ) {
				$path = wp_normalize_path( $dir_root . substr( $src, strlen( $url_root ) ) );

				return ( file_exists( $path ) && is_readable( $path ) ) ? $path : false;
			}
		}

		return false;
	}

	/**
	 * Returns the byte size of a file, treating an unreadable file as zero.
	 *
	 * @since 1.6.0
	 *
	 * @param string $path Absolute file path.
	 * @return int Size in bytes.
	 */
	private function get_file_size( $path ) {
		$size = function_exists( 'wp_filesize' ) ? wp_filesize( $path ) : filesize( $path );

		return is_int( $size ) ? $size : (int) $size;
	}

	/**
	 * Returns the combined byte size of a script's inline before/after additions.
	 *
	 * @since 1.6.0
	 *
	 * @param object $script The registered script object.
	 * @return int Size in bytes.
	 */
	private function get_inline_size( $script ) {
		$size = 0;

		foreach ( array( 'before', 'after' ) as $position ) {
			if ( empty( $script->extra[ $position ] ) ) {
				continue;
			}

			foreach ( $script->extra[ $position ] as $extra ) {
				$size += is_string( $extra ) ? mb_strlen( $extra, '8bit' ) : 0;
			}
		}

		return $size;
	}

	/**
	 * Returns an array of viewable post types.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of viewable post type slugs.
	 */
	private function get_viewable_post_types() {
		if ( ! is_array( $this->viewable_post_types ) ) {
			$this->viewable_post_types = array_filter( get_post_types(), 'is_post_type_viewable' );
		}

		return $this->viewable_post_types;
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.1.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return sprintf(
			/* translators: %s: Script size threshold. */
			__( 'Checks whether the cumulative size of all scripts enqueued on a page exceeds %s.', 'plugin-check' ),
			size_format( $this->threshold_size )
		);
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.1.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/', 'plugin-check' );
	}
}
