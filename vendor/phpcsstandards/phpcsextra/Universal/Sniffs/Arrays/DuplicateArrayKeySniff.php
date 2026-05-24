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
use PHPCSUtils\BackCompat\Helper;

/**
 * Detect duplicate array keys in array declarations.
 *
 * This sniff will detect duplicate keys with high precision, though any array key
 * set via a variable/constant/function call is excluded from the examination.
 *
 * The sniff will handle the change in how numeric array keys are set
 * since PHP 8.0 and will flag keys which would be duplicates cross-version.
 * {@link https://wiki.php.net/rfc/negative_array_index}
 *
 * @since 1.0.0
 */
final class DuplicateArrayKeySniff extends AbstractArrayDeclarationSniff
{

    /**
     * Keep track of which array keys have been seen already on PHP < 8.0.
     *
     * @since 1.0.0
     *
     * @var array<int, array<string, int>>
     */
    private $keysSeenLt8 = [];

    /**
     * Keep track of which array keys have been seen already on PHP >= 8.0.
     *
     * @since 1.0.0
     *
     * @var array<int, array<string, int>>
     */
    private $keysSeenGt8 = [];

    /**
     * Keep track of the maximum seen integer key to know what the next value will be for
     * array items without a key on PHP < 8.0.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $currentMaxIntKeyLt8;

    /**
     * Keep track of the maximum seen integer key to know what the next value will be for
     * array items without a key on PHP >= 8.0.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $currentMaxIntKeyGt8;

    /**
     * PHP version as configured or -1 if unknown.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $phpVersion;

    /**
     * Process every part of the array declaration.
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
        $this->keysSeenLt8 = [];
        $this->keysSeenGt8 = [];

        if (isset($this->phpVersion) === false) {
            // Set default value to prevent this code from running every time the sniff is triggered.
            $this->phpVersion = -1;

            $phpVersion = Helper::getConfigData('php_version');
            if ($phpVersion !== null) {
                $this->phpVersion = (int) $phpVersion;
            }
        }

        unset($this->currentMaxIntKeyLt8, $this->currentMaxIntKeyGt8);

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
        $key = $this->getActualArrayKey($phpcsFile, $startPtr, $endPtr);

        if (isset($key) === false) {
            // Key could not be determined.
            return;
        }

        $integerKey = \is_int($key);

        $errorMsg  = 'Duplicate array key found. The value will be overwritten%s.'
            . ' The %s array key "%s" was first seen on line %d';
        $errorCode = 'Found';
        $errors    = [];
        $baseData  = [
            ($integerKey === true) ? 'integer' : 'string',
            $key,
        ];

        /*
         * Check if we've seen the key before.
         */
        if (($this->phpVersion === -1 || $this->phpVersion < 80000)
            && isset($this->keysSeenLt8[$key]) === true
        ) {
            $errors['phplt8'] = [
                'data_subset'  => $baseData,
                'error_suffix' => '',
                'code_suffix'  => '',
            ];

            if ($integerKey === true) {
                $errors['phplt8']['error_suffix'] = ' when using PHP < 8.0';
                $errors['phplt8']['code_suffix']  = 'ForPHPlt80';
            }

            $firstSeen              = $this->keysSeenLt8[$key];
            $firstNonEmptyFirstSeen = $phpcsFile->findNext(Tokens::$emptyTokens, $firstSeen['ptr'], null, true);

            $errors['phplt8']['data_subset'][] = $this->tokens[$firstNonEmptyFirstSeen]['line'];
        }

        if (($this->phpVersion === -1 || $this->phpVersion >= 80000)
            && isset($this->keysSeenGt8[$key]) === true
        ) {
            $errors['phpgt8'] = [
                'data_subset'  => $baseData,
                'error_suffix' => '',
                'code_suffix'  => '',
            ];

            if ($integerKey === true) {
                $errors['phpgt8']['error_suffix'] = ' when using PHP >= 8.0';
                $errors['phpgt8']['code_suffix']  = 'ForPHPgte80';
            }

            $firstSeen              = $this->keysSeenGt8[$key];
            $firstNonEmptyFirstSeen = $phpcsFile->findNext(Tokens::$emptyTokens, $firstSeen['ptr'], null, true);

            $errors['phpgt8']['data_subset'][] = $this->tokens[$firstNonEmptyFirstSeen]['line'];
        }

        /*
         * Throw the error(s).
         *
         * If no PHP version was passed, throw errors both for PHP < 8.0 and PHP >= 8.0.
         * If a PHP version was set, only throw the error appropriate for the selected PHP version.
         * If both errors would effectively be the same, only throw one.
         */
        if ($errors !== []) {
            $firstNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, $startPtr, null, true);

            if (isset($errors['phplt8'], $errors['phpgt8'])
                && $errors['phplt8']['data_subset'] === $errors['phpgt8']['data_subset']
            ) {
                // Only throw the error once if it would be the same for PHP < 8.0 and PHP >= 8.0.
                $data = $errors['phplt8']['data_subset'];
                \array_unshift($data, '');

                $phpcsFile->addError($errorMsg, $firstNonEmpty, $errorCode, $data);
                return;
            }

            if (isset($errors['phplt8'])) {
                $code = $errorCode . $errors['phplt8']['code_suffix'];
                $data = $errors['phplt8']['data_subset'];
                \array_unshift($data, $errors['phplt8']['error_suffix']);

                $phpcsFile->addError($errorMsg, $firstNonEmpty, $code, $data);
            }

            if (isset($errors['phpgt8'])) {
                $code = $errorCode . $errors['phpgt8']['code_suffix'];
                $data = $errors['phpgt8']['data_subset'];
                \array_unshift($data, $errors['phpgt8']['error_suffix']);

                $phpcsFile->addError($errorMsg, $firstNonEmpty, $code, $data);
            }

            return;
        }

        /*
         * Key not seen before. Add to arrays.
         */
        $this->keysSeenLt8[$key] = [
            'item' => $itemNr,
            'ptr'  => $startPtr,
        ];
        $this->keysSeenGt8[$key] = [
            'item' => $itemNr,
            'ptr'  => $startPtr,
        ];

        if ($integerKey === true) {
            if ((isset($this->currentMaxIntKeyLt8) === false && $key > -1)
                || (isset($this->currentMaxIntKeyLt8) === true && $key > $this->currentMaxIntKeyLt8)
            ) {
                $this->currentMaxIntKeyLt8 = $key;
            }

            if (isset($this->currentMaxIntKeyGt8) === false
                || $key > $this->currentMaxIntKeyGt8
            ) {
                $this->currentMaxIntKeyGt8 = $key;
            }
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
        // Track the key for PHP < 8.0.
        if (isset($this->currentMaxIntKeyLt8) === false) {
            $this->currentMaxIntKeyLt8 = -1;
        }

        ++$this->currentMaxIntKeyLt8;
        $this->keysSeenLt8[$this->currentMaxIntKeyLt8] = [
            'item' => $itemNr,
            'ptr'  => $startPtr,
        ];

        // Track the key for PHP 8.0+.
        if (isset($this->currentMaxIntKeyGt8) === false) {
            $this->currentMaxIntKeyGt8 = -1;
        }

        ++$this->currentMaxIntKeyGt8;
        $this->keysSeenGt8[$this->currentMaxIntKeyGt8] = [
            'item' => $itemNr,
            'ptr'  => $startPtr,
        ];
    }
}
