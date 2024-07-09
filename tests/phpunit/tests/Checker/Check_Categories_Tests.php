<?php
/**
 * Tests for the Check_Categories class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Empty_Check_Repository;
use WordPress\Plugin_Check\Test_Data\Category_Check_Five;
use WordPress\Plugin_Check\Test_Data\Category_Check_Four;
use WordPress\Plugin_Check\Test_Data\Category_Check_One;
use WordPress\Plugin_Check\Test_Data\Category_Check_Seven;
use WordPress\Plugin_Check\Test_Data\Category_Check_Six;
use WordPress\Plugin_Check\Test_Data\Category_Check_Three;
use WordPress\Plugin_Check\Test_Data\Category_Check_Two;

class Check_Categories_Tests extends WP_UnitTestCase {
	/**
	 * @var Empty_Check_Repository
	 */
	protected $repository;

	public function set_up() {
		parent::set_up();
		$this->repository = new Empty_Check_Repository();
	}

	public function test_get_categories() {
		$check_categories = new Check_Categories();
		$category_slugs   = $check_categories->get_category_slugs();

		$reflection_class   = new ReflectionClass( Check_Categories::class );
		$category_constants = $reflection_class->getConstants();

		// Assert that all the CATEGORY_* constants are included in the returned categories array.
		foreach ( $category_constants as $constant_value ) {
			$this->assertContains( $constant_value, $category_slugs );
		}
	}

	public function test_filter_check_categories() {
		$check_categories = new Check_Categories();

		$custom_categories = array(
			'first_category'  => 'First Category',
			'second_category' => 'Second Category',
		);

		$filter_name = 'wp_plugin_check_categories';

		// Create a mock filter that will return our custom categories.
		add_filter(
			$filter_name,
			static function () use ( $custom_categories ) {
				return $custom_categories;
			}
		);

		$categories = $check_categories->get_categories();

		$this->assertSame( $custom_categories, $categories );

		// Remove the filter to avoid interfering with other tests.
		remove_filter(
			$filter_name,
			static function () use ( $custom_categories ) {
				return $custom_categories;
			}
		);
	}

	/**
	 * @dataProvider data_checks_by_categories
	 */
	public function test_filter_checks_by_categories( array $categories, array $all_checks, array $expected_filtered_checks ) {

		foreach ( $all_checks as $check ) {
			$this->repository->register_check( $check[0], $check[1] );
		}

		$checks = $this->repository->get_checks();

		$check_categories = new Check_Categories();
		$filtered_checks  = $check_categories->filter_checks_by_categories( $checks, $categories );

		$this->assertEquals( $expected_filtered_checks, $filtered_checks->to_map() );
	}

	public function data_checks_by_categories() {

		require TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/Checks/Category_Check_One.php';
		require TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/Checks/Category_Check_Two.php';
		require TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/Checks/Category_Check_Three.php';
		require TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/Checks/Category_Check_Four.php';
		require TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/Checks/Category_Check_Five.php';
		require TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/Checks/Category_Check_Six.php';
		require TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/Checks/Category_Check_Seven.php';

		$category_check_one   = new Category_Check_One();
		$category_check_two   = new Category_Check_Two();
		$category_check_three = new Category_Check_Three();
		$category_check_four  = new Category_Check_Four();
		$category_check_five  = new Category_Check_Five();
		$category_check_six   = new Category_Check_Six();
		$category_check_seven = new Category_Check_Seven();

		return array(
			'filter checks by general, plugin repo, and security categories' => array(
				array(
					Check_Categories::CATEGORY_GENERAL,
					Check_Categories::CATEGORY_PLUGIN_REPO,
					Check_Categories::CATEGORY_SECURITY,
				),
				array(
					array( 'Category_Check_One', $category_check_one ),
					array( 'Category_Check_Two', $category_check_two ),
					array( 'Category_Check_Three', $category_check_three ),
					array( 'Category_Check_Four', $category_check_four ),
					array( 'Category_Check_Five', $category_check_five ),
					array( 'Category_Check_Six', $category_check_six ),
				),
				array(
					'Category_Check_One'   => $category_check_one,
					'Category_Check_Two'   => $category_check_two,
					'Category_Check_Three' => $category_check_three,
					'Category_Check_Six'   => $category_check_six,
				),
			),
			'filter checks by performance category'  => array(
				array(
					Check_Categories::CATEGORY_PERFORMANCE,
				),
				array(
					array( 'Category_Check_One', $category_check_one ),
					array( 'Category_Check_Two', $category_check_two ),
					array( 'Category_Check_Three', $category_check_three ),
					array( 'Category_Check_Four', $category_check_four ),
					array( 'Category_Check_Five', $category_check_five ),
					array( 'Category_Check_Six', $category_check_six ),
				),
				array(
					'Category_Check_Four' => $category_check_four,
				),
			),
			'filter checks for multiple categories'  => array(
				array(
					Check_Categories::CATEGORY_PLUGIN_REPO,
				),
				array(
					array( 'Category_Check_One', $category_check_one ),
					array( 'Category_Check_Two', $category_check_two ),
					array( 'Category_Check_Three', $category_check_three ),
					array( 'Category_Check_Four', $category_check_four ),
					array( 'Category_Check_Five', $category_check_five ),
					array( 'Category_Check_Six', $category_check_six ),
					array( 'Category_Check_Seven', $category_check_seven ),
				),
				array(
					'Category_Check_Two'   => $category_check_two,
					'Category_Check_Seven' => $category_check_seven,
				),
			),
			'filter checks by non-existing category' => array(
				array(
					'plugin_demo',
				),
				array(
					array( 'Category_Check_One', $category_check_one ),
					array( 'Category_Check_Two', $category_check_two ),
					array( 'Category_Check_Three', $category_check_three ),
					array( 'Category_Check_Four', $category_check_four ),
					array( 'Category_Check_Five', $category_check_five ),
					array( 'Category_Check_Six', $category_check_six ),
				),
				array(),
			),
		);
	}
}
