<?php
/**
 * Plugin Name: Premium Only Function Clean Test
 * Description: Test plugin containing standard functions without __premium_only suffix.
 * Author: Monzur Alam
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function poft_register_settings() {
	register_setting( 'poft_settings', 'poft_enabled' );
}

function poft_admin_notice() {
	echo '<div class="notice notice-info"><p>Standard test notice.</p></div>';
}
