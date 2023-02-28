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
	 * @since n.e.x.t
	 * @var string
	 */
	private $nonce_key;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $nonce_key Nonce key.
	 */
	public function __construct( $nonce_key ) {
		$this->nonce_key = $nonce_key;
	}

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
				'message' => 'Verified!',
			)
		);
	}
}
