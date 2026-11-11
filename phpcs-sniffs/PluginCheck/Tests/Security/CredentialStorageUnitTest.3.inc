<?php

function my_plugin_save_key( $key ) {
	if ( function_exists( 'wp_set_secret' ) ) {
		return \wp_set_secret( 'my-plugin/api-key', $key );
	}

	return update_option( 'my_plugin_api_key', $key ); // OK, early return guard.
}

function my_plugin_save_password( $password, $other ) {
	wp_set_secret( 'my-plugin/other', $other );
	update_option( 'my_plugin_password', $password ); // Warning, not guarded.
}

function my_plugin_upgrade() {
	wp_import_option_as_secret( 'my_plugin_license_key', 'my-plugin/license-key' );
	update_option( 'my_plugin_license_key', $license ); // Warning, import does not stop later writes.
}

function my_plugin_save_token( $token ) {
	if ( function_exists( 'wp_set_secret' ) ) {
		wp_set_secret( 'my-plugin/token', $token );
	}
	update_option( 'my_plugin_token', $token ); // Warning, always written.
}

function my_plugin_save_secret( $secret ) {
	if ( ! function_exists( 'wp_set_secret' ) ) {
		update_option( 'my_plugin_secret', $secret ); // OK, guarded.
	}
}

function my_plugin_save_network_token( $token ) {
	if ( wp_is_secrets_api_available() ) {
		wp_set_network_secret( 'my-plugin/token', $token );
	} elseif ( $legacy ) {
		$noop = true;
	} else {
		update_network_option( null, 'my_plugin_network_token', $token ); // OK, else branch of guard.
	}
}

function my_plugin_log_and_save( $pwd ) {
	error_log( 'wp_set_secret' );
	update_option( 'my_plugin_pwd', $pwd ); // Warning, a string is not a guard.
}

function my_plugin_positive_branch( $key ) {
	if ( function_exists( 'wp_set_secret' ) ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, runs when the API exists.
	}
}

function my_plugin_string_compare( $label, $key ) {
	if ( 'wp_set_secret' === $label ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, not an availability check.
	}
}

function my_plugin_nested_return( $skip, $key ) {
	if ( wp_is_secrets_api_available() ) {
		if ( $skip ) {
			return;
		}
	}
	update_option( 'my_plugin_api_key', $key ); // Warning, block does not always return.
}

function my_plugin_else_of_negated( $key ) {
	if ( ! wp_is_secrets_api_available() ) {
		$noop = true;
	} else {
		update_option( 'my_plugin_api_key', $key ); // Warning, runs when the API is available.
	}
}
