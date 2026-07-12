<?php
/**
 * Test file for PHPErrorReportingSniff.
 *
 * Each BAD line corresponds to one entry in getErrorList().
 * GOOD lines confirm the sniff ignores allowed patterns.
 */

// BAD: direct error_reporting() call - line 10.
error_reporting( 0 );

// BAD: direct error_reporting() call with E_ALL - line 13.
error_reporting( E_ALL );

// BAD: ini_set() for display_errors - line 16.
ini_set( 'display_errors', 1 );

// BAD: ini_set() for error_reporting - line 19.
ini_set( 'error_reporting', E_ALL );

// BAD: ini_alter() for display_errors - line 22.
ini_alter( 'display_errors', 0 );

// BAD: ini_alter() for error_reporting - line 25.
ini_alter( 'error_reporting', E_ALL );

// BAD: define() WP_DEBUG - line 28.
define( 'WP_DEBUG', true );

// BAD: define() WP_DEBUG_LOG - line 31.
define( 'WP_DEBUG_LOG', true );

// BAD: define() WP_DEBUG_DISPLAY - line 34.
define( 'WP_DEBUG_DISPLAY', false );

// BAD: define() SCRIPT_DEBUG - line 37.
define( 'SCRIPT_DEBUG', true );

// BAD: const declaration of WP_DEBUG - line 40.
const WP_DEBUG = true;

// BAD: multi-const declaration with WP_DEBUG after a comma - line 43.
const SOME_OTHER = 1, WP_DEBUG = true;

// GOOD: ini_set with non-string first argument must not be matched.
ini_set( some_function(), 'error_reporting' );

// GOOD: memory_limit is not an error reporting directive.
ini_set( 'memory_limit', '256M' );

// GOOD: unrelated define().
define( 'MY_CUSTOM_CONSTANT', true );

// GOOD: error_reporting() inside a string literal must be ignored.
$body = 'Call error_reporting(0) inside documentation.';
