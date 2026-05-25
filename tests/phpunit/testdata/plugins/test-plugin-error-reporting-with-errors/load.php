<?php
/**
 * Plugin Name: Test Plugin Error Reporting With Errors
 */

error_reporting(0);
ini_set('display_errors', 1);
ini_alter('error_reporting', '0');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('SCRIPT_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
