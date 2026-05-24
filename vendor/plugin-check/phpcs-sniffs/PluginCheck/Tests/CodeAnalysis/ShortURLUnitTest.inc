<?php

/* testShortUrlInHtmlTag */
$link = '<a href="https://adf.ly/abc123">Click here</a>';

/* testShortUrlInSingleQuotedString */
$url = 'https://bit.ly/abc123';

/* testShortUrlInDoubleQuotedString */
$url = "https://tinyurl.com/xyz789";

/**
 * Test function with short URL in PHPDoc.
 *
 * @param string $link Visit https://goo.gl/test for more info.
 * @return void
 */
/* testShortUrlInPhpDocComment */
function test_function() {
}

// Visit https://is.gd/example for details.
/* testShortUrlInRegularComment */

/* testShortUrlInHeredoc */
$message = <<<EOD
Check out this link: https://cutt.ly/short
EOD;

/* testShortUrlInNowdoc */
$content = <<<'NOWDOC'
Documentation available at https://is.gd/doc123
NOWDOC;

/* testShortUrlInFileGetContents */
$response = file_get_contents( 'http://tinyurl.com/api-create.php?url=' . $target_url );

/* testShortUrlInArrayValue */
$plugin_links = array(
	'documentation' => esc_url( 'https://goo.gl/docs123' ),
	'support'       => 'https://rb.gy/support456',
);

/* testShortUrlInEchoStatement */
echo '<a href="https://bit.ly/plugin123" class="button">Get Plugin</a>';

/* testShortUrlInPhpDocLink */
/**
 * Plugin settings configuration.
 *
 * @link https://clck.ru/settings For more information about settings.
 * @since 1.0.0
 */
function configure_plugin() {
}

/* testShortUrlInInlineHtml */
printf( '<div>Visit <a href="%s">our site</a> for details.</div>', esc_url( 'https://bit.do/plugin456' ) );

/* testShortUrlInMultipleUrls */
echo esc_html__( 'Check this', 'my-plugin' ) . ' <a href="https://bit.ly/link1">link</a> ' . esc_html__( 'or this', 'my-plugin' ) . ' <a href="https://lc.chat/link2">other link</a>';

/* testShortUrlInVariableAssignment */
$api_response = wp_remote_get( 'https://s2r.co/api-endpoint' );

/* testShortUrlInPhpDocDescription */
/**
 * Process API request.
 *
 * See configuration guide at https://polr.me/config123 for setup instructions.
 *
 * @return void
 */
function process_api() {
}

