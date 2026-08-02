<?php
/**
 * Tests for the File_Editor_URL trait.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\File_Editor_URL;

class File_Editor_URL_Tests extends WP_UnitTestCase {

	use File_Editor_URL;

	/**
	 * URL filter callbacks registered by each test, removed in tear_down.
	 *
	 * Anonymous closures cannot be removed via remove_filter without the callable
	 * reference, so each test stores every callback it added and removes them by reference.
	 *
	 * @var callable[]
	 */
	private $url_callbacks = array();

	/**
	 * User cap filter callbacks registered by each test, removed in tear_down.
	 *
	 * @var callable[]
	 */
	private $cap_callbacks = array();

	/**
	 * User IDs granted super-admin status, so tear_down can revoke them.
	 *
	 * Multisite `map_meta_cap('edit_plugins')` requires `is_super_admin()`,
	 * which a `user_has_cap` filter bypass cannot satisfy.
	 *
	 * @var int[]
	 */
	private $super_admin_ids = array();

	/**
	 * Legacy filter callbacks registered by each test, removed in tear_down.
	 *
	 * @var array[]
	 */
	private $legacy_callbacks = array();

	/**
	 * Symlink path inside WP_PLUGIN_DIR for the testdata fixture.
	 *
	 * @var string|null
	 */
	private $fixture_symlink = null;

	/**
	 * Whether the testdata fixture symlink could be created inside WP_PLUGIN_DIR.
	 *
	 * @var bool
	 */
	private $fixture_symlink_ready = false;

	/**
	 * Sets up the test fixture symlink so file_exists passes inside wp-env.
	 */
	public function set_up() {
		parent::set_up();

		$this->fixture_symlink = WP_PLUGIN_DIR . '/test-plugin-external-admin-menu-links-without-errors';
		$target                = UNIT_TESTS_PLUGIN_DIR . 'test-plugin-external-admin-menu-links-without-errors';

		if ( is_link( $this->fixture_symlink ) ) {
			$this->fixture_symlink_ready = true;
		} elseif ( is_dir( $target ) && ! file_exists( $this->fixture_symlink ) && symlink( $target, $this->fixture_symlink ) ) {
			$this->fixture_symlink_ready = true;
		}
	}

	/**
	 * Skips the test when the fixture symlink could not be created.
	 *
	 * The symlink is required so the trait's `file_exists()` check passes for the
	 * external-editor filter branch. Without it the related tests error out in a
	 * confusing way instead of skipping cleanly.
	 */
	private function require_fixture_symlink() {
		if ( ! $this->fixture_symlink_ready ) {
			$this->markTestSkipped( 'Fixture symlink unavailable; cannot place plugin fixture under WP_PLUGIN_DIR.' );
		}
	}

	/**
	 * Removes the test fixture symlink and filter callbacks.
	 */
	public function tear_down() {
		// Remove symlink created in set_up.
		if ( null !== $this->fixture_symlink && file_exists( $this->fixture_symlink ) && is_link( $this->fixture_symlink ) ) {
			unlink( $this->fixture_symlink );
		}
		$this->fixture_symlink = null;

		foreach ( $this->url_callbacks as $cb ) {
			remove_filter( 'wp_plugin_check_validation_error_source_url', $cb, 10 );
		}
		$this->url_callbacks = array();

		foreach ( $this->legacy_callbacks as $legacy ) {
			remove_filter( $legacy['hook'], $legacy['callback'], 10 );
		}
		$this->legacy_callbacks = array();

		remove_filter( 'deprecated_hook_trigger_error', '__return_false' );

		foreach ( $this->cap_callbacks as $cb ) {
			remove_filter( 'user_has_cap', $cb, 10 );
		}
		$this->cap_callbacks = array();

		foreach ( $this->super_admin_ids as $id ) {
			revoke_super_admin( $id );
		}
		$this->super_admin_ids = array();

		parent::tear_down();
	}

	/**
	 * Absolute path to the single-file test plugin fixture (load.php in fixture dir).
	 *
	 * @return string
	 */
	private function single_file_plugin_basename() {
		return WP_PLUGIN_DIR . '/test-plugin-external-admin-menu-links-without-errors/load.php';
	}

	/**
	 * Registers a url filter callback and tracks it for cleanup.
	 *
	 * @param callable $callback      Filter callback.
	 * @param int      $accepted_args Number of args the callback accepts.
	 */
	private function add_url_filter( $callback, $accepted_args = 1 ) {
		add_filter( 'wp_plugin_check_validation_error_source_url', $callback, 10, $accepted_args );
		$this->url_callbacks[] = $callback;
	}

	/**
	 * Registers a legacy (deprecated) filter callback and tracks it for cleanup.
	 *
	 * @param string   $hook     Legacy filter hook.
	 * @param callable $callback Filter callback.
	 * @param int      $args     Number of accepted args.
	 */
	private function add_legacy_filter( $hook, $callback, $args = 1 ) {
		add_filter( $hook, $callback, 10, $args );
		$this->legacy_callbacks[] = array(
			'hook'     => $hook,
			'callback' => $callback,
		);
	}

	/**
	 * Registers a user_has_cap callback and tracks it for cleanup.
	 *
	 * @param callable $callback Filter callback.
	 */
	private function add_cap_filter( $callback ) {
		add_filter( 'user_has_cap', $callback, 10, 1 );
		$this->cap_callbacks[] = $callback;
	}

	/**
	 * Sets the current user so `current_user_can('edit_plugins')` returns true.
	 *
	 * On multisite, WP core requires `is_super_admin()` for the `edit_plugins`
	 * cap (see WP capabilities.php `map_meta_cap`). A `user_has_cap` filter
	 * bypasses that check on single-site only, so the test must escalate the
	 * user with `grant_super_admin()` when multisite is active.
	 */
	private function switch_to_editor_user() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		if ( is_multisite() ) {
			grant_super_admin( $user_id );
			$this->super_admin_ids[] = $user_id;
		} else {
			$this->add_cap_filter(
				static function ( $caps ) {
					$caps['edit_plugins'] = true;
					return $caps;
				}
			);
		}

		wp_set_current_user( $user_id );
	}

	/**
	 * When no filter is registered and the user lacks edit_plugins cap, returns null.
	 */
	public function test_returns_null_without_filter_and_without_caps() {
		wp_set_current_user( 0 );

		$context = new Check_Context( $this->single_file_plugin_basename() );
		$result  = new Check_Result( $context );

		$url = $this->get_file_editor_url( $result, 'load.php', 42 );

		$this->assertNull( $url );
	}

	/**
	 * Filter returning a template with placeholders gets them substituted verbatim.
	 *
	 * `{{file}}` is replaced with the raw filesystem path (no URL encoding) so that
	 * editor URI schemes like `vscode://file/{{file}}:{{line}}` interpret the path
	 * correctly. `{{line}}` is replaced with the integer line number.
	 */
	public function test_filter_url_with_placeholders_substituted() {
		$this->require_fixture_symlink();

		$this->add_url_filter(
			static function ( $url ) {
				return 'vscode://file/{{file}}:{{line}}';
			}
		);

		$context = new Check_Context( $this->single_file_plugin_basename() );
		$result  = new Check_Result( $context );

		$root = WP_PLUGIN_DIR . '/test-plugin-external-admin-menu-links-without-errors/load.php';

		$this->assertSame(
			'vscode://file/' . $root . ':7',
			$this->get_file_editor_url( $result, 'load.php', 7 )
		);
	}

	/**
	 * Filter returning a string without placeholders is used verbatim.
	 */
	public function test_filter_url_without_placeholders_used_verbatim() {
		$this->require_fixture_symlink();

		$this->add_url_filter(
			static function ( $url, $source ) {
				return 'phpstorm://open?file=' . rawurlencode( $source['file'] ) . '&line=' . (int) $source['line'];
			},
			2
		);

		$context = new Check_Context( $this->single_file_plugin_basename() );
		$result  = new Check_Result( $context );

		$expected = 'phpstorm://open?file=' . rawurlencode( WP_PLUGIN_DIR . '/test-plugin-external-admin-menu-links-without-errors/load.php' ) . '&line=9';

		$this->assertSame(
			$expected,
			$this->get_file_editor_url( $result, 'load.php', 9 )
		);
	}

	/**
	 * Filter receives the $source array with file, line, plugin, filename keys.
	 */
	public function test_filter_receives_source_array() {
		$this->require_fixture_symlink();

		$captured = null;

		$this->add_url_filter(
			static function ( $url, $source ) use ( &$captured ) {
				$captured = $source;
				return 'noop://handler';
			},
			2
		);

		$context = new Check_Context( $this->single_file_plugin_basename() );
		$result  = new Check_Result( $context );

		$this->get_file_editor_url( $result, 'load.php', 5 );

		$this->assertIsArray( $captured );
		$this->assertArrayHasKey( 'file', $captured );
		$this->assertArrayHasKey( 'line', $captured );
		$this->assertArrayHasKey( 'plugin', $captured );
		$this->assertArrayHasKey( 'filename', $captured );
		$this->assertSame( 5, $captured['line'] );
		$this->assertSame( 'test-plugin-external-admin-menu-links-without-errors', $captured['plugin'] );
		$this->assertSame( 'load.php', $captured['filename'] );
		$this->assertSame( WP_PLUGIN_DIR . '/test-plugin-external-admin-menu-links-without-errors/load.php', $captured['file'] );
	}

	/**
	 * Plugin-editor fallback is used when filter is not registered and user has edit_plugins cap.
	 *
	 * When `$line === 0`, the fallback URL must omit the `line` query arg entirely.
	 */
	public function test_fallback_to_plugin_editor_when_no_filter() {
		$this->switch_to_editor_user();

		$context = new Check_Context( $this->single_file_plugin_basename() );
		$result  = new Check_Result( $context );

		$url = $this->get_file_editor_url( $result, 'load.php', 0 );

		$this->assertIsString( $url );
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query_args );
		$this->assertArrayHasKey( 'plugin', $query_args );
		$this->assertArrayHasKey( 'file', $query_args );
		$this->assertArrayNotHasKey( 'line', $query_args );
		$this->assertSame( 'plugin-editor.php', basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
	}

	/**
	 * Plugin-editor fallback includes `line` query arg when line is set.
	 */
	public function test_fallback_to_plugin_editor_includes_line_when_set() {
		$this->switch_to_editor_user();

		$context = new Check_Context( $this->single_file_plugin_basename() );
		$result  = new Check_Result( $context );

		$url = $this->get_file_editor_url( $result, 'load.php', 42 );

		$this->assertIsString( $url );
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query_args );
		$this->assertSame( '42', $query_args['line'] );
		$this->assertSame( 'plugin-editor.php', basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
	}

	/**
	 * Legacy two-filter chain still works through the deprecation shims.
	 *
	 * Registers the old `..._file_editor_url_template` and `..._file_path` filters
	 * and asserts the URL is computed from the remapped path and the line number,
	 * matching the pre-refactor behavior.
	 *
	 * @expectedDeprecated wp_plugin_check_validation_error_source_file_editor_url_template
	 * @expectedDeprecated wp_plugin_check_validation_error_source_file_path
	 */
	public function test_legacy_filters_work_via_deprecated_shim() {
		$this->require_fixture_symlink();
		add_filter( 'deprecated_hook_trigger_error', '__return_false' );

		$this->add_legacy_filter(
			'wp_plugin_check_validation_error_source_file_editor_url_template',
			static function () {
				return 'vscode://file/{{file}}:{{line}}';
			}
		);
		$this->add_legacy_filter(
			'wp_plugin_check_validation_error_source_file_path',
			static function ( $path, $source ) {
				return '/host/mapped' . $path;
			},
			2
		);

		$context = new Check_Context( $this->single_file_plugin_basename() );
		$result  = new Check_Result( $context );

		$root = WP_PLUGIN_DIR . '/test-plugin-external-admin-menu-links-without-errors/load.php';

		$this->assertSame(
			'vscode://file/' . rawurlencode( '/host/mapped' . $root ) . ':9',
			$this->get_file_editor_url( $result, 'load.php', 9 )
		);
	}
}
