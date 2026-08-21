<?php
/**
 * Plugin Name: Premium Only Function Test
 * Description: Test plugin containing functions named with the __premium_only suffix.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function poft_register_settings__premium_only() {
	register_setting( 'poft_settings', 'poft_enabled' );
}

function poft_admin_notice__premium_only() {
	echo '<div class="notice notice-info"><p>Premium-only test notice.</p></div>';
}
