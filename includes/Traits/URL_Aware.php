<?php
/**
 * Class WordPress\Plugin_Check\Traits\URL_Aware
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

trait URL_Aware {

	/**
	 * Simulate the URL, similar to the WordPress Core test suite.
	 *
	 * @param string $url Url.
	 */
	public function go_to( $url ) {
		/*
		 * Note: the WP and WP_Query classes like to silently fetch parameters
		 * from all over the place (globals, GET, etc), which makes it tricky
		 * to run them more than once without very carefully clearing everything.
		 */
		$_GET  = array();
		$_POST = array();

		foreach ( array( 'query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow', 'current_screen' ) as $v ) {
			if ( isset( $GLOBALS[ $v ] ) ) {
				unset( $GLOBALS[ $v ] );
			}
		}

		$parts = parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = isset( $parts['path'] ) ? $parts['path'] : '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// Parse the URL query vars into $_GET.
				parse_str( $parts['query'], $_GET );
			}
		} else {
			$req = $url;
		}

		if ( ! isset( $parts['query'] ) ) {
			$parts['query'] = '';
		}

		$_SERVER['REQUEST_URI'] = $req;
		unset( $_SERVER['PATH_INFO'] );

		unset( $GLOBALS['wp_query'], $GLOBALS['wp_the_query'] );
		$GLOBALS['wp_the_query'] = new \WP_Query();
		$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];

		$public_query_vars  = $GLOBALS['wp']->public_query_vars;
		$private_query_vars = $GLOBALS['wp']->private_query_vars;

		$GLOBALS['wp']                     = new \WP();
		$GLOBALS['wp']->public_query_vars  = $public_query_vars;
		$GLOBALS['wp']->private_query_vars = $private_query_vars;

		_cleanup_query_vars();

		$GLOBALS['wp']->main( $parts['query'] );
	}

	/**
	 * Simulate all the urls like WP and run the cleanup function.
	 *
	 * @param array    $urls     An array of URLs to run.
	 * @param callable $callback Callback function to run for each URL.
	 *
	 * @return void
	 */
	public function run_for_urls( $urls, $callback ) {
		if ( ! empty( $urls ) ) {
			foreach ( $urls as $url ) {
				$this->go_to( $url );
				$callback( $url );
			}
		}
	}
}
