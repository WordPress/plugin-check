<?php
/**
 * Class File_Type_Check.
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
 * Check to detect disallowed file types.
 *
 * @since 1.0.0
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class File_Type_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	const TYPE_COMPRESSED      = 1;
	const TYPE_PHAR            = 2;
	const TYPE_VCS             = 4;
	const TYPE_HIDDEN          = 8;
	const TYPE_APPLICATION     = 16;
	const TYPE_BADLY_NAMED     = 32;
	const TYPE_LIBRARY_CORE    = 64;
	const TYPE_COMPOSER        = 128;
	const TYPE_AI_INSTRUCTIONS = 256;
	const TYPE_ALL             = 511; // Same as all of the above with bitwise OR.

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
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
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
		if ( $this->flags & self::TYPE_APPLICATION ) {
			$this->look_for_application_files( $result, $files );
		}
		if ( $this->flags & self::TYPE_BADLY_NAMED ) {
			// Check for badly named files.
			$this->look_for_badly_named_files( $result, $files );
		}
		if ( $this->flags & self::TYPE_LIBRARY_CORE ) {
			$this->look_for_library_core_files( $result, $files );
		}
		if ( $this->flags & self::TYPE_COMPOSER ) {
			$this->look_for_composer_files( $result, $files );
		}
		if ( $this->flags & self::TYPE_AI_INSTRUCTIONS ) {
			$this->look_for_ai_instructions( $result, $files );
		}
	}

	/**
	 * Looks for compressed files and amends the given result with an error if found.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_compressed_files( Check_Result $result, array $files ) {
		$compressed_files = self::filter_files_by_extensions( $files, array( 'zip', 'gz', 'tgz', 'rar', 'tar', '7z' ) );
		if ( $compressed_files ) {
			foreach ( $compressed_files as $file ) {
				$this->add_result_error_for_file(
					$result,
					__( 'Compressed files are not permitted.', 'plugin-check' ),
					'compressed_files',
					$file,
					0,
					0,
					'',
					8
				);
			}
		}
	}

	/**
	 * Looks for PHAR files and amends the given result with an error if found.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_phar_files( Check_Result $result, array $files ) {
		$phar_files = self::filter_files_by_extension( $files, 'phar' );
		if ( $phar_files ) {
			foreach ( $phar_files as $file ) {
				$this->add_result_error_for_file(
					$result,
					__( 'Phar files are not permitted.', 'plugin-check' ),
					'phar_files',
					$file,
					0,
					0,
					'',
					8
				);
			}
		}
	}

	/**
	 * Looks for VCS directories and amends the given result with an error if found.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_vcs_directories( Check_Result $result, array $files ) {
		$directories = array_flip( array( '.git', '.svn', '.hg', '.bzr' ) );

		$vcs_directories = array_filter(
			array_unique(
				array_map(
					function ( $file ) {
						return dirname( $file );
					},
					$files
				)
			),
			function ( $directory ) use ( $directories ) {
				return isset( $directories[ basename( $directory ) ] );
			}
		);

		if ( $vcs_directories ) {
			// Only use an error in production, otherwise a warning.
			$is_error = ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) && 'production' === wp_get_environment_type();
			foreach ( $vcs_directories as $dir ) {
				$this->add_result_message_for_file(
					$result,
					$is_error,
					__( 'Version control checkouts should not be present.', 'plugin-check' ),
					'vcs_present',
					$dir,
					0,
					0,
					'',
					8
				);
			}
		}
	}

	/**
	 * Looks for hidden files and amends the given result with an error if found.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_hidden_files( Check_Result $result, array $files ) {
		// Any files outside of 'vendor' or 'vendor_prefixed' or 'vendor-prefixed' or 'node_modules' directories that start with a period.
		$hidden_files = self::filter_files_by_regex( $files, '/^((?!\/vendor\/|\/node_modules\/|\/vendor_prefixed\/|\/vendor-prefixed\/).)*\/\.[\w\.\-_]+$/' );

		// Allow development-only files that are commonly used in plugin development workflows.
		$allowed_dev_files = array( '.distignore', '.gitignore' );

		if ( $hidden_files ) {
			$is_error_dev_files = ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) && 'production' === wp_get_environment_type();

			$plugin_path = $result->plugin()->path();
			foreach ( $hidden_files as $file ) {
				// Get the relative file path from the plugin root.
				$relative_file = str_replace( $plugin_path, '', $file );
				$basename      = basename( $relative_file );

				$is_error = in_array( $basename, $allowed_dev_files, true ) && ! $is_error_dev_files ? false : true;

				$this->add_result_message_for_file(
					$result,
					$is_error,
					__( 'Hidden files are not permitted.', 'plugin-check' ),
					'hidden_files',
					$file,
					0,
					0,
					'',
					8
				);
			}
		}
	}

	/**
	 * Looks for application files and amends the given result with an error if found.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_application_files( Check_Result $result, array $files ) {
		$application_files = self::filter_files_by_extensions(
			$files,
			array( 'a', 'bin', 'bpk', 'deploy', 'dist', 'distz', 'dmg', 'dms', 'dump', 'elc', 'exe', 'iso', 'lha', 'lrf', 'lzh', 'o', 'obj', 'phar', 'pkg', 'sh', 'so' )
		);
		if ( $application_files ) {
			foreach ( $application_files as $file ) {
				$this->add_result_error_for_file(
					$result,
					__( 'Application files are not permitted.', 'plugin-check' ),
					'application_detected',
					$file,
					0,
					0,
					'',
					8
				);
			}
		}
	}

	/**
	 * Looks for application files and amends the given result with an error if found.
	 *
	 * @since 1.2.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_badly_named_files( Check_Result $result, array $files ) {
		$conflict_chars = '!@#$%^&*()+=[]{};:"\'<>,?/\\|`~';

		$plugin_path = $result->plugin()->path();

		$files = array_map(
			function ( $file ) use ( $plugin_path ) {
				return str_replace( $plugin_path, '', $file );
			},
			$files
		);

		foreach ( $files as $file ) {
			$badly_name = false;
			if ( preg_match( '/\s/', $file ) ) {
				$badly_name = true;
			}

			if ( preg_match( '/[' . preg_quote( $conflict_chars, '/' ) . ']/', basename( $file ) ) ) {
				$badly_name = true;
			}

			if ( $badly_name ) {
				$this->add_result_error_for_file(
					$result,
					__( 'File and folder names must not contain spaces or special characters.', 'plugin-check' ),
					'badly_named_files',
					$file,
					0,
					0,
					'',
					8
				);
			}
		}

		// Duplicated names in different folders.
		$folders = array();
		foreach ( $files as $file ) {
			$folder = str_replace( basename( $file ), '', $file );
			if ( empty( $folder ) ) {
				continue;
			}
			$folders[] = $folder;
		}
		$folders                = array_unique( $folders );
		$folders_lowercase      = array_map( 'strtolower', $folders );
		$case_sensitive_folders = array_unique( array_diff_assoc( $folders_lowercase, array_unique( $folders_lowercase ) ) );

		if ( ! empty( $case_sensitive_folders ) ) {
			$this->add_result_error_for_file(
				$result,
				__( 'Multiple folders with the same name but different case were found. This can be problematic on certain file systems.', 'plugin-check' ),
				'case_sensitive_folders',
				implode( ', ', $case_sensitive_folders ),
				0,
				0,
				'',
				8
			);
		}

		// Duplicated names in same folder.
		$files_lowercase      = array_map( 'strtolower', $files );
		$case_sensitive_files = array_unique( array_diff_assoc( $files_lowercase, array_unique( $files_lowercase ) ) );

		if ( ! empty( $case_sensitive_files ) ) {
			$this->add_result_error_for_file(
				$result,
				__( 'Multiple files with the same name but different case were found. This can be problematic on certain file systems.', 'plugin-check' ),
				'case_sensitive_files',
				implode( ', ', $case_sensitive_files ),
				0,
				0,
				'',
				8
			);
		}
	}

	/**
	 * Looks for library core files and amends the given result with an error if found.
	 *
	 * @since 1.3.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_library_core_files( Check_Result $result, array $files ) {
		$plugin_path = $result->plugin()->path();

		$relative_files = array();
		foreach ( $files as $file ) {
			$relative_files[ $file ] = str_replace( $plugin_path, '', $file );
		}

		foreach ( $relative_files as $file => $relative_file ) {
			if ( Core_Library_File_Signature_Checker::file_matches_core_library_signature( $file, $relative_file ) ) {
				$this->add_result_error_for_file(
					$result,
					__( 'Library files that are already in the WordPress core are not permitted.', 'plugin-check' ),
					'library_core_files',
					$relative_file,
					0,
					0,
					'',
					8
				);
			}
		}
	}

	/**
	 * Looks for composer files.
	 *
	 * @since 1.4.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_composer_files( Check_Result $result, array $files ) {
		if ( $result->plugin()->is_single_file_plugin() ) {
			return;
		}

		$plugin_path = $result->plugin()->path();

		if (
			is_dir( $plugin_path . 'vendor' ) &&
			file_exists( $plugin_path . 'vendor/autoload.php' ) &&
			! file_exists( $plugin_path . 'composer.json' )
		) {
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: 1: directory, 2: filename */
					esc_html__( 'The "%1$s" directory using composer exists, but "%2$s" file is missing.', 'plugin-check' ),
					'/vendor',
					'composer.json'
				),
				'missing_composer_json_file',
				'composer.json',
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#included-unneeded-folders',
				6
			);
		}
	}

	/**
	 * Looks for AI instruction files and directories.
	 *
	 * @since 1.8.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function look_for_ai_instructions( Check_Result $result, array $files ) {
		$this->check_ai_directories( $result, $files );
		$this->check_github_directory( $result, $files );
		$this->check_unexpected_markdown_files( $result, $files );
	}

	/**
	 * Checks for AI instruction directories.
	 *
	 * @since 1.8.0
	 *
	 * @param Check_Result $result Check result to amend.
	 * @param array        $files  List of file paths.
	 */
	protected function check_ai_directories( Check_Result $result, array $files ) {
		$plugin_path    = $result->plugin()->path();
		$ai_directories = array( '.cursor', '.claude', '.aider', '.continue', '.windsurf', '.ai' );
		$found_ai_dirs  = array();

		foreach ( $files as $file ) {
			$relative_path = str_replace( $plugin_path, '', $file );

			foreach ( $ai_directories as $ai_dir ) {
				if ( strpos( $relative_path, '/' . $ai_dir . '/' ) !== false || strpos( $relative_path, $ai_dir . '/' ) === 0 ) {
					$found_ai_dirs[ $ai_dir ] = true;
					break;
				}
			}
		}

		foreach ( array_keys( $found_ai_dirs ) as $ai_dir ) {
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: %s: directory name */
					__( 'AI instruction directory "%s" detected. These directories should not be included in production plugins.', 'plugin-check' ),
					$ai_dir
				),
				'ai_instruction_directory',
				$plugin_path . $ai_dir,
				0,
				0,
				'',
				9
			);
		}
	}

	/**
	 * Checks for GitHub workflow directory.
	 *
	 * @since 1.8.0
	 *
	 * @param Check_Result $result Check result to amend.
	 * @param array        $files  List of file paths.
	 */
	protected function check_github_directory( Check_Result $result, array $files ) {
		$plugin_path  = $result->plugin()->path();
		$found_github = false;

		foreach ( $files as $file ) {
			$relative_path = str_replace( $plugin_path, '', $file );
			if ( strpos( $relative_path, '/.github/' ) !== false || strpos( $relative_path, '.github/' ) === 0 ) {
				$found_github = true;
				break;
			}
		}

		if ( $found_github ) {
			$this->add_result_warning_for_file(
				$result,
				__( 'GitHub workflow directory ".github" detected. This directory should not be included in production plugins.', 'plugin-check' ),
				'github_directory',
				$plugin_path . '.github',
				0,
				0,
				'',
				9
			);
		}
	}

	/**
	 * Checks for unexpected markdown files.
	 *
	 * @since 1.8.0
	 *
	 * @param Check_Result $result Check result to amend.
	 * @param array        $files  List of file paths.
	 */
	protected function check_unexpected_markdown_files( Check_Result $result, array $files ) {
		$plugin_path                 = $result->plugin()->path();
		$allowed_root_md_files       = array( 'README.md', 'readme.txt', 'LICENSE', 'LICENSE.md', 'CHANGELOG.md', 'CONTRIBUTING.md', 'SECURITY.md' );
		$allowed_root_md_files_lower = array_map( 'strtolower', $allowed_root_md_files );
		$root_md_files               = array();

		foreach ( $files as $file ) {
			$relative_path = str_replace( $plugin_path, '', $file );
			$relative_path = ltrim( $relative_path, '/' );
			$basename      = basename( $file );

			if ( substr_count( $relative_path, '/' ) === 0 && pathinfo( $file, PATHINFO_EXTENSION ) === 'md' ) {
				if ( ! in_array( strtolower( $basename ), $allowed_root_md_files_lower, true ) ) {
					$root_md_files[] = $file;
				}
			}
		}

		foreach ( $root_md_files as $file ) {
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: %s: file name */
					__( 'Unexpected markdown file "%s" detected in plugin root. Only specific markdown files are expected in production plugins.', 'plugin-check' ),
					basename( $file )
				),
				'unexpected_markdown_file',
				$file,
				0,
				0,
				'',
				9
			);
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
		return __( 'Detects the usage of hidden and compressed files, VCS directories, application files, badly named files, Library Core Files, AI development directories, and unexpected markdown files.', 'plugin-check' );
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
