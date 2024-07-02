<?php
/**
 * Plugin Name: Test Plugin Code Obfuscation Zend Guard Multiple Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-code-obfuscation-zendguard-multiple-errors
 *
 * @package test-plugin-code-obfuscation-zendguard-multiple-errors
 */

// This constant is defined here to prevent fatal errors from the file below.
if ( ! defined( 'Zend' ) ) {
	define( 'Zend', true );
}

/**
 * Files contain code which is used to detect Zend Guard obfuscated files.
 */
require_once __DIR__ . '/obfuscated.php';
require_once __DIR__ . '/obfuscated2.php';
