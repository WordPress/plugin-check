<?php
/**
 * Plugin Name: Test Plugin Credential Storage Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Credential Storage check.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-credential-storage-with-errors
 *
 * @package test-plugin-credential-storage-with-errors
 */

update_option( 'test_plugin_api_key', 'abc123' );

register_setting( 'test_plugin', 'test_plugin_access_token' );

// Metadata about a credential, not the credential itself.
update_option( 'test_plugin_token_expires', time() );
