<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Plugin_Updater_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Check to detect plugin updater.
 *
 * @since n.e.x.t
 */
class Plugin_Updater_Check extends Abstract_File_Check {

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );

		// Looks for "UpdateURI" in plugin header.
		$this->look_for_update_uri_header( $result );

		// Looks for special updater file.
		$this->look_for_updater_file( $result, $php_files );

		// Looks for plugin updater code in plugin files.
		$this->look_for_plugin_updaters( $result, $php_files );

		// Looks for plugin updater routines in plugin files.
		$this->look_for_updater_routines( $result, $php_files );
	}

	/**
	 * Looks for UpdateURI in plugin header and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 */
	protected function look_for_update_uri_header( Check_Result $result ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_main_file = $result->plugin()->abspath();
		$plugin_header    = get_plugin_data( $plugin_main_file );
		if ( ! empty( $plugin_header['UpdateURI'] ) ) {
			$this->add_result_error_for_file(
				$result,
				$plugin_main_file,
				__( 'Plugin Updater detected. Use of the Update URI header is not helpful in plugins hosted on WordPress.org.', 'plugin-check' )
			);
		}
	}

	/**
	 * Looks for plugin updater file and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_updater_file( Check_Result $result, array $php_files ) {

		$filenames     = array_map( 'strtolower', array_map( 'basename', $php_files ) );
		$blocked_files = array( 'plugin-update-checker.php' );
		$matches       = array_intersect( $filenames, $blocked_files );

		if ( $matches ) {
			$this->add_result_error_for_file(
				$result,
				'plugin-update-checker.php',
				sprintf(
					/* translators: %s: The match updater file name. */
					__( 'Plugin Updater detected. These are not permitted in WordPress.org hosted plugins. Detected: %s', 'plugin-check' ),
					esc_html( implode( ', ', $matches ) )
				)
			);
		}
	}

	/**
	 * Looks for plugin updater code in plugin files and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_plugin_updaters( Check_Result $result, array $php_files ) {

		$needles = array(
			"'plugin-update-checker'",
			'WP_GitHub_Updater',
			'WPGitHubUpdater',
			'#class [A-Z_]+_Plugin_Updater#i',
			'#updater\.\w+\.\w{2,5}#i',
			'site_transient_update_plugins',
		);

		foreach ( $needles as $needle ) {
			$match = self::file_str_contains( $php_files, $needle );
			if ( $match ) {
				$this->add_result_error_for_file(
					$result,
					$match,
					sprintf(
						/* translators: %s: The match file name. */
						__( 'Plugin Updater detected. These are not permitted in WordPress.org hosted plugins. Detected: %s', 'plugin-check' ),
						esc_html( $needle )
					)
				);
			}
		}
	}

	/**
	 * Looks for plugin updater routines in plugin files and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_updater_routines( Check_Result $result, array $php_files ) {

		$needles = array(
			'#pre_set_site_transient_update_\w+#i',
			'auto_update_plugin',
			'#_site_transient_update_\w+#i',
		);

		foreach ( $needles as $needle ) {
			$match = self::file_str_contains( $php_files, $needle );
			if ( $match ) {
				$this->add_result_error_for_file(
					$result,
					$match,
					sprintf(
						/* translators: %s: The match file name. */
						__( 'Detected code which may be altering WordPress update routines. Detected: %s', 'plugin-check' ),
						esc_html( $needle )
					),
					false,
					'update_modification_detected'
				);
			}
		}
	}

	/**
	 * Amends the given result with an error for the given obfuscated file and tool name.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result       The check result to amend, including the plugin context to check.
	 * @param string       $updater_file Absolute path to the updater file found.
	 * @param string       $message      Error message for updater.
	 * @param bool         $error        Whether it is an error message. Defaults to true.
	 * @param string       $code         Violation code according to the message. Default "plugin_updater_detected".
	 */
	private function add_result_error_for_file( Check_Result $result, $updater_file, $message, $error = true, $code = 'plugin_updater_detected' ) {
		$result->add_message(
			$error,
			$message,
			array(
				'code' => $code,
				'file' => str_replace( $result->plugin()->path(), '', $updater_file ),
			)
		);
	}
}
