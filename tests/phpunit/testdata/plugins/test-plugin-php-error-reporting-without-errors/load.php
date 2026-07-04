<?php
/**
 * Plugin Name: Test Plugin - PHP Error Reporting Without Errors
 * Description: Test plugin that does not trigger warnings for changing PHP error reporting settings.
 * Version: 1.0.0
 * Author: WordPress.org
 * Text Domain: test-plugin-php-error-reporting-without-errors
 *
 * @package plugin-check
 */

// Memory limit changes are allowed.
ini_set( 'memory_limit', '256M' );

// Unaffected define statements are allowed.
define( 'MY_CUSTOM_CONSTANT', true );
