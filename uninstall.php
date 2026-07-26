<?php
/**
 * Uninstall handler for Plugin Check.
 *
 * Runs whenever a WordPress administrator deletes the Plugin Check plugin
 * from the admin Plugins screen. If a runtime environment is still in place
 * from a previous, possibly interrupted, runtime check (tables prefixed with
 * `pc_` and the bundled `object-cache.php` drop-in), clean it up here as a
 * safety net.
 *
 * @since 2.1.0
 * @package plugin-check
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if ( ! file_exists( $autoload ) ) {
	return;
}

require_once $autoload;

if ( ! class_exists( \WordPress\Plugin_Check\Checker\Runtime_Environment_Setup::class ) ) {
	return;
}

\WordPress\Plugin_Check\Checker\Runtime_Environment_Setup::cleanup_if_set_up();
