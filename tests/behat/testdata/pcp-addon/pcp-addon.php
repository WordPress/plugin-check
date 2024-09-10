<?php
/**
 * Plugin Name: PCP Addon
 * Plugin URI: https://example.com
 * Description: Plugin Check addon.
 * Version: 0.1.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins: plugin-check
 */

add_filter(
	'wp_plugin_check_categories',
	function ( array $categories ) {
		return array_merge( $categories, array( 'new_category' => esc_html__( 'New Category', 'pcp-addon' ) ) );
	}
);

add_filter(
	'wp_plugin_check_checks',
	function ( array $checks ) {
		require_once plugin_dir_path( __FILE__ ) . 'Example_Static_Check.php';
		require_once plugin_dir_path( __FILE__ ) . 'Example_Runtime_Check.php';

		return array_merge(
			$checks,
			array(
				'example_static'  => new Example_Static_Check(),
				'example_runtime' => new Example_Runtime_Check(),
			)
		);
	}
);
