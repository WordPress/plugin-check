<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Plugin_Updater_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Stable_Check;

/**
 * Check to detect plugin updater.
 *
 * @since n.e.x.t
 */
class Plugin_Updater_Check extends Abstract_File_Check {

	use Stable_Check;

	const TYPE_PLUGIN_UPDATE_URI_HEADER = 1;
	const TYPE_PLUGIN_UPDATER_FILE      = 2;
	const TYPE_PLUGIN_UPDATERS          = 4;
	const TYPE_PLUGIN_UPDATER_ROUTINES  = 8;
	const TYPE_ALL                      = 15; // Same as all of the above with bitwise OR.

	/**
	 * Bitwise flags to control check behavior.
	 *
	 * @since n.e.x.t
	 * @var int
	 */
	protected $flags = 0;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param int $flags Bitwise flags to control check behavior.
	 */
	public function __construct( $flags = self::TYPE_ALL ) {
		$this->flags = $flags;
	}

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
		if ( $this->flags & self::TYPE_PLUGIN_UPDATE_URI_HEADER ) {
			$this->look_for_update_uri_header( $result );
		}

		// Looks for special updater file.
		if ( $this->flags & self::TYPE_PLUGIN_UPDATER_FILE ) {
			$this->look_for_updater_file( $result, $php_files );
		}

		// Looks for plugin updater code in plugin files.
		if ( $this->flags & self::TYPE_PLUGIN_UPDATERS ) {
			$this->look_for_plugin_updaters( $result, $php_files );
		}

		// Looks for plugin updater routines in plugin files.
		if ( $this->flags & self::TYPE_PLUGIN_UPDATER_ROUTINES ) {
			$this->look_for_updater_routines( $result, $php_files );
		}
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

		$plugin_main_file = WP_PLUGIN_DIR . '/' . $result->plugin()->basename();
		$plugin_header    = get_plugin_data( $plugin_main_file );
		if ( ! empty( $plugin_header['UpdateURI'] ) ) {
			$this->add_result_error_for_file(
				$result,
				true,
				__( 'Plugin Updater detected. Use of the Update URI header is not helpful in plugins hosted on WordPress.org.', 'plugin-check' ),
				'plugin_updater_detected',
				$plugin_main_file
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

		$plugin_update_files = self::filter_files_by_regex( $php_files, '/plugin-update-checker\.php$/' );

		if ( $plugin_update_files ) {
			foreach ( $plugin_update_files as $file ) {
				$this->add_result_error_for_file(
					$result,
					true,
					sprintf(
						/* translators: %s: The match updater file name. */
						__( 'Plugin Updater detected. These are not permitted in WordPress.org hosted plugins. Detected: %s', 'plugin-check' ),
						basename( $file )
					),
					'plugin_updater_detected',
					$file
				);
			}
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

		$look_for_regex = array(
			'#\'plugin-update-checker\'#',
			'#WP_GitHub_Updater#',
			'#WPGitHubUpdater#',
			'#class [A-Z_]+_Plugin_Updater#i',
			'#updater\.\w+\.\w{2,5}#i',
			'#site_transient_update_plugins#',
		);

		foreach ( $look_for_regex as $regex ) {
			$matches      = array();
			$updater_file = self::file_preg_match( $regex, $php_files, $matches );
			if ( $updater_file ) {
				$this->add_result_error_for_file(
					$result,
					true,
					sprintf(
						/* translators: %s: The match updater string. */
						__( 'Plugin Updater detected. These are not permitted in WordPress.org hosted plugins. Detected: %s', 'plugin-check' ),
						esc_attr( $matches[0] )
					),
					'plugin_updater_detected',
					$updater_file
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

		$look_for_regex = array(
			'#auto_update_plugin#',
			'#pre_set_site_transient_update_\w+#i',
			'#_site_transient_update_\w+#i',
		);

		foreach ( $look_for_regex as $regex ) {
			$matches      = array();
			$updater_file = self::file_preg_match( $regex, $php_files, $matches );
			if ( $updater_file ) {
				$this->add_result_error_for_file(
					$result,
					false,
					sprintf(
						/* translators: %s: The match file name. */
						__( 'Detected code which may be altering WordPress update routines. Detected: %s', 'plugin-check' ),
						esc_html( $matches[0] )
					),
					'update_modification_detected',
					$updater_file
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
	 * @param bool         $error        Whether it is an error message.
	 * @param string       $message      Error message for updater.
	 * @param string       $code         Violation code according to the message.
	 * @param string       $updater_file Absolute path to the updater file found.
	 */
	private function add_result_error_for_file( Check_Result $result, $error, $message, $code, $updater_file ) {
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
