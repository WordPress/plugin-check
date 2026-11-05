<?php
/**
 * Plugin Name: Test Plugin WP Functions Compatibility With Non-Global Declarations
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for WordPress functions compatibility check where newer function names are only declared as a method or in a namespace.
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 5.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: test-plugin-wp-functions-compatibility-with-non-global-declarations
 *
 * @package test-plugin-wp-functions-compatibility-with-non-global-declarations
 */

require_once __DIR__ . '/class-notices.php';
require_once __DIR__ . '/anonymous-class.php';
require_once __DIR__ . '/namespaced.php';
require_once __DIR__ . '/uses-new-function.php';
