<?php
/**
 * Class WordPress\Plugin_Check\Admin\Admin_AJAX
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

/**
 * Class is handling admin tools page functionality.
 *
 * @since n.e.x.t
 */
class Admin_AJAX {

	/**
	 * Nonce key.
	 *
	 * @var string
	 */
	private $nonce_key = '95854-random-admin-check-plugin-check-3845962';

	/**
	 * Initializes hooks.
	 *
	 * @since n.e.x.t
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_plugin_check_run_check', array( $this, 'run_check' ) );
	}

	/**
	 * Create nonce and send it.
	 *
	 * @since n.e.x.t
	 */
	public function get_nonce() {

		return wp_create_nonce( $this->nonce_key );
	}

	/**
	 * Run check.
	 *
	 * @since n.e.x.t
	 */
	public function run_check() {

		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );

		if ( ! wp_verify_nonce( $nonce, $this->nonce_key ) ) {

			wp_send_json_error();
		}

		wp_send_json_success(
			array(
				'message' => 'Verified',
			)
		);
	}
}
