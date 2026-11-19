<?php
/**
 * Test file calling newer WordPress functions that the plugin polyfills elsewhere.
 *
 * @package test-plugin-wp-functions-compatibility-with-polyfill-file
 */

wp_trigger_error( __FUNCTION__, 'Message' );
wp_admin_notice( 'Message' );
wp_get_admin_notice( 'Message' );
wp_get_wp_version();
wp_is_development_mode( 'plugin' );
