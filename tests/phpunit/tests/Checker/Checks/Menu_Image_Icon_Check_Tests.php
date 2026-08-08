<?php
/**
 * Tests for the Menu_Image_Icon_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Menu_Image_Icon_Check;

class Menu_Image_Icon_Check_Tests extends WP_UnitTestCase {

	/**
	 * Test that raster image icons in add_menu_page() are detected as warnings.
	 */
	public function test_detect_images_as_menu_icons_with_errors() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-menu-image-icon-with-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Menu_Image_Icon_Check();
		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );
		// One warning per flagged icon in the fixture (png, jpg, gif, webp, ico, bmp, png?v=2).
		$this->assertSame( 7, $check_result->get_warning_count() );

		// Confirm these are warnings, not errors.
		$this->assertSame( 0, $check_result->get_error_count() );

		// Check that the warning code is correct.
		$found_menu_image_icon_warning = false;
		foreach ( $warnings['load.php'] as $line => $columns ) {
			foreach ( $columns as $column => $messages ) {
				foreach ( $messages as $message ) {
					if ( 'menu_image_icon' === $message['code'] ) {
						$found_menu_image_icon_warning = true;
						break 3;
					}
				}
			}
		}
		$this->assertTrue( $found_menu_image_icon_warning, 'Expected menu_image_icon warning code not found.' );
	}

	/**
	 * Test that dashicons, SVGs, and valid icon values do not trigger warnings.
	 */
	public function test_no_errors_for_clean_plugin() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-menu-image-icon-without-errors/load.php' );
		$check_result  = new Check_Result( $check_context );

		$check = new Menu_Image_Icon_Check();
		$check->run( $check_result );

		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $warnings );
		$this->assertSame( 0, $check_result->get_warning_count() );
		$this->assertSame( 0, $check_result->get_error_count() );
	}

	/**
	 * Test that the check returns the correct categories.
	 */
	public function test_get_categories() {
		$check      = new Menu_Image_Icon_Check();
		$categories = $check->get_categories();

		$this->assertContains( Check_Categories::CATEGORY_PLUGIN_REPO, $categories );
	}

	/**
	 * Test that the check has a description.
	 */
	public function test_get_description() {
		$check       = new Menu_Image_Icon_Check();
		$description = $check->get_description();

		$this->assertNotEmpty( $description );
		$this->assertIsString( $description );
	}

	/**
	 * Test that the check has a documentation URL.
	 */
	public function test_get_documentation_url() {
		$check = new Menu_Image_Icon_Check();
		$url   = $check->get_documentation_url();

		$this->assertNotEmpty( $url );
		$this->assertStringContainsString( 'https://', $url );
	}
}
