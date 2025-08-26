<?php
/**
 * Plugin Name: Test Plugin I18n Text Domain Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the I18n Text Domain check.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-i18n-text-domain-with-errors
 *
 * @package test-plugin-i18n-text-domain-with-errors
 */

// Core translations should not trigger errors.
__( 'Save Changes' );
__( 'Activate' );
_x( 'Date', 'block title' );

// Missing text domain parameters - should trigger errors.
__( 'Hello World' );
esc_html__( 'Google Fonts' );
_x( 'Custom Label', 'context description' );
