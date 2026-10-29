<?php
/**
 * Test file calling newer WordPress functions without a global polyfill.
 *
 * @package test-plugin-wp-functions-compatibility-with-non-global-declarations
 */

wp_trigger_error( __FUNCTION__, 'Message' );
wp_admin_notice( 'Message' );
