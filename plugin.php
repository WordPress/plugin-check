<?php
/**
 * Plugin Name: Plugin Check
 * Description: Runs checks against a plugin to verify if things are looking good.
 * Author: WordPress.org
 * Version: 1.0
 * Text Domain: plugin-check
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace WordPressdotorg\Plugin_Check;
use WordPressdotorg\Plugin_Directory\Readme\Parser as Readme_Parser;

include __DIR__ . '/message.php';
include __DIR__ . '/checks/check-base.php';

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
		$readme_files = preg_grep( '!(^|/)readme.(txt|md)$!i', $top_level_files );
		if ( $readme_files ) {
			$args['readme'] = new Readme_Parser( array_shift( $readme_files ) );
		}
	}

	return Checks\Check_Base::run_checks( $args ) ?: true;
}
