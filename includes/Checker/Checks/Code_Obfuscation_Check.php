<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Code_Obfuscation_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Check to detect PHP code obfuscation.
 *
 * @since n.e.x.t
 */
class Code_Obfuscation_Check extends Abstract_File_Check {

	const TYPE_ZEND           = 1;
	const TYPE_SOURCEGUARDIAN = 2;
	const TYPE_IONCUBE        = 4;
	const TYPE_ALL            = 7; // Same as all of the above with bitwise OR.

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

		if ( $this->flags & self::TYPE_ZEND ) {
			$this->look_for_zend( $result, $php_files );
		}
		if ( $this->flags & self::TYPE_SOURCEGUARDIAN ) {
			$this->look_for_sourceguardian( $result, $php_files );
		}
		if ( $this->flags & self::TYPE_IONCUBE ) {
			$this->look_for_ioncube( $result, $php_files );
		}
	}

	/**
	 * Looks for Zend obfuscated files and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_zend( Check_Result $result, array $php_files ) {
		$obfuscated_file = self::file_preg_match( '/(<\?php \@Zend;)|(This file was encoded by)/', $php_files );
		if ( $obfuscated_file ) {
			$result->add_message(
				true,
				sprintf(
					'Code Obfuscation tools are not permitted. Detected: %s',
					'Zend Guard'
				),
				array(
					'code' => 'obfuscated_code_detected',
					'file' => str_replace( $result->plugin()->path(), '', $obfuscated_file ),
				)
			);
		}
	}

	/**
	 * Looks for Source Guardian obfuscated files and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_sourceguardian( Check_Result $result, array $php_files ) {
		$obfuscated_file = self::file_preg_match( "/(sourceguardian\.com)|(function_exists\('sg_load'\))|(\$__x=)/", $php_files );
		if ( $obfuscated_file ) {
			$result->add_message(
				true,
				sprintf(
					'Code Obfuscation tools are not permitted. Detected: %s',
					'Source Guardian'
				),
				array(
					'code' => 'obfuscated_code_detected',
					'file' => str_replace( $result->plugin()->path(), '', $obfuscated_file ),
				)
			);
		}
	}

	/**
	 * Looks for ionCube obfuscated files and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_ioncube( Check_Result $result, array $php_files ) {
		$obfuscated_file = self::file_str_contains( $php_files, 'ionCube' );
		if ( $obfuscated_file ) {
			$result->add_message(
				true,
				sprintf(
					'Code Obfuscation tools are not permitted. Detected: %s',
					'ionCube'
				),
				array(
					'code' => 'obfuscated_code_detected',
					'file' => str_replace( $result->plugin()->path(), '', $obfuscated_file ),
				)
			);
		}
	}
}
