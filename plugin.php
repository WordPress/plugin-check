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

class Plugin {
	static function instance() {
		static $instance;

		return $instance ?? ( $instance = new self );
	}

	private function __construct() {
	}

	public function run( $args ) {
		if ( is_string( $args ) && is_dir( $args ) ) {
			$args = [
				'path' => $args
			];
		}

		$args = wp_parse_args(
			$args,
			[
				'path' => '',
				'slug' => '',
			]
		);

		$path = trailingslashit( $args['path'] );
		$slug = $args['slug'] ?: basename( $path );

		$readme    = false;
		$headers   = false;
		$php_files = glob( $path . '*.php' );
		foreach ( $php_files as $plugin_file ) {
			$file_headers = get_plugin_data( $plugin_file, false, false );

			if ( ! empty( $file_headers['Name'] ) ) {
				$headers = $file_headers;
				break;
			}
		}

		// TODO: Move away from glob() due to case sensitivity.
		$readme_file = glob( $path . '{readme,README}.{txt,md}', GLOB_BRACE )[0] ?? false;
		if ( $readme_file ) {
			$readme = new Readme_Parser( $readme_file );
		}

		$args = compact(
			'readme',
			'headers',
			'plugin_file',
			'path',
			'slug',
		);

		return Checks\Check_Base::run_checks( $args );
	}

}

Plugin::instance();
