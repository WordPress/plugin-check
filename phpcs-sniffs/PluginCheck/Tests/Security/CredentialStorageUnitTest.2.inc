<?php

if ( function_exists( 'wp_is_secrets_api_available' ) && wp_is_secrets_api_available() ) {
	wp_set_secret( 'my-plugin/api-key', $key );
} else {
	update_option( 'my_plugin_api_key', $key ); // OK, fallback for older WordPress.
}
