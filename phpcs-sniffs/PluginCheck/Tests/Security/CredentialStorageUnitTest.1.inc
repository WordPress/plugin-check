<?php

update_option( 'my_plugin_api_key', $key ); // Warning.
add_option( 'my_plugin_apikey', $key ); // Warning.
update_site_option( 'my_plugin_client_secret', $secret ); // Warning.
add_site_option( 'my_plugin_access_token', $token ); // Warning.
update_network_option( null, 'my_plugin_refresh_token', $token ); // Warning.
add_network_option( null, 'my_plugin_password', $pass ); // Warning.
register_setting( 'my_plugin', 'my_plugin_smtp_pwd' ); // Warning.
update_option( "my_plugin_private_key", $key ); // Warning.
update_option( 'MY_PLUGIN_LICENSE_KEY', $key ); // Warning.
update_option( 'my-plugin-api-key', base64_encode( $key ) ); // Warning.
update_option( value: $key, option: 'my_plugin_api_secret' ); // Warning.
register_setting( option_name: 'my_plugin_passwd', option_group: 'my_plugin' ); // Warning.

update_option( 'my_plugin_token_expires', time() ); // OK, metadata.
update_option( 'my_plugin_api_key_set', true ); // OK, metadata.
update_option( 'my_plugin_key_valid', true ); // OK, no credential pattern.
update_option( 'my_plugin_api_key_valid', true ); // OK, metadata.
update_option( 'my_plugin_settings', $settings ); // OK.
update_option( $option_name, $value ); // OK, dynamic name.
update_option( 'my_plugin_' . $type . '_token', $value ); // OK, dynamic name.
get_option( 'my_plugin_api_key' ); // OK, read only.
$obj->update_option( 'my_plugin_api_key', $key ); // OK, method call.
update_option( 'my_plugin_smtp_pass', $pass ); // Warning.
update_site_option( 'my_plugin_network_token', $token ); // Warning, network message.
update_option( 'my_plugin_tokenizer', $value ); // OK, not a whole name part.
update_option( 'my_plugin_passwordless_enabled', true ); // OK, not a whole name part.
update_option( 'my_plugin_token_expired', true ); // OK, metadata.
update_option( 'my_plugin_api_key_last4', '1234' ); // OK, metadata.
update_option( "my_plugin_{$slug}_token", $token ); // OK, interpolated name.
