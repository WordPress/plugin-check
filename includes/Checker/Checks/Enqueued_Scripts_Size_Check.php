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
	 * @since 1.0.0
	 * @var int
	 */
	public $threshold_size = 300000;

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

		return function() use ( $orig_scripts ) {

			$GLOBALS['wp_scripts'] = $orig_scripts;
		};
	}

	public function get_shared_preparations() {
		// TODO: Implement get_shared_preparations() method.
	}

	/**
	 * Gets the list of URLs to run this check for.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of URL strings (either full URLs or paths).
	 */
	protected function get_urls() {

		$urls = array( home_url() );

		$viewable_post_types = array_filter(
			get_post_types( array(), 'objects' ),
			function( $post_type ) {
				return is_post_type_viewable( $post_type );
			}
		);

		foreach ( $viewable_post_types as $post_type ) {
			$posts = get_posts(
				array(
					'posts_per_page' => 1,
					'post_type'      => $post_type->name,
					'post_status'    => 'publish',
				)
			);
			if ( isset( $posts[0] ) ) {
				$urls[] = get_permalink( $posts[0] );
			}
		}

		return $urls;
	}

	public function run( Check_Result $result ) {
		// TODO: Implement run() method.
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
