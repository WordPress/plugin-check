<?php
/**
 * Class Private_Upload_Protection_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Security;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Experimental_Check;

/**
 * Advises authors when uploads and server-specific access rules occur together.
 */
class Private_Upload_Protection_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Experimental_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * @return array Check categories.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_SECURITY );
	}

	/**
	 * Checks for upload APIs and Apache/IIS-only denial rules without executing PHP.
	 *
	 * This heuristic cannot establish where files are stored or whether they are private.
	 *
	 * @param Check_Result $result Results to amend.
	 * @param array        $files  Absolute paths to plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$has_uploads = false;
		$candidates  = array();

		foreach ( $files as $file ) {
			$name = strtolower( basename( $file ) );
			if ( 'php' !== strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) && ! in_array( $name, array( '.htaccess', 'web.config' ), true ) ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $file );
			if ( false === $contents ) {
				continue;
			}

			if ( in_array( $name, array( '.htaccess', 'web.config' ), true ) ) {
				if ( $this->contains_deny_rule( $contents ) ) {
					$candidates[] = $file;
				}
				continue;
			}

			$tokens      = token_get_all( $contents );
			$strings     = '';
			$significant = array();
			foreach ( $tokens as $token ) {
				if ( is_array( $token ) ) {
					if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT, T_WHITESPACE ), true ) ) {
						continue;
					}
					if ( in_array( $token[0], array( T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE ), true ) ) {
						$value = $token[1];
						if ( T_ENCAPSED_AND_WHITESPACE === $token[0] || '"' === substr( $value, 0, 1 ) ) {
							$value = stripcslashes( $value );
						} else {
							$value = str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $value );
						}
						$strings .= "\n" . $value;
					}
				}
				$significant[] = $token;
			}

			$has_uploads = $has_uploads || $this->contains_upload_call( $significant );
			if ( preg_match( '/\\.htaccess|web\\.config/i', $strings ) && $this->contains_deny_rule( $strings ) ) {
				$candidates[] = $file;
			}
		}

		if ( ! $has_uploads ) {
			return;
		}

		foreach ( $candidates as $file ) {
			$this->add_result_warning_for_file(
				$result,
				__( 'Review upload privacy: this plugin uses upload APIs and Apache or IIS access-denial rules. Nginx ignores .htaccess and web.config. If uploads are private, verify that direct URLs are blocked on every supported server, or store files outside the public web root and serve them through an authorized handler. This advisory does not establish that files are exposed.', 'plugin-check' ),
				'potentially_unprotected_private_uploads',
				$file
			);
		}
	}

	/**
	 * Recognizes common Apache and IIS access-denial rules.
	 *
	 * @param string $contents Configuration text or PHP string tokens.
	 * @return bool Whether an access-denial rule was found.
	 */
	private function contains_deny_rule( $contents ) {
		return (bool) preg_match(
			'~\\bdeny\\s+from\\s+all\\b|\\brequire\\s+all\\s+denied\\b|<deny\\b[^>]*\\busers\\s*=\\s*[\'"][*?][\'"]|<add\\b[^>]*\\baccessType\\s*=\\s*[\'"]Deny[\'"]~i',
			$contents
		);
	}

	/**
	 * Recognizes global WordPress upload API calls, excluding declarations and methods.
	 *
	 * @param array $tokens Significant PHP tokens.
	 * @return bool Whether an upload API is called.
	 */
	private function contains_upload_call( array $tokens ) {
		$functions   = array( 'wp_upload_dir', 'wp_upload_bits', 'wp_handle_upload', 'wp_handle_sideload', 'media_handle_upload', 'media_handle_sideload', '_wp_handle_upload' );
		$name_tokens = array( T_STRING );
		if ( defined( 'T_NAME_FULLY_QUALIFIED' ) ) {
			$name_tokens[] = constant( 'T_NAME_FULLY_QUALIFIED' );
		}
		foreach ( $tokens as $index => $token ) {
			if ( ! is_array( $token ) || ! in_array( $token[0], $name_tokens, true ) || ! in_array( strtolower( ltrim( $token[1], '\\' ) ), $functions, true ) || '(' !== ( $tokens[ $index + 1 ] ?? null ) ) {
				continue;
			}

			$previous = $tokens[ $index - 1 ] ?? null;
			if ( is_array( $previous ) && ( in_array( $previous[0], array( T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW ), true ) || '?->' === $previous[1] ) ) {
				continue;
			}
			$previous_text = is_array( $previous ) ? $previous[1] : $previous;
			$before        = $tokens[ $index - 2 ] ?? null;
			if ( is_array( $before ) ) {
				if ( '&' === $previous_text && T_FUNCTION === $before[0] ) {
					continue;
				}
				if ( '\\' === $previous_text && in_array( $before[0], array( T_STRING, T_NAMESPACE, T_NEW ), true ) ) {
					// PHP 7 tokenizes qualified names separately, including class constructors.
					continue;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Gets a description of the check.
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Advises authors to verify private upload protection across web servers.', 'plugin-check' );
	}

	/**
	 * Gets documentation explaining the limitations of server-specific rules.
	 *
	 * @return string Documentation URL.
	 */
	public function get_documentation_url(): string {
		return 'https://github.com/WordPress/plugin-check/issues/1509';
	}
}
