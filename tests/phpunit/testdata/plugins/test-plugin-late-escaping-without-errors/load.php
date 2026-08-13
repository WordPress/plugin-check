<?php
/**
 * Plugin Name: Test Plugin late escaping without errors for Plugin Check
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-check
 *
 * @package test-plugin-check
 */

/**
 * File contains no errors related to late escaping issues.
 */

esc_html_e( 'Hello World!', 'test-plugin-check' );

/**
 * Outputs widget markup with escaped wrapper arguments.
 *
 * @param array $args     Widget display arguments.
 * @param array $instance Widget instance settings.
 */
function test_plugin_check_widget_output( $args, $instance ) {
	$title = isset( $instance['title'] ) ? $instance['title'] : '';

	echo wp_kses_post( $args['before_widget'] );

	if ( '' !== $title ) {
		echo wp_kses_post( $args['before_title'] );
		echo esc_html( $title );
		echo wp_kses_post( $args['after_title'] );
	}

	echo '<p>' . esc_html__( 'Widget content.', 'test-plugin-check' ) . '</p>';
	echo wp_kses_post( $args['after_widget'] );
}
