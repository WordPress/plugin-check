<?php
/**
 * Plugin Name: Test Plugin i18n usage with default for Plugin Check
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-i18n-usage-without-errors
 *
 * @package test-plugin-check
 */

// This explicitly uses the 'default' WordPress Core text domain which should cause a warning.
esc_html__( 'Log In', 'default' );

// This omits the text domain though, which will lead to the same behavior but is not allowed and thus an error.
esc_html__( 'Log In' );
