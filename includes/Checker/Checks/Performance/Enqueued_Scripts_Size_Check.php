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

		$plugin_scripts     = array();
		$plugin_script_size = 0;
		$dep_scripts        = array();
		$dep_script_size    = 0;

		foreach ( wp_scripts()->done as $handle ) {
			$script = wp_scripts()->registered[ $handle ];

			if ( ! $script->src ) {
				continue;
			}

			$is_plugin_owned = strpos( $script->src, $result->plugin()->url() ) === 0;

			// Get size of script src.
			$script_path = $this->script_src_to_path( $script->src, $result );

			$script_size = ( $script_path && file_exists( $script_path ) )
				? ( function_exists( 'wp_filesize' ) ? wp_filesize( $script_path ) : filesize( $script_path ) )
				: 0;

			// Guard against wp_filesize/filesize returning false on read failure.
			$script_size = (int) $script_size;

			// Get size of additional inline scripts.
			$script_size += $this->get_inline_script_size( $script );

			if ( $is_plugin_owned ) {
				$plugin_scripts[]    = array(
					'path' => $script_path,
					'size' => $script_size,
				);
				$plugin_script_size += $script_size;
			} else {
				$dep_scripts[]    = array(
					'src'  => $script->src,
					'size' => $script_size,
				);
				$dep_script_size += $script_size;
			}
		}

		$total_script_size = $plugin_script_size + $dep_script_size;

		if ( $plugin_script_size > $this->threshold_size ) {
			foreach ( $plugin_scripts as $plugin_script ) {
				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: 1: script file size. 2: tested URL. 3: threshold file size. */
						__( 'This script has a size of %1$s which in combination with the other scripts enqueued on %2$s exceeds the script size threshold of %3$s.', 'plugin-check' ),
						size_format( $plugin_script['size'] ),
						$url,
						size_format( $this->threshold_size )
					),
					'EnqueuedScriptsSize.ScriptSizeGreaterThanThreshold',
					$plugin_script['path'] ?? ''
				);
			}
		}

		if ( $dep_script_size > 0 && $total_script_size > $this->threshold_size ) {
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: 1: total script size including dependencies. 2: formatted dependency size. 3: dependency script count. 4: tested URL. */
					__( 'The total size of scripts enqueued for the page is %1$s with dependencies adding %2$s (from %3$d script(s)) on %4$s, exceeding the script size threshold.', 'plugin-check' ),
					size_format( $total_script_size ),
					size_format( $dep_script_size ),
					count( $dep_scripts ),
					$url
				),
				'EnqueuedScriptsSize.ExternalDependencySize',
				$result->plugin()->path()
			);
		}
	}

	/**
	 * Resolves a script source URL to a local filesystem path if possible.
	 *
	 * Returns null when the URL points to an external/CDN host that cannot be
	 * measured locally. Supports plugin-owned URLs, includes URL, and content URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $src    Script source URL.
	 * @param Check_Result $result The check result providing plugin context.
	 * @return string|null Absolute filesystem path, or null when not resolvable.
	 */
	protected function script_src_to_path( $src, Check_Result $result ) {
		// Strip query string version payload before resolving.
		$src_clean = strstr( $src, '?', true );
		if ( false === $src_clean ) {
			$src_clean = $src;
		}

		if ( strpos( $src_clean, $result->plugin()->url() ) === 0 ) {
			return wp_normalize_path(
				str_replace( $result->plugin()->url(), $result->plugin()->path(), $src_clean )
			);
		}

		if ( strpos( $src_clean, includes_url() ) === 0 ) {
			$relative = substr( $src_clean, strlen( includes_url() ) );
			$path     = ABSPATH . WPINC . '/' . ltrim( $relative, '/' );
			return file_exists( $path ) ? wp_normalize_path( $path ) : null;
		}

		if ( strpos( $src_clean, content_url() ) === 0 ) {
			$relative = substr( $src_clean, strlen( content_url() ) );
			$path     = trailingslashit( WP_CONTENT_DIR ) . ltrim( $relative, '/' );
			return file_exists( $path ) ? wp_normalize_path( $path ) : null;
		}

		return null;
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
	 * Sums the byte length of inline script payloads attached to a script dependency.
	 *
	 * @since 1.0.0
	 *
	 * @param object $script Script dependency whose extras are summed.
	 * @return int Total byte length of inline payloads.
	 */
	private function get_inline_script_size( $script ) {
		$size = 0;

		foreach ( array( 'after', 'before' ) as $position ) {
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
