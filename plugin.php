<?php
/**
 * Plugin Name: Plugin Check
 * Plugin URI: https://github.com/10up/plugin-check/
 * Description: Runs checks against a plugin to verify the latest WordPress standards and practices.
 * Author: Plugin Review Team
 * Version: 0.2.1
 * Text Domain: plugin-check
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace WordPressdotorg\Plugin_Check;
use WordPressdotorg\Plugin_Directory\Readme\Parser as Readme_Parser;

const PLUGIN_DIR = __DIR__;

/**
 * The current version of the plugin.
 *
 * @since 1.0.0
 *
 * @var string
 */
const PLUGIN_CHECK_VERSION = '0.2.1';

include __DIR__ . '/export.php';
include __DIR__ . '/message.php';
include __DIR__ . '/checks/check-base.php';

define(
	__NAMESPACE__ . '\HAS_VENDOR',
	file_exists( __DIR__ . '/vendor/autoload.php' )
);
if ( HAS_VENDOR ) {
	include __DIR__ . '/vendor/autoload.php';
}

/**
 * Load the Administration UI.
 */
add_action( 'admin_menu', function() {
	require __DIR__ . '/admin/admin.php';
}, 1 );

/**
 * Run all checks against a plugin.
 */
function run_all_checks( $args ) {
	if ( is_string( $args ) ) {
		$args = [
			'path' => $args
		];
	}

	$args = wp_parse_args(
		$args,
		[
			'path'        => '',
			'slug'        => '',
			'readme'      => false,
			'headers'     => false,
			'plugin_file' => false,
			'files'       => [],
		]
	);

	$args['path'] = trailingslashit( $args['path'] );
	$args['slug'] = $args['slug'] ?: basename( $args['path'] );

	$top_level_files = glob( $args['path'] . '*' );

	if ( ! $args['headers'] ) {
		$php_files = preg_grep( '!\.php$!i', $top_level_files );
		foreach ( $php_files as $plugin_file ) {
			$file_headers = get_plugin_data( $plugin_file, false, false );

			if ( ! empty( $file_headers['Name'] ) ) {
				$args['headers']     = $file_headers;
				$args['plugin_file'] = $plugin_file;
				break;
			}
		}
	}

	if ( ! $args['readme'] ) {
		$readme_txt = preg_grep( '!(^|/)readme.txt$!i', $top_level_files );
		$readme_md  = preg_grep( '!(^|/)readme.md$!i', $top_level_files );
		if ( ! empty( $readme_txt ) ) {
			$args['readme'] = new Readme_Parser( end( $readme_txt ) );
		} elseif ( ! empty( $readme_md ) ) {
			$args['readme'] = new Readme_Parser( end( $readme_md ) );
		}
	}

	if ( ! $args['files'] ) {
		$args['files'] = [];
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $args['path'] ) ) as $file ) {
			if ( $file->isFile() ) {
				$args['files'][] = substr( $file->getPathname(), strlen( $args['path'] ) );
			}
		}
		sort( $args['files'] );
	}

	return Checks\Check_Base::run_checks( $args ) ?: true;
}
