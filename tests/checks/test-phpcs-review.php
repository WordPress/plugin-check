<?php

/**
 * @group Checks
 * @group PHPCS
 * @group PHPCS-needs-review
 */
class Test_PHPCS_Review extends PluginCheck_TestCase {
	/**
	 * @dataProvider data_forbidden_function_warnings
	 */
	public function test_forbidden_function_warnings( $function, $triggering_php ) {
		$results = $this->run_against_string( $triggering_php );

		$this->assertHasErrorType(
			$results,
			[
				'type' => 'warning',
				'code' => 'Generic.PHP.ForbiddenFunctions.Found',
				'needle' => "function {$function} is forbidden"
			]
		);
	}

	public function data_forbidden_function_warnings() {
		return [
			[ 'error_reporting()',    'error_reporting( E_ALL );' ],
			[ 'wp_create_user()',     'wp_create_user( "admin", "admin" );' ],
			[ 'hex2bin()',            'echo hex2bin( "313031" );' ],
			[ 'base64_encode()',      'echo base64_encode( "WordPress" );' ],
			[ 'base64_decode()',      'echo base64_decode( "V29yZFByZXNz" );' ],
			[ 'shell_exec()',         'echo shell_exec( "cat /etc/passwd" );' ],
			[ 'exec()',               'exec( "cat /etc/passwd" );' ],
		];
	}

}
