<?php
use function WordPressdotorg\Plugin_Check\{ run_all_checks };

class PluginCheck_TestCase extends WP_UnitTestCase {
	public function run_against_string( $string, $args = [] ) {
		return $this->run_against_virtual_files(
			[
				'plugin.php' => $string
			],
			$args
		);
	}

	public function run_against_virtual_files( $files, $args = [] ) {
		$tempname = wp_tempnam( 'plugin-check' );
		unlink( $tempname );
		mkdir( $tempname );

		$dirs = [];

		foreach ( $files as $filename => $string ) {
			$full_filename = "{$tempname}/{$filename}";

			if ( str_ends_with( $filename, '.php' ) && ! str_starts_with( $string,  '<' . '?php' ) ) {
				$string = "<?php {$string}";
			}

			if ( str_contains( $filename, '/' ) ) {
				$dir = dirname( $full_filename );
				$dirs[] = $dir;
				mkdir( $dir, 0777, true );
			}

			file_put_contents( $full_filename, $string );
		}

		$dirs[] = $tempname;

		$args[ 'path' ] = $tempname;

		// Do not use the fallback of the path for the slug, as it contains a restricted trademark term.
		if ( ! isset( $args['slug'] ) ) {
			$args['slug'] = 'test-slug';
		}

		$results = run_all_checks( $args );

		// Cleanup
		foreach ( $files as $filename => $string ) {
			unlink( "{$tempname}/{$filename}" );
		}
		foreach ( $dirs as $d ) {
			rmdir( $d );
		}

		return $results;
	}

	public function assertHasErrorType( $results, $search = [] ) {
		$filtered = $this->_filterErrorType( $results, $search );

		$this->assertNotEmpty( $filtered, 'A matching error was not found: ' . json_encode( $search ) );
	}

	public function assertNotHasErrorType( $results, $search = [] ) {
		$filtered = $this->_filterErrorType( $results, $search );

		$this->assertEmpty( $filtered, 'A matching error was found: ' . json_encode( $search ) );
	}

	protected function _filterErrorType( $results, $search ) {
		$type   = $search['type'] ?? false;
		$code   = $search['code'] ?? false;
		$needle = $search['needle'] ?? false;

		if ( ! is_array( $results ) ) {
			return [];
		}

		if ( $type ) {
			$results = wp_list_filter( $results, [ 'error_class' => $type ] );
		}

		if ( ! $results ) {
			return [];
		}

		$error = new WP_Error();
		foreach ( $results as $r ) {
			$error->merge_from( $r );
		}
		$codes = $error->errors;

		if ( $code ) {
			$codes = $codes[ $code ] ?? [];
		} else {
			$codes = call_user_func_array( 'array_merge', $codes );
		}

		if ( $needle ) {
			$codes = array_filter(
				$codes,
				static function( $text ) use( $needle ) {
					return false !== strpos( $text, $needle );
				}
			);
		}

		return $codes;
	}
}
