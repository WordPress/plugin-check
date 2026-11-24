<?php

// function_exists() on a Secrets API write function is not an availability check.
if ( function_exists( 'wp_set_secret' ) ) { // Warning.
	wp_set_secret( 'my-plugin/api-key', $key );
}

if ( ! \function_exists( '\wp_set_network_secret' ) ) { // Warning.
	update_site_option( 'my_plugin_api_key', $key ); // OK, fallback.
}

$can_store = FUNCTION_EXISTS( "WP_SET_SECRET" ); // Warning.

$stored = function_exists( 'wp_set_secret' ) ? wp_set_secret( 'my-plugin/api-key', $key ) : false; // Warning.

// A condition that also calls wp_is_secrets_api_available() is fine.
if ( function_exists( 'wp_set_secret' ) && wp_is_secrets_api_available() ) {
	wp_set_secret( 'my-plugin/api-key', $key );
}

if ( function_exists( 'wp_is_secrets_api_available' ) && wp_is_secrets_api_available() ) {
	wp_set_secret( 'my-plugin/api-key', $key );
}

// Other functions are not checked.
if ( function_exists( 'wp_get_secret' ) ) {
	$key = wp_get_secret( 'my-plugin/api-key' );
}

if ( function_exists( 'my_plugin_set_secret' ) || function_exists( $name ) ) {
	my_plugin_set_secret( $key );
}

// A string that only mentions the function is not a call.
if ( function_exists( 'wp_set_secret' ) && 'wp_is_secrets_api_available' === $check ) { // Warning.
	wp_set_secret( 'my-plugin/api-key', $key );
}
