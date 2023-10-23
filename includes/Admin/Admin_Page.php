<?php
/**
 * Class WordPress\Plugin_Check\Admin\Admin_Page
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Checker\Check_Categories;

/**
 * Class is handling admin tools page functionality.
 *
 * @since n.e.x.t
 */
final class Admin_Page {

	/**
	 * Admin AJAX class instance.
	 *
	 * @since n.e.x.t
	 * @var Admin_AJAX
	 */
	protected $admin_ajax;

	/**
	 * Admin page hook suffix.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	protected $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param Admin_AJAX $admin_ajax Instance of Admin_AJAX.
	 */
	public function __construct( Admin_AJAX $admin_ajax ) {
		$this->admin_ajax = $admin_ajax;
	}

	/**
	 * Registers WordPress hooks for the admin page.
	 *
	 * @since n.e.x.t
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'add_and_initialize_page' ) );
		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 4 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_jump_to_line_code_editor' ) );

		$this->admin_ajax->add_hooks();
	}

	/**
	 * Adds the admin page under the tools menu.
	 *
	 * @since n.e.x.t
	 */
	public function add_page() {
		$this->hook_suffix = add_management_page(
			__( 'Plugin Check', 'plugin-check' ),
			__( 'Plugin Check', 'plugin-check' ),
			'activate_plugins',
			'plugin-check',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Adds and initializes the admin page under the tools menu.
	 *
	 * @since n.e.x.t
	 */
	public function add_and_initialize_page() {
		$this->add_page();
		add_action( 'load-' . $this->get_hook_suffix(), array( $this, 'initialize_page' ) );
	}

	/**
	 * Initializes page hooks.
	 *
	 * @since n.e.x.t
	 */
	public function initialize_page() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
	}

	/**
	 * Loads the check's script.
	 *
	 * @since n.e.x.t
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'plugin-check-admin',
			WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/plugin-check-admin.js',
			array(
				'wp-util',
			),
			WP_PLUGIN_CHECK_VERSION,
			true
		);

		wp_add_inline_script(
			'plugin-check-admin',
			'const PLUGIN_CHECK = ' . json_encode(
				array(
					'nonce'                           => $this->admin_ajax->get_nonce(),
					'actionGetChecksToRun'            => Admin_AJAX::ACTION_GET_CHECKS_TO_RUN,
					'actionSetUpRuntimeEnvironment'   => Admin_AJAX::ACTION_SET_UP_ENVIRONMENT,
					'actionRunChecks'                 => Admin_AJAX::ACTION_RUN_CHECKS,
					'actionCleanUpRuntimeEnvironment' => Admin_AJAX::ACTION_CLEAN_UP_ENVIRONMENT,
					'successMessage'                  => __( 'No errors found.', 'plugin-check' ),
					'errorMessage'                    => __( 'Errors were found.', 'plugin-check' ),
				)
			),
			'before'
		);
	}

	/**
	 * Enqueue a script in the WordPress admin on plugin-editor.php.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function add_jump_to_line_code_editor( $hook_suffix ) {
		if ( 'plugin-editor.php' !== $hook_suffix ) {
			return;
		}

		$line = (int) ( $_GET['line'] ?? 0 );
		if ( ! $line ) {
			return;
		}

		wp_add_inline_script(
			'wp-theme-plugin-editor',
			sprintf(
				'
					(
						( originalInitCodeEditor ) => {
							wp.themePluginEditor.initCodeEditor = function() {
								originalInitCodeEditor.apply( this, arguments );
								this.instance.codemirror.doc.setCursor( %d - 1 );
							};
						}
					)( wp.themePluginEditor.initCodeEditor );
				',
				wp_json_encode( $line )
			)
		);
	}

	/**
	 * Returns the list of plugins.
	 *
	 * @since n.e.x.t
	 *
	 * @return array List of available plugins.
	 */
	private function get_available_plugins() {
		$available_plugins = get_plugins();

		if ( empty( $available_plugins ) ) {
			return array();
		}

		$plugin_check_base_name = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );

		if ( isset( $available_plugins[ $plugin_check_base_name ] ) ) {
			unset( $available_plugins[ $plugin_check_base_name ] );
		}

		return $available_plugins;
	}

	/**
	 * Renders the "Plugin Check" page.
	 *
	 * @since n.e.x.t
	 */
	public function render_page() {
		global $available_plugins, $selected_plugin_basename, $categories;

		$available_plugins = $this->get_available_plugins();

		$selected_plugin_basename = filter_input( INPUT_GET, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		$categories = Check_Categories::get_categories();

		require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page.php';
	}

	/**
	 * Adds "check this plugin" link in the plugins list table.
	 *
	 * @since n.e.x.t
	 *
	 * @param array  $actions     List of actions.
	 * @param string $plugin_file Plugin main file.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $context     The plugin context. By default this can include 'all',
	 *                            'active', 'inactive', 'recently_activated', 'upgrade',
	 *                            'mustuse', 'dropins', and 'search'.
	 * @return array The modified list of actions.
	 */
	public function filter_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {

		$plugin_check_base_name = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );
		if ( in_array( $context, array( 'mustuse', 'dropins' ), true ) || $plugin_check_base_name === $plugin_file ) {
			return $actions;
		}

		if ( current_user_can( 'activate_plugins' ) ) {
			$actions[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( "tools.php?page=plugin-check&plugin={$plugin_file}" ) ),
				esc_html__( 'Check this plugin', 'plugin-check' )
			);
		}

		return $actions;
	}

	/**
	 * Render the results table templates in the footer.
	 *
	 * @since n.e.x.t
	 */
	public function admin_footer() {
		ob_start();
		require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/results-table.php';
		$results_table_template = ob_get_clean();
		wp_print_inline_script_tag(
			$results_table_template,
			array(
				'id'   => 'tmpl-plugin-check-results-table',
				'type' => 'text/template',
			)
		);

		ob_start();
		require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/results-row.php';
		$results_row_template = ob_get_clean();
		wp_print_inline_script_tag(
			$results_row_template,
			array(
				'id'   => 'tmpl-plugin-check-results-row',
				'type' => 'text/template',
			)
		);

		ob_start();
		require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/results-complete.php';
		$results_row_template = ob_get_clean();
		wp_print_inline_script_tag(
			$results_row_template,
			array(
				'id'   => 'tmpl-plugin-check-results-complete',
				'type' => 'text/template',
			)
		);
	}

	/**
	 * Gets the hook suffix under which the admin page is added.
	 *
	 * @since n.e.x.t
	 *
	 * @return string Hook suffix, or empty string if admin page was not added.
	 */
	public function get_hook_suffix() {
		return $this->hook_suffix;
	}
}
