<?php
/**
 * Tests for the URL_Utils trait.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Traits\URL_Utils;

class URL_Utils_Tests extends WP_UnitTestCase {

	use URL_Utils;

	/**
	 * @dataProvider data_url_items
	 */
	public function test_url_validation( $url, $is_valid ) {
		$result = $this->is_valid_url( $url );
		$this->assertSame( $is_valid, $result );
	}

	public function data_url_items() {
		return array(
			array( 'http://example.com/', true ),
			array( 'https://www.example.com', true ),
			array( 'https://example.com/page.html', true ),
			array( 'https://http://example.com/', false ),
			array( 'ftp://example.com/file.txt', false ),
			array( 'HTTP://example.com/', true ),
			array( 'HtTpS://example.com/', true ),
			array( 'httpx://example.com/', false ),
			array( 'httpfoo://example.com/', false ),
			// PayPal donation URLs with complex query strings.
			array( 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=developer@gmail.com&item_name=WordPress%20Plugin%20Donation&return=https://wordpress.org/plugins/my-plugin/', true ),
			array( 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=test@example.com&item_name=Support%20My%20Plugin', true ),
			array( 'https://paypal.me/username/5USD', true ),
			array( 'https://www.paypal.com/donate/?hosted_button_id=123456', true ),
		);
	}

	/**
	 * @dataProvider data_discouraged_domain_urls
	 */
	public function test_has_discouraged_domain( $url, $expected ) {
		$result = $this->has_discouraged_domain( $url );
		$this->assertSame( $expected, $result );
	}

	public function data_discouraged_domain_urls() {
		return array(
			array( 'http://example.com/', true ),
			array( 'https://example.com', true ),
			array( 'https://example.org', true ),
			array( 'http://example.net/page', true ),
			array( 'http://yourwebsite.com', true ),
			array( 'http://sub.example.com/', true ),
			array( 'http://www.example.com/', true ),
			array( 'https://www.example.com/', true ),
			array( 'https://www.example.org', true ),
			array( 'http://www.example.net/page', true ),
			array( 'http://www.yourwebsite.com', true ),
			array( 'http://notexample.com/', false ),
			array( 'http://example.co.uk/', false ),
			array( 'http://myexample.com/', false ),
			array( 'http://sub.notexample.com/', false ),
		);
	}
}
