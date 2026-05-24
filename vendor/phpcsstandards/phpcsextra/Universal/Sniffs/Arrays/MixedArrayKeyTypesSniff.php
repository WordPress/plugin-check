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
 * Forbid arrays which contain both array items with numeric keys as well as array items with string keys.
 *
 * @since 1.0.0
 */
final class MixedArrayKeyTypesSniff extends AbstractArrayDeclarationSniff
{

    /**
     * Whether a string key was encountered.
     *
     * @var bool
     */
    private $seenStringKey = false;

    /**
     * Whether a numeric key was encountered.
     *
     * @var bool
     */
    private $seenNumericKey = false;

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
        $this->seenStringKey  = false;
        $this->seenNumericKey = false;

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
     * @return true|void Returning `TRUE` will short-circuit the array item loop and stop processing.
     *                   In effect, this means that the sniff will not examine the double arrow, the array
     *                   value or comma for this array item and will not process any array items after this one.
     */
    public function processKey(File $phpcsFile, $startPtr, $endPtr, $itemNr)
    {
        $key = $this->getActualArrayKey($phpcsFile, $startPtr, $endPtr);
        if (isset($key) === false) {
            // Key could not be determined.
            return;
        }

        $integerKey = \is_int($key);

        // Handle integer key.
        if ($integerKey === true) {
            if ($this->seenStringKey === false) {
                if ($this->seenNumericKey !== false) {
                    // Already seen a numeric key before.
                    return;
                }

                $this->seenNumericKey = true;
                return;
            }

            // Ok, so we've seen a string key before and now see an explicit numeric key.
            $firstNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, $startPtr, null, true);
            $phpcsFile->addError(
                'Arrays should have either numeric keys or string keys. Explicit numeric key detected,'
                    . ' while all previous keys in this array were string keys.',
                $firstNonEmpty,
                'ExplicitNumericKey'
            );

            // Stop the loop.
            return true;
        }

        // Handle string key.
        if ($this->seenNumericKey === false) {
            if ($this->seenStringKey !== false) {
                // Already seen a string key before.
                return;
            }

            $this->seenStringKey = true;
            return;
        }

        // Ok, so we've seen a numeric key before and now see a string key.
        $firstNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, $startPtr, null, true);
        $phpcsFile->addError(
            'Arrays should have either numeric keys or string keys. String key detected,'
                . ' while all previous keys in this array were integer based keys.',
            $firstNonEmpty,
            'StringKey'
        );

        // Stop the loop.
        return true;
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
     * @return true|void Returning `TRUE` will short-circuit the array item loop and stop processing.
     *                   In effect, this means that the sniff will not examine the array value or
     *                   comma for this array item and will not process any array items after this one.
     */
    public function processNoKey(File $phpcsFile, $startPtr, $itemNr)
    {
        if ($this->seenStringKey === false) {
            if ($this->seenNumericKey !== false) {
                // Already seen a numeric key before.
                return;
            }

            $this->seenNumericKey = true;
            return;
        }

        // Ok, so we've seen a string key before and now see an implicit numeric key.
        $firstNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, $startPtr, null, true);
        $phpcsFile->addError(
            'Arrays should have either numeric keys or string keys. Implicit numeric key detected,'
                . ' while all previous keys in this array were string keys.',
            $firstNonEmpty,
            'ImplicitNumericKey'
        );

        // Stop the loop.
        return true;
    }
}
