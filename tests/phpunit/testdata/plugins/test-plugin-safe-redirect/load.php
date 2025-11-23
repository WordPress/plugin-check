<?php
/**
 * Plugin Name: Test Safe Redirect check
 * Description: A test plugin for Safe Redirect check.
 * Version: 1.0.0
 */

// These should not trigger any SafeRedirect check errors.
wp_safe_redirect( 'https://example.com' );

// This should trigger the SafeRedirect check error.
wp_redirect( 'https://example.com' );
