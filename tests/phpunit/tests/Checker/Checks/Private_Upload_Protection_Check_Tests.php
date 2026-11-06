<?php

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Security\Private_Upload_Protection_Check;

class Private_Upload_Protection_Check_Tests extends WP_UnitTestCase {

	public function test_upload_protection() {
		foreach ( self::data_upload_protection() as $name => $scenario ) {
			list( $files, $expected ) = $scenario;
			$check  = new Test_Private_Upload_Protection_Check();
			$result = new Check_Result( new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE ) );
			$base   = UNIT_TESTS_PLUGIN_DIR . 'test-plugin-private-upload-protection/';
			$check->inspect_files(
				$result,
				array_map(
					static function ( $file ) use ( $base ) {
						return $base . $file;
					},
					$files
				)
			);

			$this->assertSame( 0, $result->get_error_count() );
			$this->assertSame( $expected, $result->get_warning_count(), $name );
			if ( $expected ) {
				$this->assertStringContainsString( 'potentially_unprotected_private_uploads', wp_json_encode( $result->get_warnings() ) );
			}
		}
	}

	public static function data_upload_protection() {
		return array(
			'generated Apache rules'   => array( array( 'generated-apache.php' ), 1 ),
			'generated IIS rules'      => array( array( 'generated-iis.php' ), 1 ),
			'bundled Apache rules'     => array( array( 'upload.php', '.htaccess' ), 1 ),
			'bundled IIS rules'        => array( array( 'upload.php', 'web.config' ), 1 ),
			'config without uploads'   => array( array( '.htaccess' ), 0 ),
			'uploads without config'   => array( array( 'upload.php' ), 0 ),
			'comments only'            => array( array( 'comments.php' ), 0 ),
			'methods and declarations' => array( array( 'not-upload-calls.php', '.htaccess' ), 0 ),
			'no denial rule'           => array( array( 'no-denial.php' ), 0 ),
			'fully qualified upload'   => array( array( 'fully-qualified.php', '.htaccess' ), 1 ),
			'namespaced function'      => array( array( 'namespaced.php', '.htaccess' ), 0 ),
			'escaped IIS rules'        => array( array( 'escaped-iis.php' ), 1 ),
			'anonymous IIS denial'     => array( array( 'anonymous-iis.php' ), 1 ),
		);
	}

	public function test_check_is_experimental() {
		$check = new Private_Upload_Protection_Check();
		$this->assertSame( $check::STABILITY_EXPERIMENTAL, $check->get_stability() );
	}

	public function test_run_uses_the_plugin_file_list() {
		$check   = new Private_Upload_Protection_Check();
		$context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-private-upload-protection/generated-apache.php' );
		$result  = new Check_Result( $context );
		$check->run( $result );
		$this->assertSame( 0, $result->get_error_count() );
		$this->assertSame( 6, $result->get_warning_count() );
	}
}

class Test_Private_Upload_Protection_Check extends Private_Upload_Protection_Check {

	public function inspect_files( Check_Result $result, array $files ) {
		$this->check_files( $result, $files );
	}
}
