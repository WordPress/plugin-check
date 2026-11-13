<?php

function my_plugin_save_key( $key ) {
	if ( function_exists( 'wp_set_secret' ) ) {
		return \wp_set_secret( 'my-plugin/api-key', $key );
	}

	return update_option( 'my_plugin_api_key', $key ); // No warning, early return guard. function_exists() alone is not a full check on 7.2.
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

function my_plugin_closure_in_unavailable_branch( $key ) {
	if ( ! function_exists( 'wp_set_secret' ) ) {
		add_action(
			'init',
			function () use ( $key ) {
				update_option( 'my_plugin_api_key', $key ); // Warning, a callback can run when the API is available.
			}
		);
	}
}

if ( ! function_exists( 'wp_set_secret' ) ) {
	function my_plugin_declared_in_unavailable_branch( $key ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, the guard only controls the declaration.
	}
}

function my_plugin_conditional_exit( $fail, $key ) {
	if ( wp_is_secrets_api_available() ) {
		$fail && exit;
	}
	update_option( 'my_plugin_api_key', $key ); // Warning, the exit is conditional.
}

function my_plugin_exit_statement( $key ) {
	if ( wp_is_secrets_api_available() ) {
		wp_set_secret( 'my-plugin/api-key', $key );
		exit;
	}
	update_option( 'my_plugin_api_key', $key ); // OK, early exit guard.
}

function my_plugin_throw_statement( $key ) {
	if ( wp_is_secrets_api_available() ) {
		throw new Exception( 'Stored.' );
	}
	update_option( 'my_plugin_api_key', $key ); // OK, early throw guard.
}

function my_plugin_or_branch( $force_legacy, $key ) {
	if ( ! wp_is_secrets_api_available() || $force_legacy ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, also runs with the API when $force_legacy is true.
	}
}

function my_plugin_and_branch( $legacy, $key ) {
	if ( ! wp_is_secrets_api_available() && $legacy ) {
		update_option( 'my_plugin_api_key', $key ); // OK, only runs without the API.
	}
}

function my_plugin_else_of_and( $enabled, $key ) {
	if ( wp_is_secrets_api_available() && $enabled ) {
		wp_set_secret( 'my-plugin/api-key', $key );
	} else {
		update_option( 'my_plugin_api_key', $key ); // Warning, also runs with the API when $enabled is false.
	}
}

function my_plugin_else_of_or( $skip, $key ) {
	if ( wp_is_secrets_api_available() || $skip ) {
		return;
	} else {
		update_option( 'my_plugin_api_key', $key ); // OK, only runs without the API.
	}
}

function my_plugin_early_exit_and( $enabled, $key ) {
	if ( function_exists( 'wp_set_secret' ) && $enabled ) {
		return wp_set_secret( 'my-plugin/api-key', $key );
	}
	update_option( 'my_plugin_api_key', $key ); // Warning, also runs with the API when $enabled is false.
}

function my_plugin_early_exit_or( $skip, $key ) {
	if ( function_exists( 'wp_set_secret' ) || $skip ) {
		return;
	}
	update_option( 'my_plugin_api_key', $key ); // OK, only runs without the API.
}

function my_plugin_version_before( $key ) {
	if ( version_compare( get_bloginfo( 'version' ), '7.2', '<' ) ) {
		update_option( 'my_plugin_api_key', $key ); // OK, WordPress before 7.2.
	}
}

function my_plugin_version_global( $key ) {
	global $wp_version;

	if ( version_compare( $wp_version, '7.2', '<' ) ) {
		update_option( 'my_plugin_api_key', $key ); // OK, WordPress before 7.2.
	}

	if ( version_compare( $GLOBALS['wp_version'], '7.2', 'lt' ) ) {
		update_option( 'my_plugin_password', $key ); // OK, WordPress before 7.2.
	}
}

function my_plugin_version_negated( $key ) {
	if ( ! version_compare( get_bloginfo( 'version' ), '7.2', '>=' ) ) {
		update_option( 'my_plugin_api_key', $key ); // OK, WordPress before 7.2.
	}
}

function my_plugin_version_early_exit( $key ) {
	if ( version_compare( get_bloginfo( 'version' ), '7.2', '>=' ) ) {
		return wp_set_secret( 'my-plugin/api-key', $key );
	}
	update_option( 'my_plugin_api_key', $key ); // OK, early return on 7.2 and later.
}

function my_plugin_version_newer( $key ) {
	if ( version_compare( get_bloginfo( 'version' ), '7.3', '<' ) ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, WordPress 7.2 has the API.
	}
}

function my_plugin_version_other( $key ) {
	if ( version_compare( PHP_VERSION, '7.2', '<' ) ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, not the WordPress version.
	}
}

function my_plugin_ternary( $key ) {
	function_exists( 'wp_set_secret' ) ? wp_set_secret( 'my-plugin/api-key', $key ) : update_option( 'my_plugin_api_key', $key ); // OK, fallback side.
	! wp_is_secrets_api_available() ? update_option( 'my_plugin_password', $key ) : wp_set_secret( 'my-plugin/password', $key ); // OK, fallback side.
}

function my_plugin_ternary_return( $key ) {
	return wp_is_secrets_api_available() ? wp_set_secret( 'my-plugin/api-key', $key ) : update_option( 'my_plugin_api_key', $key ); // OK, fallback side.
}

function my_plugin_ternary_wrong_side( $legacy, $key ) {
	function_exists( 'wp_set_secret' ) ? update_option( 'my_plugin_api_key', $key ) : null; // Warning, runs with the API.
	$legacy ? update_option( 'my_plugin_password', $key ) : null; // Warning, not an availability check.
	wp_is_secrets_api_available() && $legacy ? null : update_option( 'my_plugin_token', $key ); // Warning, also runs with the API when $legacy is false.
}

function my_plugin_version_greater( $key ) {
	if ( version_compare( get_bloginfo( 'version' ), '7.2', '>' ) ) {
		return wp_set_secret( 'my-plugin/api-key', $key );
	}
	update_option( 'my_plugin_api_key', $key ); // Warning, WordPress 7.2 has the API.
}

function my_plugin_version_greater_or_equal_newer( $key ) {
	if ( version_compare( get_bloginfo( 'version' ), '7.3', '>=' ) ) {
		return wp_set_secret( 'my-plugin/api-key', $key );
	}
	update_option( 'my_plugin_api_key', $key ); // Warning, WordPress 7.2 has the API.
}

function my_plugin_version_negated_newer( $key ) {
	if ( ! version_compare( get_bloginfo( 'version' ), '7.3', '>=' ) ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, WordPress 7.2 has the API.
	}
}

function my_plugin_version_greater_older( $key ) {
	if ( version_compare( get_bloginfo( 'version' ), '7.1', '>' ) ) {
		return;
	}
	update_option( 'my_plugin_api_key', $key ); // OK, only reached on WordPress 7.1 and earlier.
}

function my_plugin_compared_to_false( $key ) {
	if ( wp_is_secrets_api_available() === false ) {
		return;
	}
	update_option( 'my_plugin_api_key', $key ); // Warning, only runs when the API is available.
}

function my_plugin_fully_qualified_negation( $key ) {
	if ( ! \function_exists( 'wp_set_secret' ) ) {
		return;
	}
	update_option( 'my_plugin_api_key', $key ); // Warning, only runs when the API is available.
}

function my_plugin_fully_qualified_negated_branch( $key ) {
	if ( ! \wp_is_secrets_api_available() ) {
		update_option( 'my_plugin_api_key', $key ); // OK, only runs without the API.
	}
}

function my_plugin_paired_check_early_return( $key ) {
	if ( function_exists( 'wp_is_secrets_api_available' ) && wp_is_secrets_api_available() ) {
		return wp_set_secret( 'my-plugin/api-key', $key );
	}
	update_option( 'my_plugin_api_key', $key ); // OK, early return with the API.
}

function my_plugin_paired_check_else( $key ) {
	if ( function_exists( 'wp_is_secrets_api_available' ) && wp_is_secrets_api_available() ) {
		wp_set_secret( 'my-plugin/api-key', $key );
	} else {
		update_option( 'my_plugin_api_key', $key ); // OK, only runs without the API.
	}
}

function my_plugin_paired_check_and_flag( $enabled, $key ) {
	if ( function_exists( 'wp_is_secrets_api_available' ) && wp_is_secrets_api_available() && $enabled ) {
		return wp_set_secret( 'my-plugin/api-key', $key );
	}
	update_option( 'my_plugin_api_key', $key ); // Warning, also runs with the API when $enabled is false.
}

function my_plugin_negated_pair( $key ) {
	if ( ! function_exists( 'wp_is_secrets_api_available' ) || ! wp_is_secrets_api_available() ) {
		update_option( 'my_plugin_api_key', $key ); // OK, only runs without the API.
	}
}

function my_plugin_mixed_operators( $a, $b, $key ) {
	if ( ! wp_is_secrets_api_available() && $a || $b ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, mixed operators are not analyzed.
	}
}

function my_plugin_write_in_condition( $key ) {
	if ( update_option( 'my_plugin_api_key', $key ) || wp_is_secrets_api_available() ) { // Warning, the write runs before the check.
		return;
	}
}

function my_plugin_bitwise_or( $force_legacy, $key ) {
	if ( ! wp_is_secrets_api_available() | $force_legacy ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, the condition can be true with the API.
	}
}

function my_plugin_double_negation( $key ) {
	if ( !! wp_is_secrets_api_available() ) {
		update_option( 'my_plugin_api_key', $key ); // Warning, two negations mean the API is available.
	}
}

function my_plugin_ternary_then_or( $key ) {
	$ok = wp_is_secrets_api_available() ? ! is_wp_error( wp_set_secret( 'my-plugin/api-key', $key ) ) : false or update_option( 'my_plugin_api_key', $key ); // Warning, "or" ends the ternary.
}
