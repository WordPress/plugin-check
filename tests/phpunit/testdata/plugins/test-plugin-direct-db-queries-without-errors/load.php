<?php
/**
 * Plugin Name: Test Plugin direct DB queries without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-direct-db-queries-without-errors
 *
 * @package test-plugin-direct-db-queries-without-errors
 */

global $wpdb;

echo $wpdb->insert_id;
