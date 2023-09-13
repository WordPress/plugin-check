<?php

/**
 * @group Checks
 * @group PHPCS
 * @group PHPCS-required
 */
class Test_PHPCS extends PluginCheck_TestCase {
	public function test_generic_php_checks() {
		$results = $this->run_against_string( '<?php
			start:
			attempt_to_escape_the_matrix();
			$whoiam = `whoami`;
			?><?= $whoiam ?>
			<% echo $whoiam; %>
			goto start;
		' );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'Generic.PHP.BacktickOperator.Found' ] );
		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'Generic.PHP.DisallowShortOpenTag.EchoFound' ] );
		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'Generic.PHP.DiscourageGoto.Found' ] );

		// This is a warning-level, as PHPCS appears not to be 100% sure when it spots it.
		$this->assertHasErrorType( $results, [ 'type' => 'warning', 'code' => 'Generic.PHP.DisallowAlternativePHPTags.MaybeASPOpenTagFound' ] );
	}

	/**
	 * @dataProvider data_forbidden_function_warnings
	 */
	public function test_forbidden_function_warnings( $function, $triggering_php ) {
		$results = $this->run_against_string( $triggering_php );

		$this->assertHasErrorType(
			$results,
			[
				'type' => 'error',
				'code' => 'Generic.PHP.ForbiddenFunctions.Found',
				'needle' => "function {$function} is forbidden"
			]
		);
	}

	public function data_forbidden_function_warnings() {
		return [
			[ 'create_function()',    'create_function( "example", "return 123;" );' ],
			[ 'eval()',               'eval( $_POST["cmd"] );' ],
			[ 'str_rot13()',          'echo str_rot13( "JbeqCerff" );' ],
			[ 'move_uploaded_file()', 'move_uploaded_file( $a, $b );' ],
			[ 'passthru()',           'passthru( $cmd );' ],
			[ 'proc_open()',          'proc_open( $cmd, $descriptors, $pipes );' ],
		];
	}

}
