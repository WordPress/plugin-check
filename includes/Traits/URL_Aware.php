<?php
/**
 * Class WordPress\Plugin_Check\Traits\URL_Aware
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

trait URL_Aware {

	/**
	 * List of relevant global query variables to modify.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private $query_globals = array(
		'query_string',
		'id',
		'postdata',
		'authordata',
		'day',
		'currentmonth',
		'page',
		'pages',
		'multipage',
		'more',
		'numpages',
		'pagenow',
		'current_screen',
	);

	/**
	 * Array of original values for the global state.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private $global_values = array(
		'get'         => array(),
		'post'        => array(),
		'server'      => array(),
		'global_vars' => array(),
	);

	/**
	 * Backups the original values for any global state that may be modified to be restored later.
	 *
	 * @since n.e.x.t
	 */
	protected function backup_globals() {
		$this->global_values = array(
			'get'    => $_GET,
			'post'   => $_POST,
			'server' => $_SERVER,
		);

		$global_vars = array();

		foreach ( $this->query_globals as $query_global ) {
			if ( isset( $GLOBALS[ $query_global ] ) ) {
				$global_vars[ $query_global ] = $GLOBALS[ $query_global ];
			}
		}
		if ( isset( $GLOBALS['wp_query'] ) ) {
			$global_vars['wp_query'] = $GLOBALS['wp_query'];
		}
		if ( isset( $GLOBALS['wp_the_query'] ) ) {
			$global_vars['wp_the_query'] = $GLOBALS['wp_the_query'];
		}
		if ( isset( $GLOBALS['wp'] ) ) {
			$global_vars['wp'] = $GLOBALS['wp'];
		}

		$this->global_values['global_vars'] = $global_vars;
	}

	/**
	 * Restores the original values for any global state that may have been modified.
	 *
	 * @since n.e.x.t
	 */
	protected function restore_globals() {
		$_GET    = $this->global_values['get'];
		$_POST   = $this->global_values['post'];
		$_SERVER = $this->global_values['server'];

		$global_vars = $this->global_values['global_vars'];

		foreach ( $this->query_globals as $query_global ) {
			if ( isset( $global_vars[ $query_global ] ) ) {
				$GLOBALS[ $query_global ] = $global_vars[ $query_global ];
			} else {
				unset( $GLOBALS[ $query_global ] );
			}
		}
		if ( isset( $global_vars['wp_query'] ) ) {
			$GLOBALS['wp_query'] = $global_vars['wp_query'];
		} else {
			unset( $GLOBALS['wp_query'] );
		}
		if ( isset( $global_vars['wp_the_query'] ) ) {
			$GLOBALS['wp_the_query'] = $global_vars['wp_the_query'];
		} else {
			unset( $GLOBALS['wp_the_query'] );
		}
		if ( isset( $global_vars['wp'] ) ) {
			$GLOBALS['wp'] = $global_vars['wp'];
		} else {
			unset( $GLOBALS['wp'] );
		}
	}

	/**
	 * Sets the global state to as if a given URL has been requested.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $url URL to simulate request for.
	 */
	protected function go_to( $url ) {
		/*
		 * Note: the WP and WP_Query classes like to silently fetch parameters
		 * from all over the place (globals, GET, etc), which makes it tricky
		 * to run them more than once without very carefully clearing everything.
		 */
		$_GET  = array();
		$_POST = array();

		foreach ( $this->query_globals as $v ) {
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
	 * @since n.e.x.t
	 *
	 * @param array    $urls     An array of URLs to run.
	 * @param callable $callback Callback function to run for each URL.
	 */
	protected function run_for_urls( $urls, $callback ) {
		if ( ! empty( $urls ) ) {
			foreach ( $urls as $url ) {
				$this->go_to( $url );
				$callback( $url );
			}
		}
	}
}
