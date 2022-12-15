<?php
/**
 * Class WordPress\Plugin_Check\Plugin_Main
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check;

/**
 * Main class for the plugin
 *
 * @since 0.1.0
 */
class Plugin_Main {

	/**
	 * Context instance for the plugin.
	 *
	 * @since 0.1.0
	 *
	 * @var Plugin_Context
	 */
	protected $context;

	/**
	 * Constructor. Set the plugin main file.
	 *
	 * @since 0.1.0
	 *
	 * @param string $main_file Absolute path of the plugin main file.
	 * @return void
	 */
	public function __construct( string $main_file ) {
		$this->context = new Plugin_Context( $main_file );
	}

	/**
	 * Register WordPress hooks for the plugin.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function add_hooks() {
		//
	}
}
