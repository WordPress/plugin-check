<?php

if ( function_exists( 'wp_set_secret' ) ) {
	wp_set_secret( 'my-plugin/api-key', $key );
} else {
	update_option( 'my_plugin_api_key', $key ); // OK, fallback for older WordPress.
}
