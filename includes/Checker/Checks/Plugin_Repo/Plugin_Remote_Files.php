<?php
/**
 * Class Plugin_Remote_Files.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check to detect plugin updater.
 *
 * @since 1.0.0
 */
class Plugin_Remote_Files extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Bitwise flags to control check behavior.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $flags = 0;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $flags Bitwise flags to control check behavior.
	 */
	public function __construct() {
	}

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.0.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );

		// Looks for Kwnown External URLs.
		$this->look_for_known_urls( $result, $files );
	}

	/**
	 * Looks for kwown urls that makes remote calls.
	 *
	 * @since n.e.x.t.
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_known_urls( Check_Result $result, array $files ) {
		// Known offloading services.
		$look_known_offloading_services = array(
			'code\.jquery\.com',
			'(?<!api\.)cloudflare\.com',
			'cdn\.jsdelivr\.net',
			'cdn\.rawgit\.com',
			'code\.getmdl\.io',
			'bootstrapcdn',
			'cl\.ly',
			'cdn\.datatables\.net',
			'aspnetcdn\.com',
			'ajax\.googleapis\.com',
			'webfonts\.zoho\.com',
			'raw\.githubusercontent\.com',
			'github\.com\/.*\/raw',
			'unpkg\.com',
			'imgur\.com',
			'rawgit\.com',
			'amazonaws\.com',
			'cdn\.tiny\.cloud',
			'tiny\.cloud',
			'tailwindcss\.com',
			'herokuapp\.com',
			'(?<!fonts\.)gstatic\.com',
			'kit\.fontawesome',
			'use\.fontawesome',
			'googleusercontent\.com',
			'placeholder\.com',
			's\.w\.org',
		);

		$offloaded_pattern = '/(' . implode( '|', $look_known_offloading_services ) . ')/i';
		$files = self::files_preg_match_all( $offloaded_pattern, $files );

		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				$this->add_result_error_for_file(
					$result,
					__( '<strong>Offloaded Content.</strong><br>Offloading images, js, css, and other scripts to your servers or any remote service is disallowed.', 'plugin-check' ),
					'external_offloaded',
					$file['file'],
					$file['line'],
					$file['column'],
					'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#calling-files-remotely'
				);
			}
		}
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since n.e.x.t.
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Prevents using remote services that are not necessary.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since n.e.x.t.
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#calling-files-remotely', 'plugin-check' );
	}
}
