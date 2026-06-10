<?php
/**
 * NonceVerificationSniff
 *
 * Extends the WPCS NonceVerificationSniff to fix false positives
 * from empty() and isset() checks on superglobals.
 *
 * @package plugin-check
 * @since 1.7.0
 */

namespace PluginCheckCS\PluginCheck\Sniffs\Security;

use PHP_CodeSniffer\Files\File;
use WordPressCS\WordPress\Helpers\ContextHelper;

/**
 * Custom nonce verification sniff that correctly handles empty() and isset().
 *
 * The WPCS parent sniff treats empty() and isset() on superglobals as needing
 * a nonce check after the call. This override treats them as safe since they
 * only check existence rather than processing the form data value.
 *
 * @since 1.7.0
 */
class NonceVerificationSniff extends \WordPressCS\WordPress\Sniffs\Security\NonceVerificationSniff {

	/**
	 * Determine whether a nonce check is needed for the current superglobal.
	 *
	 * Overrides the parent to return false for empty()/isset() contexts,
	 * since those only check existence and do not process form data.
	 *
	 * @since 1.7.0
	 *
	 * @param int   $stackPtr   The position of the current token in the stack of tokens.
	 * @param array $cache_keys The keys for the applicable cache.
	 *
	 * @return string|false String "before" or "after" if a nonce check is needed.
	 *                      FALSE when no nonce check is needed.
	 */
	protected function needs_nonce_check( $stackPtr, array $cache_keys ) {
		if ( ContextHelper::is_in_isset_or_empty( $this->phpcsFile, $stackPtr ) ) {
			// empty() and isset() only check existence, they do not process the value.
			return false;
		}

		return parent::needs_nonce_check( $stackPtr, $cache_keys );
	}
}
