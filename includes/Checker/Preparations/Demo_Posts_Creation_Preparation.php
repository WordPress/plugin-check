<?php
/**
 * Class WordPress\Plugin_Check\Checker\Preparations\Demo_Posts_Creation_Preparation
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Preparations;

use WordPress\Plugin_Check\Checker\Preparation;
use Exception;

/**
 * Class to create demo posts to be used by Checks.
 *
 * @since n.e.x.t
 */
class Demo_Posts_Creation_Preparation implements Preparation {

	/**
	 * An array of posts data to create.
	 *
	 * @since n.e.x.t
	 * @var arrat
	 */
	protected $posts;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $posts An array of posts to create in the database.
	 */
	public function __construct( array $posts ) {
		$this->posts = $posts;
	}

	/**
	 * Creates the demo posts in the database to be us
	 *
	 * @since n.e.x.t
	 *
	 * @return callable Cleanup function to revert changes made by theme and plugin preparation classes.
	 *
	 * @throws Exception Thrown when preparation fails.
	 */
	public function prepare() {
		$post_ids = array();

		foreach ( $this->posts as $postarr ) {
			$post_id = wp_insert_post( $postarr, true );

			if ( is_wp_error( $post_id ) ) {
				throw new Exception( $post_id->get_error_message() );
			}

			$post_ids[] = $post_id;
		}

		// Return the cleanup function.
		return function () use ( $post_ids ) {
			foreach ( $post_ids as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		};
	}
}
