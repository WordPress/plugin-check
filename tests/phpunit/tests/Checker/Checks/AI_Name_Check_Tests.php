<?php
namespace phpunit\tests\Checker\Checks;

use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\AI_Name_Check;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

class AI_Name_Check_Tests extends \WP_UnitTestCase {

	public function test_run_bails_early_when_ai_disabled() {
		$runner = $this->createMock( AJAX_Runner::class );
		$runner->method( 'should_use_ai_name' )->willReturn( false );

		$reflection = new \ReflectionClass( Plugin_Request_Utility::class );
		$property   = $reflection->getProperty( 'runner' );
		$property->setAccessible( true );
		$property->setValue( null, $runner );

		$check   = new AI_Name_Check();
		$context = new Check_Context( WP_PLUGIN_CHECK_MAIN_FILE );
		$result  = new Check_Result( $context );

		$check->run( $result );

		$this->assertEmpty( $result->get_errors() );
		$this->assertEmpty( $result->get_warnings() );

		$property->setValue( null, null );
	}
}
