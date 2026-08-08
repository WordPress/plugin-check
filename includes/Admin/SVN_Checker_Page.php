<?php
/**
 * SVN_Checker_Page class.
 *
 * @package Plugin_Check
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\SVN\Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SVN_Checker_Page.
 *
 * Registers the SVN Checker menu page and renders the report UI.
 *
 * @since 2.1.0
 */
class SVN_Checker_Page {

	const MENU_SLUG = 'plugin-check-svn-checker';

	/**
	 * AJAX action name.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const ACTION_CHECK = 'plugin_check_svn_checker_check';

	/**
	 * Admin page hook.
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 2.1.0
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_' . self::ACTION_CHECK, array( $this, 'handle_check' ) );
	}

	/**
	 * Register admin menu page.
	 *
	 * @since 2.1.0
	 */
	public function add_page(): void {
		$hook = add_management_page(
			__( 'Plugin Check SVN Checker', 'plugin-check' ),
			__( 'Plugin Check SVN Checker', 'plugin-check' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);

		$this->hook_suffix = (string) $hook;
	}

	/**
	 * Enqueue assets only on this plugin's page.
	 *
	 * @since 2.1.0
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_scripts( string $hook_suffix ): void {
		if ( '' === $this->hook_suffix || $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'plugin-check-svn-checker',
			WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/plugin-check-svn-checker.css',
			array(),
			WP_PLUGIN_CHECK_VERSION
		);

		wp_enqueue_script(
			'plugin-check-svn-checker',
			WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/plugin-check-svn-checker.js',
			array(),
			WP_PLUGIN_CHECK_VERSION,
			true
		);

		wp_localize_script(
			'plugin-check-svn-checker',
			'pluginCheckSvnChecker',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::ACTION_CHECK,
				'nonce'   => wp_create_nonce( self::ACTION_CHECK ),
				'i18n'    => array(
					'error'         => __( 'Error', 'plugin-check' ),
					'enterSlug'     => __( 'Enter plugin slug', 'plugin-check' ),
					'requestFailed' => __( 'Request failed.', 'plugin-check' ),
					'viewInSvn'     => __( 'View in SVN', 'plugin-check' ),
					'colStatus'     => __( 'Status', 'plugin-check' ),
					'colCheck'      => __( 'Check', 'plugin-check' ),
					'colDetail'     => __( 'Detail', 'plugin-check' ),
					'pass'          => __( 'Pass', 'plugin-check' ),
					'warning'       => __( 'Warning', 'plugin-check' ),
					'fail'          => __( 'Fail', 'plugin-check' ),
					'info'          => __( 'Info', 'plugin-check' ),
				),
			)
		);
	}

	/**
	 * Render the page.
	 *
	 * @since 2.1.0
	 */
	public function render_page(): void {
		$slug = isset( $_GET['plugin_slug'] ) ? sanitize_title( wp_unslash( $_GET['plugin_slug'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap plugin-check-svn-checker-wrap">

			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="plugin-check-svn-checker-inner">

				<form id="plugin-check-svn-checker-form" class="plugin-check-svn-checker-form">
					<div class="plugin-check-svn-checker-search-row">
						<input
							type="text"
							name="plugin_slug"
							id="plugin-check-svn-checker-slug-input"
							value="<?php echo esc_attr( $slug ); ?>"
							class="plugin-check-svn-checker-slug-input"
							placeholder="<?php esc_attr_e( 'Enter plugin slug', 'plugin-check' ); ?>"
							autocomplete="off"
							spellcheck="false"
							required
						/>
						<button type="submit" class="button button-primary">
							<?php echo esc_html( _x( 'Check', 'button label', 'plugin-check' ) ); ?>
						</button>
						<span class="spinner" id="plugin-check-svn-checker-spinner"></span>
					</div>
				</form>

				<div id="plugin-check-svn-checker-result"></div>

			</div>

		</div>
		<?php
	}

	/**
	 * Handle the AJAX check request.
	 *
	 * @since 2.1.0
	 */
	public function handle_check(): void {
		check_ajax_referer( self::ACTION_CHECK, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'plugin-check' ) ), 403 );
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

		if ( empty( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin slug is required.', 'plugin-check' ) ), 400 );
		}

		$checker = new Checker( $slug );
		$report  = $checker->run();

		if ( ! empty( $report['meta']['error'] ) ) {
			wp_send_json_error(
				array(
					/* translators: %s: plugin slug. */
					'message' => sprintf( __( 'Plugin "%s" was not found in the WordPress.org SVN repository.', 'plugin-check' ), $slug ),
				),
				404
			);
		}

		wp_send_json_success( $report );
	}
}
