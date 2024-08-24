<?php
/**
 * Class Enqueued_Scripts_Scope_Check.
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
 * Check if a script is present in all URLs.
 *
 * @since 1.0.2
 */
class Enqueued_Scripts_Scope_Check extends Abstract_Runtime_Check implements With_Shared_Preparations {

	use Amend_Check_Result;
	use Stable_Check;
	use URL_Aware;

	/**
	 * List of viewable post types.
	 *
	 * @since 1.0.2
	 * @var array
	 */
	private $viewable_post_types;

	/**
	 * List of plugin scripts.
	 *
	 * @since 1.0.2
	 * @var array
	 */
	private $plugin_scripts = array();

	/**
	 * Plugin script counter.
	 *
	 * @since 1.0.2
	 * @var array
	 */
	private $plugin_script_count = array();

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.0.2
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PERFORMANCE );
	}

	/**
	 * Runs this preparation step for the environment and returns a cleanup function.
	 *
	 * @since 1.0.2
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
	 * @since 1.0.2
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
	 * @since 1.0.2
	 *
	 * @param Check_Result $result The check results to amend and the plugin context.
	 */
	public function run( Check_Result $result ) {
		$urls = $this->get_urls();
		$this->run_for_urls(
			$urls,
			function () use ( $result ) {
				$this->check_url( $result );
			}
		);

		if ( ! empty( $this->plugin_scripts ) ) {
			$url_count = count( $urls );
			foreach ( $this->plugin_scripts as $plugin_script ) {
				if ( isset( $plugin_script['count'] ) && ( $url_count === $plugin_script['count'] ) ) {
					$this->add_result_warning_for_file(
						$result,
						__( 'This script is being loaded in all contexts.', 'plugin-check' ),
						'EnqueuedScriptsScope',
						$plugin_script['path']
					);
				}
			}
		}
	}

	/**
	 * Gets the list of URLs to run this check for.
	 *
	 * @since 1.0.2
	 *
	 * @return array List of URL strings (either full URLs or paths).
	 *
	 * @throws Exception Thrown when a post type URL cannot be retrieved.
	 */
	protected function get_urls() {
		$urls = array( home_url( '/' ), get_search_link(), get_author_posts_url( 1 ) );
		foreach ( $this->get_viewable_post_types() as $post_type ) {
			$args = array(
				'posts_per_page'         => 1,
				'post_type'              => $post_type,
				'post_status'            => array( 'publish', 'inherit' ),
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'lazy_load_term_meta'    => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);

			$the_query = new \WP_Query( $args );

			if ( $the_query->have_posts() ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();

					$urls[]         = get_permalink();
					$post           = get_post();
					$taxonomy_names = get_post_taxonomies( $post );
					foreach ( $taxonomy_names as $taxonomy_name ) {
						if ( ! is_taxonomy_viewable( $taxonomy_name ) ) {
							continue;
						}

						$terms = get_the_terms( $post, $taxonomy_name );
						if ( ! is_array( $terms ) ) {
							continue;
						}
						foreach ( $terms as $term ) {
							$term_link = get_term_link( $term );
							if ( ! is_wp_error( $term_link ) ) {
								$urls[] = $term_link;
							}
						}
					}
				}
			} else {
				throw new Exception(
					sprintf(
						/* translators: %s: The Post Type name. */
						__( 'Unable to retrieve post URL for post type: %s', 'plugin-check' ),
						$post_type
					)
				);
			}

			/* Restore original Post Data */
			wp_reset_postdata();
		}

		return $urls;
	}

	/**
	 * Amends the given result by running the check for the given URL.
	 *
	 * @since 1.0.2
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	protected function check_url( Check_Result $result ) {
		// Reset the wp_scripts instance.
		unset( $GLOBALS['wp_scripts'] );

		/*
		 * Run the 'wp_enqueue_script' action, wrapped in an output buffer in case of any callbacks printing scripts
		 * directly. This is discouraged, but some plugins or themes are still doing it.
		 */
		$wp_scripts = wp_scripts();
		ob_start();
			wp_enqueue_scripts();
			$wp_scripts->do_head_items();
			$wp_scripts->do_footer_items();
		ob_get_clean();

		foreach ( $wp_scripts->done as $handle ) {
			$script = $wp_scripts->registered[ $handle ];

			if ( ! $script->src || strpos( $script->src, $result->plugin()->url() ) !== 0 ) {
				continue;
			}

			$script_path = str_replace( $result->plugin()->url(), $result->plugin()->path(), $script->src );

			if ( ! isset( $this->plugin_script_count[ $handle ] ) ) {
				$this->plugin_script_count[ $handle ] = 0;
			}
			$this->plugin_script_count[ $handle ] += 1;

			$this->plugin_scripts[ $handle ] = array(
				'path'  => $script_path,
				'count' => $this->plugin_script_count[ $handle ],
			);
		}
	}

	/**
	 * Returns an array of viewable post types.
	 *
	 * @since 1.0.2
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
		return __( 'Checks whether any scripts are loaded on all pages, which is usually not desirable and can lead to performance issues.', 'plugin-check' );
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
