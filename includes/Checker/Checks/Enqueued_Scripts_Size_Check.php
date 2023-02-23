<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Enqueued_Scripts_Size_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\With_Shared_Preparations;
use WordPress\Plugin_Check\Traits\URL_Aware;
use WordPress\Plugin_Check\Checker\Preparations\Demo_Posts_Creation_Preparation;
use Exception;

/**
 * Check for running WordPress internationalization sniffs.
 *
 * @since n.e.x.t
 */
class Enqueued_Scripts_Size_Check extends Abstract_Runtime_Check implements With_Shared_Preparations {

	use URL_Aware;

	/**
	 * Threshold for script size to surface a warning for.
	 *
	 * @since n.e.x.t
	 * @var int
	 */
	private $threshold_size;

	/**
	 * List of viewable post types.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private $viewable_post_types;

	/**
	 * Set the threshold size for script sizes to surface warnings.
	 *
	 * @since n.e.x.t
	 *
	 * @param integer $threshold_size The threshold in bytes for script size to surface warnings.
	 */
	public function __construct( $threshold_size = 300000 ) {
		$this->threshold_size = $threshold_size;

		$this->viewable_post_types = array_filter(
			get_post_types(),
			function( $post_type ) {
				return is_post_type_viewable( $post_type );
			}
		);
	}

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
		$orig_scripts = isset( $GLOBALS['wp_scripts'] ) ? $GLOBALS['wp_scripts'] : null;

		// Backup the original values for the global state.
		$check = $this;
		$check->backup_globals();

		return function() use ( $orig_scripts, $check ) {
			if ( is_null( $orig_scripts ) ) {
				unset( $GLOBALS['wp_scripts'] );
			} else {
				$GLOBALS['wp_scripts'] = $orig_scripts;
			}

			$check->restore_globals();
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
			function( $post_type ) {
				return array(
					'post_title'   => "Demo {$post_type} post",
					'post_content' => 'Test content',
					'post_type'    => $post_type,
					'post_status'  => 'publish',
				);
			},
			$this->viewable_post_types
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
	 * @since n.e.x.t
	 *
	 * @return array List of URL strings (either full URLs or paths).
	 */
	protected function get_urls() {
		$urls = array( home_url() );

		foreach ( $this->viewable_post_types as $post_type ) {
			$posts = get_posts(
				array(
					'posts_per_page' => 1,
					'post_type'      => $post_type,
					'post_status'    => 'publish',
				)
			);
			if ( isset( $posts[0] ) ) {
				$urls[] = get_permalink( $posts[0] );
			}
		}

		return $urls;
	}

	/**
	 * Amends the given result by running the check for the given URL.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param string       $url    URL to run the check for.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	protected function check_url( Check_Result $result, $url ) {
		// Reset the WP_Scripts instance.
		unset( $GLOBALS['wp_scripts'] );

		// Run the 'wp_enqueue_script' action, wrapped in an output buffer in case of any callbacks printing scripts
		// directly. This is discouraged, but some plugins or themes are still doing it.
		ob_start();
		wp_enqueue_scripts();
		ob_get_clean();

		$plugin_scripts     = array();
		$plugin_script_size = 0;

		foreach ( wp_scripts()->done as $handle ) {
			$script = wp_scripts()->registered[ $handle ];

			if ( ! $script->src || strpos( $script->src, $result->plugin()->url() ) !== 0 ) {
				continue;
			}

			// Get size of script src.
			$script_path = str_replace( $result->plugin()->url(), $result->plugin()->path(), $script->src );
			$script_size = function_exists( 'wp_filesize' ) ? wp_filesize( $script_path ) : filesize( $script_path );

			// Get size of additional inline scripts.
			if ( ! empty( $script->extra['after'] ) ) {
				foreach ( $script->extra['after'] as $extra ) {
					$script_size += ( is_string( $extra ) ) ? mb_strlen( $extra, '8bit' ) : 0;
				}
			}

			if ( ! empty( $script->extra['before'] ) ) {
				foreach ( $script->extra['before'] as $extra ) {
					$script_size += ( is_string( $extra ) ) ? mb_strlen( $extra, '8bit' ) : 0;
				}
			}

			$plugin_scripts[]    = array(
				'path' => $script_path,
				'size' => $script_size,
			);
			$plugin_script_size += $script_size;
		}

		if ( $plugin_script_size > $this->threshold_size ) {
			foreach ( $plugin_scripts as $plugin_script ) {
				$result->add_message(
					false,
					sprintf(
						'This script has a size of %1$s which in combination with the other scripts enqueued on %2$s exceeds the script size threshold of %3$s.',
						size_format( $plugin_script['size'] ),
						$url,
						size_format( $this->threshold_size )
					),
					array(
						'code' => 'EnqueuedScripts',
						'file' => $plugin_script['path'],
					)
				);
			}
		}
	}
}
