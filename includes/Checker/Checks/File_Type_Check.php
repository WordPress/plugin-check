<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\File_Type_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Stable_Check;

/**
 * Check to detect disallowed file types.
 *
 * @since n.e.x.t
 */
class File_Type_Check extends Abstract_File_Check {

	use Stable_Check;

	const TYPE_COMPRESSED = 1;
	const TYPE_PHAR       = 2;
	const TYPE_VCS        = 4;
	const TYPE_HIDDEN     = 8;
	const TYPE_ALL        = 15; // Same as all of the above with bitwise OR.

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
		if ( $this->flags & self::TYPE_COMPRESSED ) {
			$this->look_for_compressed_files( $result, $files );
		}
		if ( $this->flags & self::TYPE_PHAR ) {
			$this->look_for_phar_files( $result, $files );
		}
		if ( $this->flags & self::TYPE_VCS ) {
			$this->look_for_vcs_directories( $result, $files );
		}
		if ( $this->flags & self::TYPE_HIDDEN ) {
			$this->look_for_hidden_files( $result, $files );
		}
	}

	/**
	 * Looks for compressed files and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_compressed_files( Check_Result $result, array $files ) {
		$compressed_files = self::filter_files_by_extensions( $files, array( 'zip', 'gz', 'tgz', 'rar', 'tar', '7z' ) );
		if ( $compressed_files ) {
			foreach ( $compressed_files as $file ) {
				$this->add_result_error_for_file( $result, $file, 'compressed_files', __( 'Compressed files are not permitted.', 'plugin-check' ) );
			}
		}
	}

	/**
	 * Looks for PHAR files and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_phar_files( Check_Result $result, array $files ) {
		$phar_files = self::filter_files_by_extension( $files, 'phar' );
		if ( $phar_files ) {
			foreach ( $phar_files as $file ) {
				$this->add_result_error_for_file( $result, $file, 'phar_files', __( 'Phar files are not permitted.', 'plugin-check' ) );
			}
		}
	}

	/**
	 * Looks for VCS directories and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_vcs_directories( Check_Result $result, array $files ) {
		$directories = array_flip( array( '.git', '.svn', '.hg', '.bzr' ) );

		$vcs_directories = array_filter(
			array_unique(
				array_map(
					function( $file ) {
						return dirname( $file );
					},
					$files
				)
			),
			function( $directory ) use ( $directories ) {
				return isset( $directories[ basename( $directory ) ] );
			}
		);

		if ( $vcs_directories ) {
			// Only use an error in production, otherwise a warning.
			$is_error = ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) && 'production' === wp_get_environment_type();
			foreach ( $vcs_directories as $dir ) {
				$result->add_message(
					$is_error,
					__( 'Version control checkouts should not be present.', 'plugin-check' ),
					array(
						'code' => 'vcs_present',
						'file' => str_replace( $result->plugin()->path(), '', $dir ),
					)
				);
			}
		}
	}

	/**
	 * Looks for hidden files and amends the given result with an error if found.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_hidden_files( Check_Result $result, array $files ) {
		// Any files outside of 'vendor' or 'node_modules' directories that start with a period.
		$hidden_files = self::filter_files_by_regex( $files, '/^((?!\/vendor\/|\/node_modules\/).)*\/\.\w+(\.\w+)*$/' );
		if ( $hidden_files ) {
			foreach ( $hidden_files as $file ) {
				$this->add_result_error_for_file( $result, $file, 'hidden_files', __( 'Hidden files are not permitted.', 'plugin-check' ) );
			}
		}
	}

	/**
	 * Amends the given result with an error for the given file, code, and message.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result  The check result to amend, including the plugin context to check.
	 * @param string       $file    Absolute path to the file found.
	 * @param string       $code    Error code.
	 * @param string       $message Error message.
	 */
	private function add_result_error_for_file( Check_Result $result, $file, $code, $message ) {
		$result->add_message(
			true,
			$message,
			array(
				'code' => $code,
				'file' => str_replace( $result->plugin()->path(), '', $file ),
			)
		);
	}
}
