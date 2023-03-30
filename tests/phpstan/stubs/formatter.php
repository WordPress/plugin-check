<?php

namespace WP_CLI;

class Formatter {
	/**
	 * @param array $assoc_args Output format arguments.
	 * @param array $fields Fields to display of each item.
	 * @param string|bool $prefix Check if fields have a standard prefix.
	 * False indicates empty prefix.
	 */
	public function __construct( &$assoc_args, $fields = null, $prefix = false ) {}

	/**
	 * Display multiple items according to the output arguments.
	 *
	 * @param array|Iterator $items The items to display.
	 * @param bool|array      $ascii_pre_colorized Optional. A boolean or an array of booleans to pass to `format()` if items in the table are pre-colorized. Default false.
	 */
	public function display_items( $items, $ascii_pre_colorized = false ) {}
}
