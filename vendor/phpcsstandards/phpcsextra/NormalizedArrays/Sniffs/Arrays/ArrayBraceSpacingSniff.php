<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\NormalizedArrays\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Fixers\SpacesFixer;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Arrays;

/**
 * Enforce consistent spacing for the open/close braces of arrays.
 *
 * The sniff allows for having different settings for:
 * - space between the `array` keyword and the open parenthesis for long arrays;
 * - spaces on the inside of the braces for single-line arrays;
 * - spaces on the inside of the braces for multi-line arrays;
 * - spaces on the inside of the braces for empty arrays.
 *
 * Note: If 'newline' is expected and _no_ new line is encountered, a new line will be
 * added, but no assumptions will be made about the intended indentation of the code.
 * This should be handled by a (separate) indentation sniff.
 *
 * @since 1.0.0
 */
final class ArrayBraceSpacingSniff implements Sniff
{

    /**
     * Number of spaces which should be between the `array` keyword and the open parenthesis for long arrays.
     *
     * Accepted values:
     * - (int) number of spaces.
     * - or `false` to turn this check off.
     *
     * Defaults to 0 spaces.
     *
     * @since 1.0.0
     *
     * @var int|false
     */
    public $keywordSpacing = 0;

    /**
     * Number of spaces to enforce between the braces for an empty array.
     *
     * Accepted values:
     * - (string) 'newline'
     * - (int) number of spaces.
     * - or `false` to turn this check off.
     *
     * Defaults to 0 spaces.
     *
     * @since 1.0.0
     *
     * @var string|int|false
     */
    public $spacesWhenEmpty = 0;

    /**
     * Number of spaces which should be on the inside of array braces for a single-line array.
     *
     * Accepted values:
     * - (int) number of spaces.
     * - or `false` to turn this check off.
     *
     * Defaults to 0 spaces.
     *
     * @since 1.0.0
     *
     * @var int|false
     */
    public $spacesSingleLine = 0;

    /**
     * Number of spaces which should be on the inside of array braces for a multi-line array.
     *
     * Accepted values:
     * - (string) 'newline'
     * - (int) number of spaces.
     * - or `false` to turn this check off.
     *
     * Defaults to 'newline'.
     *
     * @since 1.0.0
     *
     * @var string|int|false
     */
    public $spacesMultiLine = 'newline';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return Collections::arrayOpenTokensBC();
    }

    /**
     * Processes this test when one of its tokens is encountered.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The PHP_CodeSniffer file where the
     *                                               token was found.
     * @param int                         $stackPtr  The position in the PHP_CodeSniffer
     *                                               file's token stack where the token
     *                                               was found.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        /*
         * Normalize the public settings.
         */
        if ($this->keywordSpacing !== false) {
            $this->keywordSpacing = \max((int) $this->keywordSpacing, 0);
        }

        if ($this->spacesSingleLine !== false) {
            $this->spacesSingleLine = \max((int) $this->spacesSingleLine, 0);
        }

        if ($this->spacesMultiLine !== false && $this->spacesMultiLine !== 'newline') {
            $this->spacesMultiLine = \max((int) $this->spacesMultiLine, 0);
        }

        if ($this->spacesWhenEmpty !== false && $this->spacesWhenEmpty !== 'newline') {
            $this->spacesWhenEmpty = \max((int) $this->spacesWhenEmpty, 0);
        }

        if ($this->keywordSpacing === false
            && $this->spacesSingleLine === false
            && $this->spacesMultiLine === false
            && $this->spacesWhenEmpty === false
        ) {
            // Nothing to do. Why was the sniff turned on at all ?
            return;
        }

        $openClose = Arrays::getOpenClose($phpcsFile, $stackPtr);
        if ($openClose === false) {
            // Live coding, short list or real square brackets.
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $opener = $openClose['opener'];
        $closer = $openClose['closer'];

        /*
         * Check the spacing between the array keyword and the open parenthesis for long arrays.
         */
        if ($tokens[$stackPtr]['code'] === \T_ARRAY && $this->keywordSpacing !== false) {
            $error = 'There should be %s between the "array" keyword and the open parenthesis. Found: %s';
            $code  = 'SpaceAfterKeyword';

            SpacesFixer::checkAndFix(
                $phpcsFile,
                $stackPtr,
                $opener,
                $this->keywordSpacing,
                $error,
                $code,
                'error',
                0,
                'Space between array keyword and open brace'
            );
        }

        /*
         * Check for empty arrays.
         */
        $nextNonWhiteSpace = $phpcsFile->findNext(\T_WHITESPACE, ($opener + 1), null, true);
        if ($nextNonWhiteSpace === $closer) {
            if ($this->spacesWhenEmpty === false) {
                // Check was turned off.
                return;
            }

            $error = 'There should be %s between the array opener and closer for an empty array. Found: %s';
            $code  = 'EmptyArraySpacing';

            SpacesFixer::checkAndFix(
                $phpcsFile,
                $opener,
                $closer,
                $this->spacesWhenEmpty,
                $error,
                $code,
                'error',
                0,
                'Space between open and close brace for an empty array'
            );

            return;
        }

        /*
         * Check non-empty arrays.
         */
        if ($tokens[$opener]['line'] === $tokens[$closer]['line']) {
            // Single line array.
            if ($this->spacesSingleLine === false) {
                // Check was turned off.
                return;
            }

            $error = 'Expected %s after the array opener in a single line array. Found: %s';
            $code  = 'SpaceAfterArrayOpenerSingleLine';

            SpacesFixer::checkAndFix(
                $phpcsFile,
                $opener,
                $phpcsFile->findNext(\T_WHITESPACE, ($opener + 1), null, true),
                $this->spacesSingleLine,
                $error,
                $code,
                'error',
                0,
                'Space after array opener, single line array'
            );

            $error = 'Expected %s before the array closer in a single line array. Found: %s';
            $code  = 'SpaceBeforeArrayCloserSingleLine';

            SpacesFixer::checkAndFix(
                $phpcsFile,
                $closer,
                $phpcsFile->findPrevious(\T_WHITESPACE, ($closer - 1), null, true),
                $this->spacesSingleLine,
                $error,
                $code,
                'error',
                0,
                'Space before array closer, single line array'
            );

            return;
        }

        // Multi-line array.
        if ($this->spacesMultiLine === false) {
            // Check was turned off.
            return;
        }

        $error = 'Expected %s after the array opener in a multi line array. Found: %s';
        $code  = 'SpaceAfterArrayOpenerMultiLine';

        $nextNonWhitespace = $phpcsFile->findNext(\T_WHITESPACE, ($opener + 1), null, true);
        if ($this->spacesMultiLine === 'newline') {
            // Check for a trailing comment after the array opener and allow for it.
            if (($tokens[$nextNonWhitespace]['code'] === \T_COMMENT
                || isset(Tokens::$phpcsCommentTokens[$tokens[$nextNonWhitespace]['code']]) === true)
                && $tokens[$nextNonWhitespace]['line'] === $tokens[$opener]['line']
            ) {
                // We found a trailing comment after array opener. Treat that as the opener instead.
                $opener            = $nextNonWhitespace;
                $nextNonWhitespace = $phpcsFile->findNext(\T_WHITESPACE, ($opener + 1), null, true);
            }
        }

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $opener,
            $nextNonWhitespace,
            $this->spacesMultiLine,
            $error,
            $code,
            'error',
            0,
            'Space after array opener, multi-line array'
        );

        $error = 'Expected %s before the array closer in a multi line array. Found: %s';
        $code  = 'SpaceBeforeArrayCloserMultiLine';

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $closer,
            $phpcsFile->findPrevious(\T_WHITESPACE, ($closer - 1), null, true),
            $this->spacesMultiLine,
            $error,
            $code,
            'error',
            0,
            'Space before array closer, multi-line array'
        );
    }
}
