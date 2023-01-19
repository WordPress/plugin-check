<?php
/**
 * Tests for the Check_Result class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Checks\I18n_Usage_Check;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;

class I18n_Usage_Check_Tests extends WP_UnitTestCase {

	protected $i18n_usage_check;

	public function set_up() {
		parent::set_up();

		$this->i18n_usage_check = new I18n_Usage_Check();
	}

	/**
	 * @covers I18n_Usage_Check::get_args()
	 */
	public function test_get_args() {

		$sniffs = $this->i18n_usage_check->get_args();

		$this->assertArrayHasKey( 'sniff', $sniffs );
		$this->assertEquals(
			array(
				'sniffs' => 'WordPress.WP.I18n',
			),
			$sniffs
		);
	}
}
