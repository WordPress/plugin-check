<?php
/**
 * Plugin Name: Test Plugin WP Functions Compatibility With Polyfill File
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for WordPress functions compatibility check where newer functions are polyfilled in a separate file.
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 5.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: test-plugin-wp-functions-compatibility-with-polyfill-file
 *
 * @package test-plugin-wp-functions-compatibility-with-polyfill-file
 */

require_once __DIR__ . '/includes/compat.php';
require_once __DIR__ . '/includes/braced-namespaces.php';
require_once __DIR__ . '/includes/commented-declaration.php';
require_once __DIR__ . '/includes/class-compat-loader.php';
require_once __DIR__ . '/uses-polyfilled-function.php';
