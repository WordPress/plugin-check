<?php
/**
 * Class Code_Obfuscation_Check.
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
 * Check to detect PHP code obfuscation.
 *
 * @since 1.0.0
 */
class Code_Obfuscation_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	const TYPE_ZEND           = 1;
	const TYPE_SOURCEGUARDIAN = 2;
	const TYPE_IONCUBE        = 4;
	const TYPE_ALL            = 7; // Same as all of the above with bitwise OR.

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
	public function __construct( $flags = self::TYPE_ALL ) {
		$this->flags = $flags;
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

		if ( $this->flags & self::TYPE_ZEND ) {
			$this->look_for_zendguard( $result, $php_files );
		}
		if ( $this->flags & self::TYPE_SOURCEGUARDIAN ) {
			$this->look_for_sourceguardian( $result, $php_files );
		}
		if ( $this->flags & self::TYPE_IONCUBE ) {
			$this->look_for_ioncube( $result, $php_files );
		}
	}

	/**
	 * Looks for Zend Guard obfuscated files and amends the given result with an error if found.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_zendguard( Check_Result $result, array $php_files ) {
		$files = self::files_preg_match_all( '/(\<\?php \@Zend;)|(This file was encoded by)/', $php_files );

		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: %s: tool name */
						__( 'Code Obfuscation tools are not permitted. Detected: %s', 'plugin-check' ),
						__( 'Zend Guard', 'plugin-check' )
					),
					'obfuscated_code_detected',
					$file['file'],
					$file['line'],
					$file['column'],
					'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#gpl-no-publicly-documented-resource',
					7
				);
			}
		}
	}

	/**
	 * Looks for Source Guardian obfuscated files and amends the given result with an error if found.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_sourceguardian( Check_Result $result, array $php_files ) {
		$files = self::files_preg_match_all( "/(sourceguardian\.com)|(function_exists\('sg_load'\))|(\$__x=)/", $php_files );

		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: %s: tool name */
						__( 'Code Obfuscation tools are not permitted. Detected: %s', 'plugin-check' ),
						__( 'Source Guardian', 'plugin-check' )
					),
					'obfuscated_code_detected',
					$file['file'],
					$file['line'],
					$file['column'],
					'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#gpl-no-publicly-documented-resource',
					7
				);
			}
		}
	}

	/**
	 * Looks for ionCube obfuscated files and amends the given result with an error if found.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_ioncube( Check_Result $result, array $php_files ) {
		$files = self::files_preg_match_all( '/ionCube/', $php_files );

		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: %s: tool name */
						__( 'Code Obfuscation tools are not permitted. Detected: %s', 'plugin-check' ),
						__( 'ionCube', 'plugin-check' )
					),
					'obfuscated_code_detected',
					$file['file'],
					$file['line'],
					$file['column'],
					'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#gpl-no-publicly-documented-resource',
					7
				);
			}
		}
	}
	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.1.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects the usage of code obfuscation tools.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.1.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/', 'plugin-check' );
	}
}
