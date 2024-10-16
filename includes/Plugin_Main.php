<?php
/**
 * Class WordPress\Plugin_Check\Plugin_Main
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check;

use WordPress\Plugin_Check\Admin\Admin_AJAX;
use WordPress\Plugin_Check\Admin\Admin_Page;

/**
 * Main class for the plugin.
 *
 * @since 1.0.0
 */
class Plugin_Main {

	/**
	 * Context instance for the plugin.
	 *
	 * @since 1.0.0
	 * @var Plugin_Context
	 */
	protected $context;

	/**
	 * Constructor. Set the plugin main file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $main_file Absolute path of the plugin main file.
	 */
	public function __construct( $main_file ) {
		$this->context = new Plugin_Context( $main_file );
	}

	/**
	 * Returns the Plugin Context.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin_Context
	 */
	public function context() {
		return $this->context;
	}

	/**
	 * Registers WordPress hooks for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @global Plugin_Context $context The plugin context instance.
	 */
	public function add_hooks() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			global $context;

			// Setup the CLI command.
			$context = $this->context();
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'cli.php';
		}

		$admin_ajax = new Admin_AJAX();
		// Create the Admin page.
		$admin_page = new Admin_Page( $admin_ajax );
		$admin_page->add_hooks();
	}
}
