<?php

get_option( 'connectors_ai_openai_api_key' ); // Error.
get_site_option( 'connectors_ai_anthropic_api_key' ); // Error.
get_network_option( null, 'connectors_ai_grok_api_key' ); // Error.

get_options(
	array(
		'connectors_ai_openai_api_key',
		'connectors_ai_custom_provider_api_key',
	)
); // Error.

get_options(
	array(
		"connectors_ai_openai_api_key",
	)
); // Error.

get_option( 'siteurl' ); // Good.
get_site_option( 'blogname' ); // Good.
get_network_option( null, 'admin_email' ); // Good.
