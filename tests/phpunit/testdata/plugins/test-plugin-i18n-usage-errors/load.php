<?php
/**
 * Plugin Name: Test Plugin i18n usage with Errors for Plugin Check
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-check-errors
 *
 * @package test-plugin-check-errors
 */

/**
 * File contains errors related to i18n translation issues.
 */

$city = 'Surat';

// This will cause a WordPress.WP.I18n.MissingTranslatorsComment error as it has no translators comment.
sprintf(
	__( 'Your city is %s.', 'test-plugin-check-errors' ),
	$city
);

$text_domain = 'test-plugin-check-errors';

// This will cause a WordPress.WP.I18n.NonSingularStringLiteralDomain error as a variable is used for the text-domain.
esc_html__( 'Hello World!', $text_domain );

esc_html__( 'Hello World!', 'textdomain' ); // Restricted textdomain. Severity should be 7.
esc_html__( 'Hello World!', 'woocommerce' ); // Severity should be default 5.

// Non singular string literals.
echo esc_html__( $test, 'test-plugin-i18n-usage-errors' );
echo _n( $single, $plural, $number, 'test-plugin-i18n-usage-errors' );
echo _n_noop( $single, $plural, 'test-plugin-i18n-usage-errors' );
echo _x( $text, $context, 'test-plugin-i18n-usage-errors' );

// Interpolated variables.
echo esc_html__( "${text}", 'test-plugin-i18n-usage-errors' );
echo _n( "${single}", "${plural}", $number, 'test-plugin-i18n-usage-errors' );
echo _n_noop( "${single}", "${plural}", 'test-plugin-i18n-usage-errors' );
echo _x( "${text}", "${context}", 'test-plugin-i18n-usage-errors' );

// Contains restricted characters. Severity should be 7.
esc_html__( 'Hello World!', 'test_plugin_check_errors' );
esc_html__( 'Hello World!', 'test plugin check errors' );
esc_html__( 'Hello World!', 'Test-Plugin-Check_Errors' );
esc_html__( 'Hello World!', 'test,plugin-check-errors' );
