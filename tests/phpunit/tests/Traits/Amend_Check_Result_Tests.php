<?php
/**
 * Tests for the Amend_Check_Result trait.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;

class Amend_Check_Result_Tests extends WP_UnitTestCase {

	use Amend_Check_Result;

	/**
	 * Track callbacks to clean up in tear_down.
	 *
	 * @var callable[]
	 */
	private $cleanups = array();

	/**
	 * Symlink path inside WP_PLUGIN_DIR for test plugin fixture.
	 *
	 * @var string|null
	 */
	private $fixture_symlink = null;

	/**
	 * Whether the testdata fixture symlink is available.
	 *
	 * @var bool
	 */
	private $fixture_symlink_ready = false;

	/**
	 * Whether this test execution created the fixture symlink.
	 *
	 * @var bool
	 */
	private $fixture_symlink_created = false;

	/**
	 * Sets up test environment and fixture symlink.
	 */
	public function set_up() {
		parent::set_up();

		$this->fixture_symlink         = WP_PLUGIN_DIR . '/test-plugin-external-admin-menu-links-without-errors';
		$this->fixture_symlink_ready   = false;
		$this->fixture_symlink_created = false;
		$target                        = UNIT_TESTS_PLUGIN_DIR . 'test-plugin-external-admin-menu-links-without-errors';

		if ( is_link( $this->fixture_symlink ) ) {
			$this->fixture_symlink_ready = true;
		} elseif ( is_dir( $target ) && ! file_exists( $this->fixture_symlink ) && symlink( $target, $this->fixture_symlink ) ) {
			$this->fixture_symlink_ready   = true;
			$this->fixture_symlink_created = true;
		}
	}

	/**
	 * Cleans up filters, test environment, and removes fixture symlink if created.
	 */
	public function tear_down() {
		// Clean up fixture symlink created during set_up to prevent global state leakage.
		if ( $this->fixture_symlink_created && null !== $this->fixture_symlink && ( is_link( $this->fixture_symlink ) || file_exists( $this->fixture_symlink ) ) ) {
			unlink( $this->fixture_symlink );
		}
		$this->fixture_symlink         = null;
		$this->fixture_symlink_ready   = false;
		$this->fixture_symlink_created = false;

		foreach ( $this->cleanups as $cleanup ) {
			$cleanup();
		}
		$this->cleanups = array();

		parent::tear_down();
	}

	/**
	 * Skips the test when the fixture symlink is unavailable.
	 */
	private function require_fixture_symlink() {
		if ( ! $this->fixture_symlink_ready ) {
			$this->markTestSkipped( 'Fixture symlink unavailable; cannot place plugin fixture under WP_PLUGIN_DIR.' );
		}
	}

	/**
	 * Tests that add_result_error_for_file adds an error and increments the error count.
	 */
	public function test_add_result_error_for_file_adds_error() {
		$context = new Check_Context( 'test-plugin/test-plugin.php' );
		$result  = new Check_Result( $context );

		$this->add_result_error_for_file(
			$result,
			'Critical syntax violation',
			'syntax_error',
			'test-plugin/includes/file.php'
		);

		$this->assertSame( 1, $result->get_error_count() );
		$this->assertSame( 0, $result->get_warning_count() );
		$this->assertNotEmpty( $result->get_errors() );
		$this->assertEmpty( $result->get_warnings() );

		$errors = $result->get_errors();
		$this->assertArrayHasKey( 'includes/file.php', $errors );
		$this->assertArrayHasKey( 0, $errors['includes/file.php'] );
		$this->assertArrayHasKey( 0, $errors['includes/file.php'][0] );

		$message_data = $errors['includes/file.php'][0][0][0];
		$this->assertSame( 'Critical syntax violation', $message_data['message'] );
		$this->assertSame( 'syntax_error', $message_data['code'] );
		$this->assertSame( 5, $message_data['severity'] );
		$this->assertSame( '', $message_data['docs'] );
	}

	/**
	 * Tests that add_result_warning_for_file adds a warning and increments the warning count.
	 */
	public function test_add_result_warning_for_file_adds_warning() {
		$context = new Check_Context( 'test-plugin/test-plugin.php' );
		$result  = new Check_Result( $context );

		$this->add_result_warning_for_file(
			$result,
			'Deprecated function call',
			'deprecated_usage',
			'test-plugin/templates/view.php'
		);

		$this->assertSame( 0, $result->get_error_count() );
		$this->assertSame( 1, $result->get_warning_count() );
		$this->assertEmpty( $result->get_errors() );
		$this->assertNotEmpty( $result->get_warnings() );

		$warnings = $result->get_warnings();
		$this->assertArrayHasKey( 'templates/view.php', $warnings );
		$this->assertArrayHasKey( 0, $warnings['templates/view.php'] );
		$this->assertArrayHasKey( 0, $warnings['templates/view.php'][0] );

		$message_data = $warnings['templates/view.php'][0][0][0];
		$this->assertSame( 'Deprecated function call', $message_data['message'] );
		$this->assertSame( 'deprecated_usage', $message_data['code'] );
		$this->assertSame( 5, $message_data['severity'] );
		$this->assertSame( '', $message_data['docs'] );
	}

	/**
	 * Tests that add_result_message_for_file handles truthy and falsy values for error flag.
	 */
	public function test_add_result_message_for_file_boolean_casting() {
		$context = new Check_Context( 'test-plugin/test-plugin.php' );
		$result  = new Check_Result( $context );

		// Truthy values route to errors.
		$this->add_result_message_for_file( $result, 1, 'Error 1', 'code_1', 'test-plugin/a.php' );
		$this->add_result_message_for_file( $result, true, 'Error 2', 'code_2', 'test-plugin/b.php' );

		// Falsy values route to warnings.
		$this->add_result_message_for_file( $result, 0, 'Warning 1', 'code_3', 'test-plugin/c.php' );
		$this->add_result_message_for_file( $result, false, 'Warning 2', 'code_4', 'test-plugin/d.php' );

		$this->assertSame( 2, $result->get_error_count() );
		$this->assertSame( 2, $result->get_warning_count() );
	}

	/**
	 * Tests that add_result_message_for_file strips the plugin root path from the file parameter.
	 */
	public function test_add_result_message_for_file_strips_plugin_base_path() {
		$context = new Check_Context( 'test-plugin/test-plugin.php' );
		$result  = new Check_Result( $context );

		$plugin_path   = $context->path();
		$absolute_file = $plugin_path . 'assets/js/admin.js';

		$this->add_result_error_for_file( $result, 'Invalid JS syntax', 'invalid_js', $absolute_file );

		$errors = $result->get_errors();
		$this->assertArrayHasKey( 'assets/js/admin.js', $errors );
		$this->assertArrayNotHasKey( $absolute_file, $errors );
	}

	/**
	 * Tests that add_result_message_for_file preserves relative paths when plugin path is not present.
	 */
	public function test_add_result_message_for_file_with_relative_path() {
		$context = new Check_Context( 'test-plugin/test-plugin.php' );
		$result  = new Check_Result( $context );

		$this->add_result_warning_for_file( $result, 'Notice on readme', 'readme_notice', 'readme.txt' );

		$warnings = $result->get_warnings();
		$this->assertArrayHasKey( 'readme.txt', $warnings );
	}

	/**
	 * Tests that add_result_message_for_file sets expected defaults when optional args are omitted.
	 */
	public function test_add_result_message_for_file_default_parameters() {
		$context = new Check_Context( 'test-plugin/test-plugin.php' );
		$result  = new Check_Result( $context );

		$this->add_result_error_for_file( $result, 'Default args message', 'default_code', 'test-plugin/file.php' );

		$errors       = $result->get_errors();
		$message_data = $errors['file.php'][0][0][0];

		$this->assertSame( 5, $message_data['severity'] );
		$this->assertSame( '', $message_data['docs'] );
		$this->assertArrayHasKey( 0, $errors['file.php'] );
		$this->assertArrayHasKey( 0, $errors['file.php'][0] );
	}

	/**
	 * Tests that add_result_message_for_file properly applies custom line, column, docs, and severity.
	 */
	public function test_add_result_message_for_file_custom_parameters() {
		$context = new Check_Context( 'test-plugin/test-plugin.php' );
		$result  = new Check_Result( $context );

		$this->add_result_error_for_file(
			$result,
			'Direct DB call detected',
			'direct_db_query',
			'test-plugin/includes/db.php',
			77,
			14,
			'https://developer.wordpress.org/plugins/security/data-validation/',
			9
		);

		$errors = $result->get_errors();
		$this->assertArrayHasKey( 'includes/db.php', $errors );
		$this->assertArrayHasKey( 77, $errors['includes/db.php'] );
		$this->assertArrayHasKey( 14, $errors['includes/db.php'][77] );

		$message_data = $errors['includes/db.php'][77][14][0];
		$this->assertSame( 'Direct DB call detected', $message_data['message'] );
		$this->assertSame( 'direct_db_query', $message_data['code'] );
		$this->assertSame( 'https://developer.wordpress.org/plugins/security/data-validation/', $message_data['docs'] );
		$this->assertSame( 9, $message_data['severity'] );
	}

	/**
	 * Tests that add_result_message_for_file populates the link property via File_Editor_URL filter.
	 */
	public function test_add_result_message_for_file_populates_editor_link_when_filter_present() {
		$this->require_fixture_symlink();

		$filter = static function ( $url, $source ) {
			return 'phpstorm://open?file=' . rawurlencode( $source['file'] ) . '&line=' . (int) $source['line'];
		};
		add_filter( 'wp_plugin_check_validation_error_source_url', $filter, 10, 2 );
		$this->cleanups[] = static function () use ( $filter ) {
			remove_filter( 'wp_plugin_check_validation_error_source_url', $filter );
		};

		$fixture_main_file = WP_PLUGIN_DIR . '/test-plugin-external-admin-menu-links-without-errors/load.php';
		$context           = new Check_Context( $fixture_main_file );
		$result            = new Check_Result( $context );

		$this->add_result_error_for_file(
			$result,
			'External URL error',
			'external_url_found',
			$fixture_main_file,
			18
		);

		$errors = $result->get_errors();
		$this->assertArrayHasKey( 'load.php', $errors );

		$message_data = $errors['load.php'][18][0][0];
		$expected_url = 'phpstorm://open?file=' . rawurlencode( $fixture_main_file ) . '&line=18';
		$this->assertSame( $expected_url, $message_data['link'] );
	}

	/**
	 * Tests that add_result_message_for_file leaves link as null when user cannot edit plugins and no filter is active.
	 */
	public function test_add_result_message_for_file_link_is_null_without_permissions_or_filter() {
		wp_set_current_user( 0 );

		$context = new Check_Context( 'test-plugin/test-plugin.php' );
		$result  = new Check_Result( $context );

		$this->add_result_error_for_file(
			$result,
			'Standard error',
			'std_error',
			'test-plugin/file.php',
			10
		);

		$errors       = $result->get_errors();
		$message_data = $errors['file.php'][10][0][0];
		$this->assertNull( $message_data['link'] );
	}

	/**
	 * Tests that multiple errors and warnings across different files and coordinates accumulate correctly.
	 */
	public function test_multiple_messages_accumulate_correctly() {
		$context = new Check_Context( 'test-plugin/test-plugin.php' );
		$result  = new Check_Result( $context );

		$this->add_result_error_for_file( $result, 'Error 1', 'code_e1', 'test-plugin/f1.php', 10, 1 );
		$this->add_result_error_for_file( $result, 'Error 2', 'code_e2', 'test-plugin/f1.php', 20, 2 );
		$this->add_result_warning_for_file( $result, 'Warning 1', 'code_w1', 'test-plugin/f2.php', 30, 3 );
		$this->add_result_warning_for_file( $result, 'Warning 2', 'code_w2', 'test-plugin/f3.php', 40, 4 );

		$this->assertSame( 2, $result->get_error_count() );
		$this->assertSame( 2, $result->get_warning_count() );
		$this->assertCount( 1, $result->get_errors() );
		$this->assertCount( 2, $result->get_warnings() );
	}
}
