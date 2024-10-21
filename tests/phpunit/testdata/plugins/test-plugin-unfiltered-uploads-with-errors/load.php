<?php
/**
 * Plugin Name: Test Plugin Unfiltered Uploads Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Localhost check.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-unfiltered-uploads-errors
 * Requires Plugins: woocommerce, contact-form-7
 *
 * @package test-plugin-unfiltered-uploads-errors
 */

// Check if the constant is defined.
if ( defined( 'ALLOW_UNFILTERED_UPLOADS' ) ) {
    return;
}
