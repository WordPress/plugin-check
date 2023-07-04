<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Enqueued_Styles_Scope_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Preparations\Demo_Posts_Creation_Preparation;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPress\Plugin_Check\Checker\With_Shared_Preparations;
use WordPress\Plugin_Check\Traits\URL_Aware;

/**
 * Check if a stylesheet is present in all URLs.
 *
 * @since n.e.x.t
 */
class Enqueued_Styles_Scope_Check extends Abstract_Runtime_Check implements With_Shared_Preparations {

	use URL_Aware, Stable_Check;

	/**
	 * List of viewable post types.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private $viewable_post_types;

	/**
	 * List of plugin styles.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private $plugin_styles = array();

	/**
	 * Plugin style counter.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private $plugin_style_count = array();

	/**
	 * Runs this preparation step for the environment and returns a cleanup function.
	 *
	 * @since n.e.x.t
	 *
	 * @return callable Cleanup function to revert any changes made here.
	 *
	 * @throws Exception Thrown when preparation fails.
	 */
	public function prepare() {
		$orig_scripts = isset( $GLOBALS['wp_styles'] ) ? $GLOBALS['wp_styles'] : null;

		// Backup the original values for the global state.
		$this->backup_globals();

		return function() use ( $orig_scripts ) {
			if ( is_null( $orig_scripts ) ) {
				unset( $GLOBALS['wp_styles'] );
			} else {
				$GLOBALS['wp_styles'] = $orig_scripts;
			}

			$this->restore_globals();
		};
	}

	/**
	 * Returns an array of shared preparations for the check.
	 *
	 * @since n.e.x.t
	 *
	 * @return array Returns a map of $class_name => $constructor_args pairs. If the class does not
	 *               need any constructor arguments, it would just be an empty array.
	 */
	public function get_shared_preparations() {
		$demo_posts = array_map(
			static function( $post_type ) {
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
	 * @since n.e.x.t
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

		if ( ! empty( $this->plugin_styles ) ) {
			$url_count = count( $urls );
			foreach ( $this->plugin_styles as $plugin_style ) {
				if ( isset( $plugin_style['count'] ) && ( $url_count === $plugin_style['count'] ) ) {
					$result->add_message(
						false,
						__( 'This style is being loaded in all contexts.', 'plugin-check' ),
						array(
							'code' => 'EnqueuedStylesScope.StyleLoadedInAllContext',
							'file' => $plugin_style['path'],
						)
					);
				}
			}
		}
	}

	/**
	 * Gets the list of URLs to run this check for.
	 *
	 * @since n.e.x.t
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
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	protected function check_url( Check_Result $result ) {
		// Reset the wp_styles instance.
		unset( $GLOBALS['wp_styles'] );

		/*
		 * Run the 'wp_enqueue_script' action, wrapped in an output buffer in case of any callbacks printing styles
		 * directly. This is discouraged, but some plugins or themes are still doing it.
		 */
		$wp_styles = wp_styles();
		ob_start();
			wp_enqueue_scripts();
			$wp_styles->do_footer_items();
		ob_get_clean();

		foreach ( $wp_styles->done as $handle ) {
			$style = $wp_styles->registered[ $handle ];

			if ( ! $style->src || strpos( $style->src, $result->plugin()->url() ) !== 0 ) {
				continue;
			}

			$style_path = str_replace( $result->plugin()->url(), $result->plugin()->path(), $style->src );

			if ( ! isset( $this->plugin_style_count[ $handle ] ) ) {
				$this->plugin_style_count[ $handle ] = 0;
			}
			$this->plugin_style_count[ $handle ] += 1;

			$this->plugin_styles[ $handle ] = array(
				'path'  => $style_path,
				'count' => $this->plugin_style_count[ $handle ],
			);
		}
	}

	/**
	 * Returns an array of viewable post types.
	 *
	 * @return array Array of viewable post type slugs.
	 */
	private function get_viewable_post_types() {
		if ( ! is_array( $this->viewable_post_types ) ) {
			$this->viewable_post_types = array_filter( get_post_types(), 'is_post_type_viewable' );
		}

		return $this->viewable_post_types;
	}
}
