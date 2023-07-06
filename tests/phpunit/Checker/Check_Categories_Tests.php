<?php
/**
 * Tests for the Check_Categories class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Default_Check_Repository;
use WordPress\Plugin_Check\Test_Data\Category_Check_Five;
use WordPress\Plugin_Check\Test_Data\Category_Check_Four;
use WordPress\Plugin_Check\Test_Data\Category_Check_One;
use WordPress\Plugin_Check\Test_Data\Category_Check_Six;
use WordPress\Plugin_Check\Test_Data\Category_Check_Three;
use WordPress\Plugin_Check\Test_Data\Category_Check_Two;

class Check_Categories_Tests extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		$this->repository = new Default_Check_Repository();
	}

	public function test_get_categories() {
		$check_categories = new Check_Categories();
		$categories       = $check_categories->get_categories();

		// Assert that all the CATEGORY_* constants are included in the returned categories array.
		$this->assertContains( Check_Categories::CATEGORY_GENERAL, $categories );
		$this->assertContains( Check_Categories::CATEGORY_PLUGIN_REPO, $categories );
		$this->assertContains( Check_Categories::CATEGORY_SECURITY, $categories );
		$this->assertContains( Check_Categories::CATEGORY_PERFORMANCE, $categories );
		$this->assertContains( Check_Categories::CATEGORY_ACCESSIBILITY, $categories );
	}

	public function test_filter_checks_by_categories() {

		$category_check_one   = new Category_Check_One();
		$category_check_two   = new Category_Check_Two();
		$category_check_three = new Category_Check_Three();
		$category_check_four  = new Category_Check_Four();
		$category_check_five  = new Category_Check_Five();
		$category_check_six   = new Category_Check_Six();

		$this->repository->register_check( 'Category_Check_One', $category_check_one );
		$this->repository->register_check( 'Category_Check_Two', $category_check_two );
		$this->repository->register_check( 'Category_Check_Three', $category_check_three );
		$this->repository->register_check( 'Category_Check_Four', $category_check_four );
		$this->repository->register_check( 'Category_Check_Five', $category_check_five );
		$this->repository->register_check( 'Category_Check_Six', $category_check_six );

		$checks = $this->repository->get_checks();

		$categories = array(
			Check_Categories::CATEGORY_GENERAL,
			Check_Categories::CATEGORY_PLUGIN_REPO,
			Check_Categories::CATEGORY_SECURITY,
		);

		$check_categories = new Check_Categories();
		$filtered_checks  = $check_categories->filter_checks_by_categories( $checks, $categories );

		$expected_filtered_checks = array(
			'Category_Check_One'   => $category_check_one,
			'Category_Check_Two'   => $category_check_two,
			'Category_Check_Three' => $category_check_three,
			'Category_Check_Six'   => $category_check_six,
		);

		$this->assertEquals( $expected_filtered_checks, $filtered_checks );
	}

	public function test_filter_checks_by_categories_return_performance_checks() {

		$category_check_one   = new Category_Check_One();
		$category_check_two   = new Category_Check_Two();
		$category_check_three = new Category_Check_Three();
		$category_check_four  = new Category_Check_Four();
		$category_check_five  = new Category_Check_Five();
		$category_check_six   = new Category_Check_Six();

		$this->repository->register_check( 'Category_Check_One', $category_check_one );
		$this->repository->register_check( 'Category_Check_Two', $category_check_two );
		$this->repository->register_check( 'Category_Check_Three', $category_check_three );
		$this->repository->register_check( 'Category_Check_Four', $category_check_four );
		$this->repository->register_check( 'Category_Check_Five', $category_check_five );
		$this->repository->register_check( 'category_check_six', $category_check_six );

		$checks = $this->repository->get_checks();

		$categories = array(
			Check_Categories::CATEGORY_PERFORMANCE,
		);

		$check_categories = new Check_Categories();
		$filtered_checks  = $check_categories->filter_checks_by_categories( $checks, $categories );

		$expected_filtered_checks = array(
			'Category_Check_Four' => $category_check_four,
		);

		$this->assertEquals( $expected_filtered_checks, $filtered_checks );
	}

	public function test_filter_checks_by_categories_return_empty_checks() {

		$category_check_one   = new Category_Check_One();
		$category_check_two   = new Category_Check_Two();
		$category_check_three = new Category_Check_Three();
		$category_check_four  = new Category_Check_Four();
		$category_check_five  = new Category_Check_Five();
		$category_check_six   = new Category_Check_Six();

		$this->repository->register_check( 'Category_Check_One', $category_check_one );
		$this->repository->register_check( 'Category_Check_Two', $category_check_two );
		$this->repository->register_check( 'Category_Check_Three', $category_check_three );
		$this->repository->register_check( 'Category_Check_Four', $category_check_four );
		$this->repository->register_check( 'Category_Check_Five', $category_check_five );
		$this->repository->register_check( 'category_check_six', $category_check_six );

		$checks = $this->repository->get_checks();

		$categories = array( 'plugin_demo' );

		$check_categories = new Check_Categories();
		$filtered_checks  = $check_categories->filter_checks_by_categories( $checks, $categories );

		$this->assertEquals( array(), $filtered_checks );
	}
}
