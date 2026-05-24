<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Fixers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Exceptions\LogicException;
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\TypeError;
use PHPCSUtils\Exceptions\UnexpectedTokenType;
use PHPCSUtils\Exceptions\ValueError;
use PHPCSUtils\Utils\Numbers;

/**
 * Utility to check and, if necessary, fix the whitespace between two tokens.
 *
 * @since 1.0.0
 */
final class SpacesFixer
{

    /**
     * Check the whitespace between two tokens, throw an error if it doesn't match the
     * expected whitespace and if relevant, fix it.
     *
     * Note:
     * - This method will not auto-fix if there is anything but whitespace between the two
     *   tokens. In that case, it will throw a non-fixable error/warning.
     * - If `'newline'` is expected and _no_ new line is encountered, a new line will be added,
     *   but no assumptions will be made about the intended indentation of the code.
     *   This should be handled by a (separate) indentation sniff.
     * - If `'newline'` is expected and multiple new lines are encountered, this will be accepted
     *   as valid.
     *   No assumptions are made about whether additional blank lines are allowed or not.
     *   If _exactly_ one line is desired, combine this fixer with the {@see \PHPCSUtils\Fixers\BlankLineFixer}
     *   (upcoming).
     * - The fixer will not leave behind any trailing spaces on the original line when fixing
     *   to `'newline'`, but it will not correct _existing_ trailing spaces when there already
     *   is a new line in place.
     * - This method can optionally record a metric for this check which will be displayed
     *   when the end-user requests the "info" report.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile      The file being scanned.
     * @param int                         $stackPtr       The position of the token which should be used
     *                                                    when reporting an issue.
     * @param int                         $secondPtr      The stack pointer to the second token.
     *                                                    This token can be before or after the `$stackPtr`,
     *                                                    but should only be separated from the `$stackPtr`
     *                                                    by whitespace and/or comments/annotations.
     * @param string|int                  $expectedSpaces Number of spaces to enforce.
     *                                                    Valid values:
     *                                                    - (int) Number of spaces. Must be `0` or more.
     *                                                    - (string) `'newline'`.
     * @param string                      $errorTemplate  Error message template.
     *                                                    Note: _The placeholder replacement phrase will be
     *                                                    in human readable English and include "spaces"/
     *                                                    "new line", so no need to include that in the template._
     *                                                    This string should contain two placeholders:
     *                                                    - `%1$s` = expected spaces phrase.
     *                                                    - `%2$s` = found spaces phrase.
     * @param string                      $errorCode      A violation code unique to the sniff message.
     *                                                    Defaults to `"Found"`.
     *                                                    It is strongly recommended to change this if
     *                                                    this fixer is used for different errors in the
     *                                                    same sniff.
     * @param string                      $errorType      Optional. Whether to report the issue as a
     *                                                    `"warning"` or an `"error"`. Defaults to `"error"`.
     * @param int                         $errorSeverity  Optional. The severity level for this message.
     *                                                    A value of `0` will be converted into the default
     *                                                    severity level.
     * @param string                      $metricName     Optional. The name of the metric to record.
     *                                                    This can be a short description phrase.
     *                                                    Leave empty to not record metrics.
     *
     * @return void
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr or $secondPtr parameters are not integers.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the tokens passed do not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the tokens passed are whitespace tokens.
     * @throws \PHPCSUtils\Exceptions\ValueError          If `$expectedSpaces` parameter is not a valid value.
     * @throws \PHPCSUtils\Exceptions\LogicException      If the tokens passed are separated by more than just
     *                                                    empty (whitespace + comments/annotations) tokens.
     */
    public static function checkAndFix(
        File $phpcsFile,
        $stackPtr,
        $secondPtr,
        $expectedSpaces,
        $errorTemplate,
        $errorCode = 'Found',
        $errorType = 'error',
        $errorSeverity = 0,
        $metricName = ''
    ) {
        $tokens = $phpcsFile->getTokens();

        /*
         * Validate the received function input.
         */

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (\is_int($secondPtr) === false) {
            throw TypeError::create(3, '$secondPtr', 'integer', $secondPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if (isset($tokens[$secondPtr]) === false) {
            throw OutOfBoundsStackPtr::create(3, '$secondPtr', $secondPtr);
        }

        if ($tokens[$stackPtr]['code'] === \T_WHITESPACE) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'any, except whitespace', 'T_WHITESPACE');
        }

        if ($tokens[$secondPtr]['code'] === \T_WHITESPACE) {
            throw UnexpectedTokenType::create(3, '$secondPtr', 'any, except whitespace', 'T_WHITESPACE');
        }

        $expected = false;
        if ($expectedSpaces === 'newline') {
            $expected = $expectedSpaces;
        } elseif (\is_int($expectedSpaces) === true && $expectedSpaces >= 0) {
            $expected = $expectedSpaces;
        } elseif (\is_string($expectedSpaces) === true && Numbers::isDecimalInt($expectedSpaces) === true) {
            $expected = (int) $expectedSpaces;
        }

        if ($expected === false) {
            $message = \sprintf('should be either "newline", 0 or a positive integer; %s given', $expectedSpaces);
            throw ValueError::create(4, '$expectedSpaces', $message);
        }

        $ptrA = $stackPtr;
        $ptrB = $secondPtr;
        if ($stackPtr > $secondPtr) {
            $ptrA = $secondPtr;
            $ptrB = $stackPtr;
        }

        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($ptrA + 1), null, true);
        if ($nextNonEmpty !== false && $nextNonEmpty < $ptrB) {
            throw LogicException::create(
                'The $stackPtr and the $secondPtr token must be adjacent tokens separated only'
                    . ' by whitespace and/or comments'
            );
        }

        /*
         * Determine how many spaces are between the two tokens.
         */

        $found       = 0;
        $foundPhrase = 'no spaces';
        if ($tokens[$ptrA]['line'] !== $tokens[$ptrB]['line']) {
            $found       = 'newline';
            $foundPhrase = 'a new line';
            if (($tokens[$ptrA]['line'] + 1) !== $tokens[$ptrB]['line']) {
                $foundPhrase = 'multiple new lines';
            }
        } elseif (($ptrA + 1) !== $ptrB) {
            if ($tokens[($ptrA + 1)]['code'] === \T_WHITESPACE) {
                $found       = $tokens[($ptrA + 1)]['length'];
                $foundPhrase = $found . (($found === 1) ? ' space' : ' spaces');
            } else {
                $found       = 'non-whitespace tokens';
                $foundPhrase = 'non-whitespace tokens';
            }
        }

        if ($metricName !== '') {
            $phpcsFile->recordMetric($stackPtr, $metricName, $foundPhrase);
        }

        if ($found === $expected) {
            return;
        }

        /*
         * Handle the violation message.
         */

        $expectedPhrase = 'no space';
        if ($expected === 'newline') {
            $expectedPhrase = 'a new line';
        } elseif ($expected === 1) {
            $expectedPhrase = $expected . ' space';
        } elseif ($expected > 1) {
            $expectedPhrase = $expected . ' spaces';
        }

        $fixable           = true;
        $nextNonWhitespace = $phpcsFile->findNext(\T_WHITESPACE, ($ptrA + 1), null, true);
        if ($nextNonWhitespace !== $ptrB) {
            // Comment found between the tokens and we don't know where it should go, so don't auto-fix.
            $fixable = false;
        }

        if ($found === 'newline'
            && $tokens[$ptrA]['code'] === \T_COMMENT
            && \substr($tokens[$ptrA]['content'], -2) !== '*/'
        ) {
            /*
             * $ptrA is a slash-style trailing comment, removing the new line would comment out
             * the code, so don't auto-fix.
             */
            $fixable = false;
        }

        $method  = 'add';
        $method .= ($fixable === true) ? 'Fixable' : '';
        $method .= ($errorType === 'error') ? 'Error' : 'Warning';

        $recorded = $phpcsFile->$method(
            $errorTemplate,
            $stackPtr,
            $errorCode,
            [$expectedPhrase, $foundPhrase],
            $errorSeverity
        );

        if ($fixable === false || $recorded === false) {
            return;
        }

        /*
         * Fix the violation.
         */

        $phpcsFile->fixer->beginChangeset();

        /*
         * Remove existing whitespace. No need to check if it's whitespace as otherwise the fixer
         * wouldn't have kicked in.
         */
        for ($i = ($ptrA + 1); $i < $ptrB; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        // If necessary: add the correct amount whitespace.
        if ($expected !== 0) {
            if ($expected === 'newline') {
                $phpcsFile->fixer->addContent($ptrA, $phpcsFile->eolChar);
            } else {
                $replacement = $tokens[$ptrA]['content'] . \str_repeat(' ', $expected);
                $phpcsFile->fixer->replaceToken($ptrA, $replacement);
            }
        }

        $phpcsFile->fixer->endChangeset();
    }
}
