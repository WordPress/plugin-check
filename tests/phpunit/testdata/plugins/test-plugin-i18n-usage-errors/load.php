<?php
/**
 * Plugin Name: Test Plugin i18n usage with Errors for Plugin Check
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: n.e.x.t
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
