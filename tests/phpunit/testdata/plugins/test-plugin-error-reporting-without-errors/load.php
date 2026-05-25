<?php
/**
 * Plugin Name: Test Plugin Error Reporting Without Errors
 */

// error_reporting(0);
// ini_set('display_errors', 1);
// define('WP_DEBUG', true);

$error_reporting = 'some_val';
$ini_set = 'another_val';

// Checking constants or function names in docstrings/comments should not trigger
/**
 * ini_set('display_errors', 1);
 * define('WP_DEBUG', true);
 */
