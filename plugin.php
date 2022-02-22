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

$_SERVER['HTTP_HOST'] = 'wordpress.org';
$_SERVER['REQUEST_URI'] = '/plugins/';
include '../../../wp-load.php';

include __DIR__ . '/message.php';
include __DIR__ . '/check-base.php';

class Plugin {
	public $checks = array();

	static function instance() {
		static $instance;
		return $instance ?? ( $instance = new self );
	}

	private function __construct() {
	}

	protected function load_checks() {
		foreach ( glob( __DIR__ . '/checks/*' ) as $file ) {
			$name  = ucwords( str_replace( ['.', '-'], ' ', basename( $file, '.php' ) ) );
			$check = include $file;

			if ( !( $check instanceof \Closure ) ) {
				$check = false;
				$class = __NAMESPACE__ . '\\Checks\\' . $name;
				if ( method_exists( $class, '__invoke' ) ) {
					$check = new $class;

					// Classes can define the test name, as either a class const or as a property.
					$name = $check->name();
				}
			}

			if ( $check ) {
				$this->checks[ $name ] = $check;
			}
		}

		return (bool) $this->checks;
	}

	public function run( $args ) {
		if ( is_string( $args ) && is_dir( $args ) ) {
			$args = [ 'path' => $args ];
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

		$this->load_checks();

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
			'readme', 'headers',
			'plugin_file',
			'path', 'slug',
		);

		$messages = [];

		foreach ( $this->checks as $name => $check ) {
			$check_messages = $check( $args );

			if ( ! $check_messages ) {
				$check_messages = new Notice( "The $name check returned false." );
			}

			if ( is_wp_error( $check_messages ) ) {
				$check_messages = [ $check_messages ];
			}

			if ( is_array( $check_messages ) ) {
				$messages = array_merge( $messages, $check_messages );
			}
		}

		return $messages;
	}

}

$check = Plugin::instance();

var_dump(
	$check->run( __DIR__ )
);