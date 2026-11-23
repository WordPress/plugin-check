<?php

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Security\Private_Upload_Protection_Check;
use WordPress\Plugin_Check\Test_Data\Test_Private_Upload_Protection_Check;

class Private_Upload_Protection_Check_Tests extends WP_UnitTestCase {

	/**
	 * @dataProvider data_upload_protection
	 */
	public function test_upload_protection( $files, $expected ) {
		$check  = new Test_Private_Upload_Protection_Check();
		$base   = UNIT_TESTS_PLUGIN_DIR . 'test-plugin-private-upload-protection/';
		$result = new Check_Result( new Check_Context( $base . 'generated-apache.php' ) );
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
		$this->assertSame( $expected, $result->get_warning_count() );
		foreach ( $result->get_warnings() as $file => $lines ) {
			$this->assertContains( $file, $files );
			foreach ( $lines as $line => $columns ) {
				$this->assertGreaterThan( 0, $line );
				$this->assertStringContainsString( 'file_put_contents', file( $base . $file )[ $line - 1 ] );
				foreach ( $columns as $messages ) {
					foreach ( $messages as $message ) {
						$this->assertSame( 'potentially_unprotected_private_uploads', $message['code'] );
						$this->assertSame( $check->get_documentation_url(), $message['docs'] );
						$this->assertStringContainsString( 'does not establish that files are exposed', $message['message'] );
					}
				}
			}
		}
	}

	public static function data_upload_protection() {
		return array(
			'apache-limit'                 => array( array( 'apache-limit.php' ), 0 ),
			'apache-limitexcept'           => array( array( 'apache-limitexcept.php' ), 0 ),
			'apache-unclosed-limit'        => array( array( 'apache-unclosed-limit.php' ), 0 ),
			'apache-global-after-limit'    => array( array( 'apache-global-after-limit.php' ), 1 ),
			'apache-commented-limit'       => array( array( 'apache-commented-limit.php' ), 1 ),
			'iis-deny-restricted-verbs'    => array( array( 'iis-deny-restricted-verbs.php' ), 0 ),
			'help-with-write'              => array( array( 'help-with-write.php' ), 0 ),
			'named-arguments-limitation'   => array( array( 'named-arguments-limitation.php' ), 0 ),
			'upload-callable-reference'    => array( array( 'upload-callable-reference.php' ), 0 ),
			'first-class-callable'         => array( array( 'first-class-callable.php' ), 0 ),
			'dynamic-call'                 => array( array( 'dynamic-call.php' ), 0 ),
			'interpolated-rule'            => array( array( 'interpolated-rule.php' ), 0 ),
			'no-execution'                 => array( array( 'no-execution.php' ), 1 ),
			'custom-upload'                => array( array( 'custom-upload.php' ), 1 ),
			'get-upload-dir'               => array( array( 'get-upload-dir.php' ), 1 ),
			'literal-concatenation'        => array( array( 'literal-concatenation.php' ), 1 ),
			'apache-comments'              => array( array( 'apache-comments.php' ), 0 ),
			'apache-filesmatch'            => array( array( 'apache-filesmatch.php' ), 0 ),
			'apache-files'                 => array( array( 'apache-files.php' ), 0 ),
			'apache-unclosed-files'        => array( array( 'apache-unclosed-files.php' ), 0 ),
			'apache-global-after-files'    => array( array( 'apache-global-after-files.php' ), 1 ),
			'apache-crlf'                  => array( array( 'apache-crlf.php' ), 1 ),
			'apache-help-text'             => array( array( 'apache-help-text.php' ), 0 ),
			'unrelated-strings'            => array( array( 'unrelated-strings.php' ), 0 ),
			'separate-writes'              => array( array( 'separate-writes.php' ), 0 ),
			'wrong-basename'               => array( array( 'wrong-basename.php' ), 0 ),
			'dynamic-basename'             => array( array( 'dynamic-basename.php' ), 0 ),
			'dynamic-rule'                 => array( array( 'dynamic-rule.php' ), 0 ),
			'method-write'                 => array( array( 'method-write.php' ), 0 ),
			'static-write'                 => array( array( 'static-write.php' ), 0 ),
			'namespaced-write'             => array( array( 'namespaced-write.php' ), 0 ),
			'no-upload-write'              => array( array( 'no-upload-write.php' ), 0 ),
			'iis-comment'                  => array( array( 'iis-comment.php' ), 0 ),
			'iis-php-location'             => array( array( 'iis-php-location.php' ), 0 ),
			'iis-add-deny'                 => array( array( 'iis-add-deny.php' ), 1 ),
			'iis-specific-user'            => array( array( 'iis-specific-user.php' ), 0 ),
			'iis-restricted-verbs'         => array( array( 'iis-restricted-verbs.php' ), 0 ),
			'wrong-config-format'          => array( array( 'wrong-config-format.php' ), 0 ),
			'rewrite-rule-limitation'      => array( array( 'rewrite-rule-limitation.php' ), 0 ),
			'variable-rule-limitation'     => array( array( 'variable-rule-limitation.php' ), 0 ),
			'multiple-writes'              => array( array( 'multiple-writes.php' ), 2 ),
			'write-declaration'            => array( array( 'write-declaration.php' ), 0 ),
			'cross-file upload'            => array( array( 'upload.php', 'no-upload-write.php' ), 0 ),
			'declarations with real write' => array( array( 'not-upload-calls.php' ), 0 ),
			'generated Apache rules'       => array( array( 'generated-apache.php' ), 1 ),
			'generated IIS rules'          => array( array( 'generated-iis.php' ), 1 ),
			'bundled Apache rules'         => array( array( 'upload.php', '.htaccess' ), 0 ),
			'bundled IIS rules'            => array( array( 'upload.php', 'web.config' ), 0 ),
			'config without uploads'       => array( array( '.htaccess' ), 0 ),
			'uploads without config'       => array( array( 'upload.php' ), 0 ),
			'comments only'                => array( array( 'comments.php' ), 0 ),
			'methods and declarations'     => array( array( 'not-upload-calls.php', '.htaccess' ), 0 ),
			'no denial rule'               => array( array( 'no-denial.php' ), 0 ),
			'fully qualified upload'       => array( array( 'fully-qualified.php' ), 1 ),
			'namespaced function'          => array( array( 'namespaced.php', '.htaccess' ), 0 ),
			'escaped IIS rules'            => array( array( 'escaped-iis.php' ), 1 ),
			'anonymous IIS denial'         => array( array( 'anonymous-iis.php' ), 1 ),
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
		$this->assertSame( 16, $result->get_warning_count() );
	}
}
