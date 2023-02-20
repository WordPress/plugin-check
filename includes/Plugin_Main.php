<?php
/**
 * Class WordPress\Plugin_Check\Plugin_Main
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check;

use WordPress\Plugin_Check\Admin\Admin_Page;

/**
 * Main class for the plugin.
 *
 * @since n.e.x.t
 */
class Plugin_Main {

	/**
	 * Context instance for the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @var Plugin_Context
	 */
	protected $context;

	/**
	 * Constructor. Set the plugin main file.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $main_file Absolute path of the plugin main file.
	 */
	public function __construct( $main_file ) {
		$this->context = new Plugin_Context( $main_file );
	}

	/**
	 * Returns the Plugin Context.
	 *
	 * @since n.e.x.t
	 *
	 * @return Plugin_Context
	 */
	public function context() {
		return $this->context;
	}

	/**
	 * Registers WordPress hooks for the plugin.
	 *
	 * @since n.e.x.t
	 */
	public function add_hooks() {
		// @TODO: Update to register CLI command to WordPress as part of issue #30

		$admin_page = new Admin_Page();
		$admin_page->add_hooks();
	}
}
