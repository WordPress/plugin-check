<?php
/**
 * PHPUnit bootstrap file
 *
 * @package plugin-check
 */

use Composer\Autoload\ClassLoader;

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__, 2 ) );
define( 'UNIT_TESTS_PLUGIN_DIR', TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/plugins/' );

/**
 * Attempts to determine the WordPress core root from the tests root.
 *
 * @since 1.8.1
 *
 * @param string $_test_root The tests root path.
 * @return string|null The WordPress core root or null if unknown.
 */
function wp_plugin_check_get_core_root_from_tests_root( $_test_root ) {
	$_test_root = rtrim( $_test_root, '/' );

	if ( str_ends_with( $_test_root, '/tests/phpunit' ) ) {
		return dirname( $_test_root, 2 );
	}

	if ( str_ends_with( $_test_root, '/wp-tests-lib' ) ) {
		return dirname( $_test_root );
	}

	return null;
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

require_once $_test_root . '/includes/functions.php';

// If core ships the AI client, ensure the Composer autoloader doesn't register its own.
$_core_root = wp_plugin_check_get_core_root_from_tests_root( $_test_root );
if ( $_core_root && file_exists( $_core_root . '/wp-includes/ai-client/bootstrap.php' ) ) {
	foreach ( (array) spl_autoload_functions() as $autoload_function ) {
		if (
			is_array( $autoload_function ) &&
			$autoload_function[0] instanceof ClassLoader
		) {
			$autoload_function[0]->setPsr4( 'WordPress\\AiClient\\', array() );
			$autoload_function[0]->setPsr4( 'WordPress\\AI_Client\\', array() );
		}
	}
}

// Force plugin to be active.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( basename( TESTS_PLUGIN_DIR ) . '/plugin.php' ),
);

// Start up the WP testing environment.
require $_test_root . '/includes/bootstrap.php';

// Load Composer autoloader after core bootstrap to avoid core AI client conflicts.
if ( file_exists( TESTS_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
	require_once TESTS_PLUGIN_DIR . '/vendor/autoload.php';
}
