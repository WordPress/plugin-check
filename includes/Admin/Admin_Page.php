<?php
/**
 * Class WordPress\Plugin_Check\Admin\Admin_Page
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

/**
 * Class is handling admin tools page functionality.
 *
 * @since n.e.x.t
 */
class Admin_Page {

	/**
	 * Initializes hooks.
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 2 );
	}

	/**
	 * Registers the admin page under the tools menu.
	 */
	public function add_page() {
		add_management_page(
			__( 'Plugin Check', 'plugin-check' ),
			__( 'Plugin Check', 'plugin-check' ),
			'activate_plugins',
			'plugin-check',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Returns the list of plugins.
	 *
	 * @return array List of plugins.
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
	 * Render the "Plugin Check" page.
	 */
	public function render_page() {

		$available_plugins = $this->get_available_plugins();

		$selected_plugin_basename = filter_input( INPUT_GET, 'plugin', FILTER_SANITIZE_STRING );

		require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . '/templates/admin-page.php';
	}

	/**
	 * Add "check this plugin" link in the plugins list table.
	 *
	 * @param array  $actions     List of actions.
	 * @param string $plugin_file Plugin main file.
	 * @return array
	 */
	public function filter_plugin_action_links( $actions, $plugin_file ) {

		if ( current_user_can( 'activate_plugins' ) ) {

			$actions[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url() . 'tools.php?page=plugin-check&plugin=' . $plugin_file ),
				esc_html__( 'Check this plugin', 'plugin-check' )
			);
		}

		return $actions;
	}
}
