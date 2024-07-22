<?php
/**
 * Tests for the Plugin_Request_Utility class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks;
use WordPress\Plugin_Check\Checker\Checks\General\I18n_Usage_Check;
use WordPress\Plugin_Check\Test_Data\Runtime_Check;
use WordPress\Plugin_Check\Test_Utils\Traits\With_Mock_Filesystem;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

class Plugin_Request_Utility_Tests extends WP_UnitTestCase {

	use With_Mock_Filesystem;

	public function tear_down() {
		// Force reset the database prefix after runner prepare method called.
		global $wpdb, $table_prefix;
		$wpdb->set_prefix( $table_prefix );
		parent::tear_down();
	}

	public function test_get_plugin_basename_from_input() {
		$plugin = Plugin_Request_Utility::get_plugin_basename_from_input( 'plugin-check' );

		$this->assertSame( plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE ), $plugin );
	}

	public function test_get_plugin_basename_from_input_with_empty_input() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Invalid plugin slug: Plugin slug must not be empty.' );

		Plugin_Request_Utility::get_plugin_basename_from_input( '' );
	}

	public function test_get_plugin_basename_from_input_with_invalid_input() {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Invalid plugin slug: Plugin with slug invalid is not installed.' );

		Plugin_Request_Utility::get_plugin_basename_from_input( 'invalid' );
	}

	public function test_initialize_runner_with_ajax() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_REQUEST['action'] = 'plugin_check_run_checks';
		$_REQUEST['plugin'] = 'plugin-check';

		Plugin_Request_Utility::initialize_runner();

		do_action( 'muplugins_loaded' );

		$runner = Plugin_Request_Utility::get_runner();

		$this->assertInstanceOf( AJAX_Runner::class, $runner );
	}

	public function test_destroy_runner_with_ajax() {
		global $wpdb, $table_prefix, $wp_actions;

		$this->set_up_mock_filesystem();

		add_filter( 'wp_doing_ajax', '__return_true' );
		$_REQUEST['action'] = 'plugin_check_run_checks';
		$_REQUEST['plugin'] = 'plugin-check';
		$_REQUEST['checks'] = array( 'runtime_check' );

		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				return array(
					'runtime_check' => new Runtime_Check(),
				);
			}
		);

		$muplugins_loaded = $wp_actions['muplugins_loaded'];
		unset( $wp_actions['muplugins_loaded'] );

		Plugin_Request_Utility::initialize_runner();

		do_action( 'muplugins_loaded' );

		// Determine if one of the Universal_Runtime_Preparation was run.
		$prepared = has_filter( 'option_active_plugins' );

		Plugin_Request_Utility::destroy_runner();

		// Determine if the cleanup function was run.
		$cleanup = ! has_filter( 'option_active_plugins' );
		$runner  = Plugin_Request_Utility::get_runner();

		$wpdb->set_prefix( $table_prefix );
		$wp_actions['muplugins_loaded'] = $muplugins_loaded;

		$this->assertTrue( $prepared );
		$this->assertTrue( $cleanup );
		$this->assertNull( $runner );
	}

	public function test_destroy_runner_with_no_runner() {
		Plugin_Request_Utility::destroy_runner();
		$runner = Plugin_Request_Utility::get_runner();

		$this->assertNull( $runner );
	}

	public function test_default_ignore_directories() {
		$expected_directories = array(
			'.git',
			'vendor',
			'node_modules',
		);

		$actual_directories = Plugin_Request_Utility::get_directories_to_ignore();

		$this->assertEquals( $expected_directories, $actual_directories );
	}

	public function test_filter_ignore_directories() {
		// Define custom directories to ignore for testing.
		$custom_ignore_directories = array(
			'custom_directory_1',
			'custom_directory_2',
		);

		// Create a mock filter that will return our custom directories to ignore.
		$filter_name = 'wp_plugin_check_ignore_directories';
		add_filter(
			$filter_name,
			static function () use ( $custom_ignore_directories ) {
				return $custom_ignore_directories;
			}
		);

		$result = Plugin_Request_Utility::get_directories_to_ignore();

		$this->assertEquals( $custom_ignore_directories, $result );

		// Remove the filter to avoid interfering with other tests.
		remove_filter(
			$filter_name,
			static function () use ( $custom_ignore_directories ) {
				return $custom_ignore_directories;
			}
		);
	}

	public function test_filter_ignore_files() {
		// Define custom files to ignore for testing.
		$custom_ignore_files = array(
			'file-1.php',
			'inc/file-2.php',
		);

		// Create a mock filter that will return our custom files to ignore.
		$filter_name = 'wp_plugin_check_ignore_files';
		add_filter(
			$filter_name,
			static function () use ( $custom_ignore_files ) {
				return $custom_ignore_files;
			}
		);

		$result = Plugin_Request_Utility::get_files_to_ignore();

		$this->assertEquals( $custom_ignore_files, $result );

		// Remove the filter to avoid interfering with other tests.
		remove_filter(
			$filter_name,
			static function () use ( $custom_ignore_files ) {
				return $custom_ignore_files;
			}
		);
	}

	public function test_plugin_without_error_for_ignore_directories() {

		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-ignore-directories/load.php' );
		$checks        = new Checks();
		$checks_to_run = array(
			new I18n_Usage_Check(),
		);

		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				return array(
					'i18n_usage_check' => new I18n_Usage_Check(),
				);
			}
		);

		// Define custom directories to ignore for testing.
		$custom_ignore_directories = array( 'custom_directory' );

		// Create a mock filter that will return our custom directories to ignore.
		$filter_name = 'wp_plugin_check_ignore_directories';
		add_filter(
			$filter_name,
			static function () use ( $custom_ignore_directories ) {
				return $custom_ignore_directories;
			}
		);

		$results = $checks->run_checks( $check_context, $checks_to_run );

		$this->assertInstanceOf( Check_Result::class, $results );
		$this->assertEmpty( $results->get_warnings() );
		$this->assertEmpty( $results->get_errors() );

		// Remove the filter to avoid interfering with other tests.
		remove_filter(
			$filter_name,
			static function () use ( $custom_ignore_directories ) {
				return $custom_ignore_directories;
			}
		);
	}

	public function test_plugin_with_error_for_ignore_directories() {

		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-ignore-directories/load.php' );
		$checks        = new Checks();
		$checks_to_run = array(
			new I18n_Usage_Check(),
		);

		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				return array(
					'i18n_usage_check' => new I18n_Usage_Check(),
				);
			}
		);

		add_filter( 'wp_plugin_check_ignore_directories', '__return_empty_array' );

		$results = $checks->run_checks( $check_context, $checks_to_run );

		$this->assertInstanceOf( Check_Result::class, $results );

		$errors = $results->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'custom_directory/error.php', $errors );
		$this->assertEquals( 2, $results->get_error_count() );

		// Check for WordPress.WP.I18n.MissingTranslatorsComment error on Line no 26 and column no at 5.
		$this->assertArrayHasKey( 11, $errors['custom_directory/error.php'] );
		$this->assertArrayHasKey( 6, $errors['custom_directory/error.php'][11] );
		$this->assertArrayHasKey( 'code', $errors['custom_directory/error.php'][11][6][0] );
		$this->assertEquals( 'WordPress.WP.I18n.MissingTranslatorsComment', $errors['custom_directory/error.php'][11][6][0]['code'] );

		// Check for WordPress.WP.I18n.NonSingularStringLiteralDomain error on Line no 33 and column no at 29.
		$this->assertArrayHasKey( 18, $errors['custom_directory/error.php'] );
		$this->assertArrayHasKey( 30, $errors['custom_directory/error.php'][18] );
		$this->assertArrayHasKey( 'code', $errors['custom_directory/error.php'][18][30][0] );
		$this->assertEquals( 'WordPress.WP.I18n.NonSingularStringLiteralDomain', $errors['custom_directory/error.php'][18][30][0]['code'] );
	}

	public function test_plugin_for_ignore_files_with_empty_filter() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-ignore-files/load.php' );
		$checks        = new Checks();
		$checks_to_run = array(
			new I18n_Usage_Check(),
		);

		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				return array(
					'i18n_usage_check' => new I18n_Usage_Check(),
				);
			}
		);

		$results = $checks->run_checks( $check_context, $checks_to_run );

		$this->assertInstanceOf( Check_Result::class, $results );

		$errors = $results->get_errors();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'bar.php', $errors );
		$this->assertArrayHasKey( 'foobar.php', $errors );
		$this->assertArrayHasKey( 'subdirectory/error.php', $errors );
		$this->assertEquals( 3, $results->get_error_count() );

		// Check for WordPress.WP.I18n.MissingTranslatorsComment error on Line no 7 and column 10.
		$this->assertArrayHasKey( 7, $errors['bar.php'] );
		$this->assertArrayHasKey( 10, $errors['bar.php'][7] );
		$this->assertArrayHasKey( 'code', $errors['bar.php'][7][10][0] );
		$this->assertEquals( 'WordPress.WP.I18n.MissingTranslatorsComment', $errors['bar.php'][7][10][0]['code'] );

		// Check for WordPress.WP.I18n.NonSingularStringLiteralText error on Line no 7 and column 13.
		$this->assertArrayHasKey( 7, $errors['foobar.php'] );
		$this->assertArrayHasKey( 13, $errors['foobar.php'][7] );
		$this->assertArrayHasKey( 'code', $errors['foobar.php'][7][13][0] );
		$this->assertEquals( 'WordPress.WP.I18n.NonSingularStringLiteralText', $errors['foobar.php'][7][13][0]['code'] );

		// Check for WordPress.WP.I18n.NonSingularStringLiteralDomain error on Line no 7 and column 29.
		$this->assertArrayHasKey( 7, $errors['subdirectory/error.php'] );
		$this->assertArrayHasKey( 29, $errors['subdirectory/error.php'][7] );
		$this->assertArrayHasKey( 'code', $errors['subdirectory/error.php'][7][29][0] );
		$this->assertEquals( 'WordPress.WP.I18n.NonSingularStringLiteralDomain', $errors['subdirectory/error.php'][7][29][0]['code'] );
	}

	public function test_plugin_for_ignore_files_with_custom_filter() {
		$check_context = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-ignore-files/load.php' );
		$checks        = new Checks();
		$checks_to_run = array(
			new I18n_Usage_Check(),
		);

		add_filter(
			'wp_plugin_check_checks',
			function ( $checks ) {
				return array(
					'i18n_usage_check' => new I18n_Usage_Check(),
				);
			}
		);

		$custom_ignore_files = array(
			'bar.php',
		);

		// Create a mock filter that will return our custom files to ignore.
		$filter_name = 'wp_plugin_check_ignore_files';
		add_filter(
			$filter_name,
			static function () use ( $custom_ignore_files ) {
				return $custom_ignore_files;
			}
		);

		$results = $checks->run_checks( $check_context, $checks_to_run );

		$this->assertInstanceOf( Check_Result::class, $results );

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$this->assertNotEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 2, $results->get_error_count() );
		$this->assertEquals( 0, $results->get_warning_count() );

		// Remove the filter to avoid interfering with other tests.
		remove_filter(
			$filter_name,
			static function () use ( $custom_ignore_files ) {
				return $custom_ignore_files;
			}
		);
	}
}
