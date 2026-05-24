<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\AbstractSniffs\AbstractArrayDeclarationSniff;

/**
 * Forbid arrays which contain both array items with an explicit key and array items without a key set.
 *
 * @since 1.0.0
 */
final class MixedKeyedUnkeyedArraySniff extends AbstractArrayDeclarationSniff
{

    /**
     * Whether or not any array items with a key were encountered in the array.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    private $hasKeys = false;

    /**
     * Cache of any array items encountered without keys up to the time an array item _with_ a key is encountered.
     *
     * @since 1.0.0
     *
     * @var array<int, int> Key is the item number; value the stack point to the first non empty token in the item.
     */
    private $itemsWithoutKey = [];

    /**
     * Process the array declaration.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The PHP_CodeSniffer file where the
     *                                               token was found.
     *
     * @return void
     */
    public function processArray(File $phpcsFile)
    {
        // Reset properties before processing this array.
        $this->hasKeys         = false;
        $this->itemsWithoutKey = [];

        parent::processArray($phpcsFile);
    }

    /**
     * Process the tokens in an array key.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The PHP_CodeSniffer file where the
     *                                               token was found.
     * @param int                         $startPtr  The stack pointer to the first token in the "key" part of
     *                                               an array item.
     * @param int                         $endPtr    The stack pointer to the last token in the "key" part of
     *                                               an array item.
     * @param int                         $itemNr    Which item in the array is being handled.
     *
     * @return void
     */
    public function processKey(File $phpcsFile, $startPtr, $endPtr, $itemNr)
    {
        $this->hasKeys = true;

        // Process any previously encountered items without keys.
        if (empty($this->itemsWithoutKey) === false) {
            foreach ($this->itemsWithoutKey as $itemNr => $stackPtr) {
                $phpcsFile->addError(
                    'Inconsistent array detected. A mix of keyed and unkeyed array items is not allowed.'
                        . ' The array item in position %d does not have an array key.',
                    $stackPtr,
                    'Found',
                    [$itemNr]
                );
            }

            // No need to do this again.
            $this->itemsWithoutKey = [];
        }
    }

    /**
     * Process an array item without an array key.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The PHP_CodeSniffer file where the
     *                                               token was found.
     * @param int                         $startPtr  The stack pointer to the first token in the array item,
     *                                               which in this case will be the first token of the array
     *                                               value part of the array item.
     * @param int                         $itemNr    Which item in the array is being handled.
     *
     * @return void
     */
    public function processNoKey(File $phpcsFile, $startPtr, $itemNr)
    {
        $firstNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, $startPtr, null, true);
        if ($firstNonEmpty === false || $this->tokens[$firstNonEmpty]['code'] === \T_COMMA) {
            // Shouldn't really be possible, but this must be a parse error (empty array item).
            return;
        }

        // If we already know there are keys in the array, throw an error message straight away.
        if ($this->hasKeys === true) {
            $phpcsFile->addError(
                'Inconsistent array detected. A mix of keyed and unkeyed array items is not allowed.'
                    . ' The array item in position %d does not have an array key.',
                $firstNonEmpty,
                'Found',
                [$itemNr]
            );
        } else {
            // Save the array item info for later in case we do encounter an array key later on in the array.
            $this->itemsWithoutKey[$itemNr] = $firstNonEmpty;
        }
    }
}
