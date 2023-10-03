<?php
/**
 * Plugin Name: Test Plugin Performant WP_Query with errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Plugin Check plugin from the WordPress Performance Team, a collection of tests to help improve plugin performance.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: n.e.x.t
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-check
 *
 * @package test-plugin-check
 */

/**
 * File contains errors related to Performant WP_Query params check.
 */

 $query = new WP_Query(
	array(
		'post__not_in'   => array( 1, 2, 3 ),
		'posts_per_page' => -1,
		'cache_results'  => false,
		'meta_query'     => array(
			array(
				'key'     => 'age',
				'value'   => array( 3, 4 ),
				'compare' => 'IN',
			),
		),
		'tax_query'      => array(
			array(
				'taxonomy' => 'custom_taxonomy_slug',
				'operator' => 'EXISTS',
			),
		),
	)
);
