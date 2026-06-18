<?php
/**
 * Plugin Name: Test Plugin WP Functions Compatibility With function_exists Guard
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for WordPress functions compatibility check where a newer function is guarded by function_exists().
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 5.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: test-plugin-wp-functions-compatibility-with-function-exists-guard
 *
 * @package test-plugin-wp-functions-compatibility-with-function-exists-guard
 */

require_once __DIR__ . '/uses-guarded-function.php';
