<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 */

namespace WordPressVIPMinimum\Sniffs\Performance;

use PHP_CodeSniffer\Util\Tokens;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * Restricts usage of file_get_contents().
 */
class FetchingRemoteDataSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [ T_STRING ];
	}

	/**
	 * Process this test when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {

		$functionName = $this->tokens[ $stackPtr ]['content'];
		if ( $functionName !== 'file_get_contents' ) {
			return;
		}

		$data = [ $this->tokens[ $stackPtr ]['content'] ];

		$fileNameStackPtr = $this->phpcsFile->findNext( Tokens::$stringTokens, $stackPtr + 1, null, false, null, true );
		if ( $fileNameStackPtr === false ) {
			$message = '`%s()` is highly discouraged for remote requests, please use `wpcom_vip_file_get_contents()` or `vip_safe_wp_remote_get()` instead. If it\'s for a local file please use WP_Filesystem instead.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'FileGetContentsUnknown', $data );
		}

		$fileName = $this->tokens[ $fileNameStackPtr ]['content'];

		$isRemoteFile = ( strpos( $fileName, '://' ) !== false );
		if ( $isRemoteFile === true ) {
			$message = '`%s()` is highly discouraged for remote requests, please use `wpcom_vip_file_get_contents()` or `vip_safe_wp_remote_get()` instead.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'FileGetContentsRemoteFile', $data );
		}
	}
}
