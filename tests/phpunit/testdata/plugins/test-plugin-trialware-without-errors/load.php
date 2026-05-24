<?php
/**
 * Plugin Name: Test Plugin Trialware Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: File contains no locked built-in features.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-trialware-without-errors
 *
 * @package test-plugin-check
 */

function test_plugin_trialware_without_errors_can_export_report() {
	return array( 'report' => 'exported' );
}
