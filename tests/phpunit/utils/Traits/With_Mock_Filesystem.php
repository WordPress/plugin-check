<?php
/**
 * Trait With_Mock_Filesystem.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Test_Utils\Traits;

trait With_Mock_Filesystem {
	/**
	 * Sets up a Mock Filesystem.
	 *
	 * @since 1.0.0
	 */
	protected function set_up_mock_filesystem() {
		global $wp_filesystem;

		add_filter(
			'filesystem_method_file',
			function () {
				return TESTS_PLUGIN_DIR . '/tests/phpunit/testdata/Filesystem/WP_Filesystem_MockFilesystem.php';
			}
		);
		add_filter(
			'filesystem_method',
			function () {
				return 'MockFilesystem';
			}
		);
	}
}
