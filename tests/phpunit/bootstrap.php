<?php
/**
 * PHPUnit bootstrap file
 *
 * @package plugin-check
 */

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__, 2 ) );
define( 'UNIT_TESTS_PLUGIN_DIR', TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/' );

if ( file_exists( TESTS_PLUGIN_DIR . '/build-phpunit/vendor/autoload.php' ) ) {
	require_once TESTS_PLUGIN_DIR . '/build-phpunit/vendor/autoload.php';
}

if ( file_exists( TESTS_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
	require_once TESTS_PLUGIN_DIR . '/vendor/autoload.php';
}

// Detect where to load the WordPress tests environment from.
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$_test_root = getenv( 'WP_TESTS_DIR' );
} elseif ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$_test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} elseif ( false !== getenv( 'WP_PHPUNIT__DIR' ) ) {
	$_test_root = getenv( 'WP_PHPUNIT__DIR' );
} elseif ( file_exists( TESTS_PLUGIN_DIR . '/../../../../../tests/phpunit/includes/functions.php' ) ) {
	$_test_root = TESTS_PLUGIN_DIR . '/../../../../../tests/phpunit';
} else { // Fallback.
	$_test_root = '/tmp/wordpress-tests-lib';
}

// Force plugin to be active.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( basename( TESTS_PLUGIN_DIR ) . '/plugin-check.php' ),
);

// Start up the WP testing environment.
require $_test_root . '/includes/bootstrap.php';
