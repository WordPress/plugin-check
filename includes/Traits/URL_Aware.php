<?php
/**
 * Class WordPress\Plugin_Check\Traits\URL_Aware
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * URL Aware trait.
 *
 * @since n.e.x.t
 */
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
	 * List of relevant WP global variables to modify.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private $wp_globals = array(
		'wp_query',
		'wp_the_query',
		'wp',
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
			'get'    => $_GET, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'post'   => $_POST, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'server' => $_SERVER,
		);

		$global_vars = array();
		$global_keys = array_merge( $this->query_globals, $this->wp_globals );

		foreach ( $global_keys as $query_global ) {
			if ( isset( $GLOBALS[ $query_global ] ) ) {
				$global_vars[ $query_global ] = $GLOBALS[ $query_global ];
			}
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
		$global_keys = array_merge( $this->query_globals, $this->wp_globals );

		foreach ( $global_keys as $query_global ) {
			if ( isset( $global_vars[ $query_global ] ) ) {
				$GLOBALS[ $query_global ] = $global_vars[ $query_global ];
			} else {
				unset( $GLOBALS[ $query_global ] );
			}
		}
	}

	/**
	 * Sets the global state to as if a given URL has been requested.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $url URL to simulate request for.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
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

		$parts = wp_parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = isset( $parts['path'] ) ? $parts['path'] : '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// Parse the URL query vars into $_GET.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

		// Clean up query vars.
		foreach ( $GLOBALS['wp']->public_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}

		foreach ( $GLOBALS['wp']->private_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}

		// Set up query vars for taxonomies and post types.
		foreach ( get_taxonomies( array(), 'objects' ) as $t ) {
			if ( $t->publicly_queryable && ! empty( $t->query_var ) ) {
				$GLOBALS['wp']->add_query_var( $t->query_var );
			}
		}

		foreach ( get_post_types( array(), 'objects' ) as $t ) {
			if ( is_post_type_viewable( $t ) && ! empty( $t->query_var ) ) {
				$GLOBALS['wp']->add_query_var( $t->query_var );
			}
		}

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
