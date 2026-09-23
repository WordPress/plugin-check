<?php
/**
 * Tests for the Section class.
 *
 * @package plugin-check
 */

namespace SVN;

use WordPress\Plugin_Check\SVN\Section;
use WP_UnitTestCase;

class Section_Tests extends WP_UnitTestCase {

	public function test_constructor_sets_id_and_label() {
		$section = new Section( 'trunk', 'Trunk' );

		$this->assertSame( 'trunk', $section->id );
		$this->assertSame( 'Trunk', $section->label );
	}

	public function test_add_check_stores_check() {
		$section = new Section( 'trunk', 'Trunk' );
		$section->add_check( 'trunk_readme_found', 'readme.txt found', 'pass', 'trunk/readme.txt' );

		$this->assertSame(
			array(
				array(
					'key'    => 'trunk_readme_found',
					'label'  => 'readme.txt found',
					'status' => 'pass',
					'detail' => 'trunk/readme.txt',
				),
			),
			$section->get_checks()
		);
	}

	public function test_add_check_defaults_detail_to_empty_string() {
		$section = new Section( 'trunk', 'Trunk' );
		$section->add_check( 'trunk_readme_found', 'readme.txt found', 'pass' );

		$this->assertSame( '', $section->get_checks()[0]['detail'] );
	}

	public function test_add_check_accumulates_in_order() {
		$section = new Section( 'trunk', 'Trunk' );
		$section->add_check( 'check_one', 'Check One', 'pass' );
		$section->add_check( 'check_two', 'Check Two', 'fail' );

		$checks = $section->get_checks();

		$this->assertCount( 2, $checks );
		$this->assertSame( 'check_one', $checks[0]['key'] );
		$this->assertSame( 'check_two', $checks[1]['key'] );
	}

	public function test_json_serialize() {
		$section = new Section( 'trunk', 'Trunk' );
		$section->add_check( 'trunk_readme_found', 'readme.txt found', 'pass', 'trunk/readme.txt' );

		$this->assertSame(
			array(
				'id'     => 'trunk',
				'label'  => 'Trunk',
				'checks' => $section->get_checks(),
			),
			$section->jsonSerialize()
		);
	}
}
