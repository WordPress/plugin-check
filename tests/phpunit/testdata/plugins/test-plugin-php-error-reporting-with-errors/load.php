<?php
/**
 * Plugin Name: Test Plugin - PHP Error Reporting With Errors
 * Description: Test plugin that triggers warnings for changing PHP error reporting settings.
 * Version: 1.0.0
 * Author: WordPress.org
 * Text Domain: test-plugin-php-error-reporting-with-errors
 *
 * @package plugin-check
 */

error_reporting( 0 );

error_reporting( E_ALL );

ini_set( 'display_errors', 1 );

ini_set( 'error_reporting', E_ALL );

ini_alter( 'display_errors', 0 );

ini_alter( 'error_reporting', E_ALL );

define( 'WP_DEBUG', true );

define( 'WP_DEBUG_LOG', true );

define( 'WP_DEBUG_DISPLAY', true );

define( 'SCRIPT_DEBUG', true );

const WP_DEBUG = true;
