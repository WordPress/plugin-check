<?php
/**
 * WordPressVIPMinimum_Sniffs_Files_IncludingFileSniff.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Files;

use PHP_CodeSniffer\Util\Tokens;
use WordPressCS\WordPress\AbstractFunctionRestrictionsSniff;

/**
 * WordPressVIPMinimum_Sniffs_Files_IncludingFileSniff.
 *
 * Checks for custom variables, functions and constants, and external URLs used in file inclusion.
 */
class IncludingFileSniff extends AbstractFunctionRestrictionsSniff {

	/**
	 * List of function used for getting paths.
	 *
	 * @var array
	 */
	public $getPathFunctions = [
		'dirname',
		'get_404_template',
		'get_archive_template',
		'get_attachment_template',
		'get_author_template',
		'get_category_template',
		'get_date_template',
		'get_embed_template',
		'get_front_page_template',
		'get_page_template',
		'get_paged_template', // Deprecated, but should still be accepted for the purpose of this sniff.
		'get_home_template',
		'get_index_template',
		'get_parent_theme_file_path',
		'get_privacy_policy_template',
		'get_query_template',
		'get_search_template',
		'get_single_template',
		'get_singular_template',
		'get_stylesheet_directory',
		'get_tag_template',
		'get_taxonomy_template',
		'get_template_directory',
		'get_theme_file_path',
		'locate_block_template',
		'locate_template',
		'plugin_dir_path',
	];

	/**
	 * List of restricted constants.
	 *
	 * @var array
	 */
	public $restrictedConstants = [
		'TEMPLATEPATH'   => 'get_template_directory',
		'STYLESHEETPATH' => 'get_stylesheet_directory',
	];

	/**
	 * List of allowed constants.
	 *
	 * @var array
	 */
	public $allowedConstants = [
		'ABSPATH',
		'WP_CONTENT_DIR',
		'WP_PLUGIN_DIR',
	];

	/**
	 * List of keywords allowed for use in custom constants.
	 * Note: Customizing this property will overwrite current default values.
	 *
	 * @var array
	 */
	public $allowedKeywords = [
		'PATH',
		'DIR',
	];

	/**
	 * Functions used for modify slashes.
	 *
	 * @var array
	 */
	public $slashingFunctions = [
		'trailingslashit',
		'user_trailingslashit',
		'untrailingslashit',
	];

	/**
	 * Groups of functions to restrict.
	 *
	 * @return array
	 */
	public function getGroups() {
		return [];
	}

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return Tokens::$includeTokens;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {
		$nextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true );

		if ( $this->tokens[ $nextToken ]['code'] === T_OPEN_PARENTHESIS ) {
			// The construct is using parenthesis, grab the next non empty token.
			$nextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $nextToken + 1, null, true, null, true );
		}

		if ( $this->tokens[ $nextToken ]['code'] === T_DIR || $this->tokens[ $nextToken ]['content'] === '__DIR__' ) {
			// The construct is using __DIR__ which is fine.
			return;
		}

		if ( $this->tokens[ $nextToken ]['code'] === T_VARIABLE ) {
			$message = 'File inclusion using variable (`%s`). Probably needs manual inspection.';
			$data    = [ $this->tokens[ $nextToken ]['content'] ];
			$this->phpcsFile->addWarning( $message, $nextToken, 'UsingVariable', $data );
			return;
		}

		if ( $this->tokens[ $nextToken ]['code'] === T_STRING ) {
			if ( in_array( $this->tokens[ $nextToken ]['content'], $this->getPathFunctions, true ) === true ) {
				// The construct is using one of the functions for getting correct path which is fine.
				return;
			}

			if ( in_array( $this->tokens[ $nextToken ]['content'], $this->allowedConstants, true ) === true ) {
				// The construct is using one of the allowed constants which is fine.
				return;
			}

			if ( $this->has_custom_path( $this->tokens[ $nextToken ]['content'] ) === true ) {
				// The construct is using a constant with an allowed keyword.
				return;
			}

			if ( array_key_exists( $this->tokens[ $nextToken ]['content'], $this->restrictedConstants ) === true ) {
				// The construct is using one of the restricted constants.
				$message = '`%s` constant might not be defined or available. Use `%s()` instead.';
				$data    = [ $this->tokens[ $nextToken ]['content'], $this->restrictedConstants[ $this->tokens[ $nextToken ]['content'] ] ];
				$this->phpcsFile->addError( $message, $nextToken, 'RestrictedConstant', $data );
				return;
			}

			$nextNextToken = $this->phpcsFile->findNext( array_merge( Tokens::$emptyTokens, [ T_COMMENT ] ), $nextToken + 1, null, true, null, true );
			if ( preg_match( '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $this->tokens[ $nextToken ]['content'] ) === 1 && $this->tokens[ $nextNextToken ]['code'] !== T_OPEN_PARENTHESIS ) {
				// The construct is using custom constant, which needs manual inspection.
				$message = 'File inclusion using custom constant (`%s`). Probably needs manual inspection.';
				$data    = [ $this->tokens[ $nextToken ]['content'] ];
				$this->phpcsFile->addWarning( $message, $nextToken, 'UsingCustomConstant', $data );
				return;
			}

			if ( strpos( $this->tokens[ $nextToken ]['content'], '$' ) === 0 ) {
				$message = 'File inclusion using variable (`%s`). Probably needs manual inspection.';
				$data    = [ $this->tokens[ $nextToken ]['content'] ];
				$this->phpcsFile->addWarning( $message, $nextToken, 'UsingVariable', $data );
				return;
			}

			if ( in_array( $this->tokens[ $nextToken ]['content'], $this->slashingFunctions, true ) === true ) {
				// The construct is using one of the slashing functions, it's probably correct.
				return;
			}

			if ( $this->is_targetted_token( $nextToken ) ) {
				$message = 'File inclusion using custom function ( `%s()` ). Must return local file source, as external URLs are prohibited on WordPress VIP. Probably needs manual inspection.';
				$data    = [ $this->tokens[ $nextToken ]['content'] ];
				$this->phpcsFile->addWarning( $message, $nextToken, 'UsingCustomFunction', $data );
				return;
			}

			$message = 'Absolute include path must be used. Use `get_template_directory()`, `get_stylesheet_directory()` or `plugin_dir_path()`.';
			$this->phpcsFile->addError( $message, $nextToken, 'NotAbsolutePath' );
			return;
		}

		if ( $this->tokens[ $nextToken ]['code'] === T_CONSTANT_ENCAPSED_STRING && filter_var( str_replace( [ '"', "'" ], '', $this->tokens[ $nextToken ]['content'] ), FILTER_VALIDATE_URL ) ) {
			$message = 'Include path must be local file source, external URLs are prohibited on WordPress VIP.';
			$this->phpcsFile->addError( $message, $nextToken, 'ExternalURL' );
			return;
		}

		$message = 'Absolute include path must be used. Use `get_template_directory()`, `get_stylesheet_directory()` or `plugin_dir_path()`.';
		$this->phpcsFile->addError( $message, $nextToken, 'NotAbsolutePath' );
	}

	/**
	 * Check if a content string contains a keyword in custom paths.
	 *
	 * @param string $content Content string.
	 *
	 * @return bool True if the string partially matches a keyword in $allowedCustomKeywords, false otherwise.
	 */
	private function has_custom_path( $content ) {
		foreach ( $this->allowedKeywords as $keyword ) {
			if ( strpos( $content, $keyword ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
