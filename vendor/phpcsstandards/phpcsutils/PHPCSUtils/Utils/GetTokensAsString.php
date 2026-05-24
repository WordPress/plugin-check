<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Utils;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\TypeError;

/**
 * Utility functions to retrieve the content of a set of tokens as a string.
 *
 * In contrast to the PHPCS native {@see \PHP_CodeSniffer\Files\File::getTokensAsString()} method,
 * which has `$length` as the third parameter, all methods in this class expect a stack pointer to
 * an `$end` token (inclusive) as the third parameter.
 *
 * @since 1.0.0 The principle of this class is loosely based on and inspired by the
 *              {@see \PHP_CodeSniffer\Files\File::getTokensAsString()} method in the
 *              PHPCS native `File` class.
 *              Also see {@see \PHPCSUtils\BackCompat\BCFile::getTokensAsString()}.
 */
final class GetTokensAsString
{

    /**
     * Retrieve the tab-replaced content of the tokens from the specified start position in
     * the token stack to the specified end position (inclusive).
     *
     * Alias for the {@see \PHPCSUtils\Utils\GetTokensAsString::tabReplaced()} method.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $start     The position to start from in the token stack.
     * @param int                         $end       The position to end at in the token stack (inclusive).
     *
     * @return string The token contents.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $start parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the $start token does not exist in the $phpcsFile.
     */
    public static function normal(File $phpcsFile, $start, $end)
    {
        return self::tabReplaced($phpcsFile, $start, $end);
    }

    /**
     * Retrieve the tab-replaced content of the tokens from the specified start position in
     * the token stack to the specified end position (inclusive).
     *
     * This is the default behaviour for PHPCS.
     *
     * If the `tabWidth` is set, either via a (custom) ruleset, the config file or by passing it
     * on the command-line, PHPCS will automatically replace tabs with spaces.
     * The `'content'` index key in the `$tokens` array will contain the tab-replaced content.
     * The `'orig_content'` index key in the `$tokens` array will contain the original content.
     *
     * @see \PHP_CodeSniffer\Files\File::getTokensAsString()   Similar length-based function.
     * @see \PHPCSUtils\BackCompat\BCFile::getTokensAsString() Cross-version compatible version of the original.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $start     The position to start from in the token stack.
     * @param int                         $end       The position to end at in the token stack (inclusive).
     *
     * @return string The token contents.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $start parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the $start token does not exist in the $phpcsFile.
     */
    public static function tabReplaced(File $phpcsFile, $start, $end)
    {
        return self::getString($phpcsFile, $start, $end);
    }

    /**
     * Retrieve the original content of the tokens from the specified start position in
     * the token stack to the specified end position (inclusive).
     *
     * In contrast to the {@see GetTokensAsString::tabReplaced()} method, this method will return
     * the original token content for the specified tokens.
     * That means that if the original content contained tabs, the return value of this function
     * will also contain tabs.
     *
     * The same can be achieved in PHPCS since version 3.3.0, by calling the
     * {@see \PHP_CodeSniffer\Files\File::getTokensAsString()} method and passing `true` as the
     * value for the `$origContent` parameter.
     *
     * @see \PHP_CodeSniffer\Files\File::getTokensAsString()   Similar length-based function.
     * @see \PHPCSUtils\BackCompat\BCFile::getTokensAsString() Cross-version compatible version of the original.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $start     The position to start from in the token stack.
     * @param int                         $end       The position to end at in the token stack (inclusive).
     *
     * @return string The token contents.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $start parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the $start token does not exist in the $phpcsFile.
     */
    public static function origContent(File $phpcsFile, $start, $end)
    {
        return self::getString($phpcsFile, $start, $end, true);
    }

    /**
     * Retrieve the content of the tokens from the specified start position in the token
     * stack to the specified end position (inclusive) without comments.
     *
     * @see \PHP_CodeSniffer\Files\File::getTokensAsString() Loosely related function.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $start     The position to start from in the token stack.
     * @param int                         $end       The position to end at in the token stack (inclusive).
     *
     * @return string The token contents stripped off comments.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $start parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the $start token does not exist in the $phpcsFile.
     */
    public static function noComments(File $phpcsFile, $start, $end)
    {
        return self::getString($phpcsFile, $start, $end, false, true);
    }

    /**
     * Retrieve the code-tokens only content of the tokens from the specified start position
     * in the token stack to the specified end position (inclusive) without whitespace or comments.
     *
     * This is useful, for instance, to retrieve a namespace name without stray whitespace or comments.
     * Use this function selectively and with care!
     *
     * @see \PHP_CodeSniffer\Files\File::getTokensAsString() Loosely related function.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $start     The position to start from in the token stack.
     * @param int                         $end       The position to end at in the token stack (inclusive).
     *
     * @return string The token contents stripped off comments and whitespace.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $start parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the $start token does not exist in the $phpcsFile.
     */
    public static function noEmpties(File $phpcsFile, $start, $end)
    {
        return self::getString($phpcsFile, $start, $end, false, true, true);
    }

    /**
     * Retrieve the content of the tokens from the specified start position in the token
     * stack to the specified end position (inclusive) with all consecutive whitespace tokens - tabs,
     * new lines, multiple spaces - replaced by a single space and optionally without comments.
     *
     * @see \PHP_CodeSniffer\Files\File::getTokensAsString() Loosely related function.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile     The file being scanned.
     * @param int                         $start         The position to start from in the token stack.
     * @param int                         $end           The position to end at in the token stack (inclusive).
     * @param bool                        $stripComments Whether comments should be stripped from the contents.
     *                                                   Defaults to `false`.
     *
     * @return string The token contents with compacted whitespace and optionally stripped off comments.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $start parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the $start token does not exist in the $phpcsFile.
     */
    public static function compact(File $phpcsFile, $start, $end, $stripComments = false)
    {
        return self::getString($phpcsFile, $start, $end, false, $stripComments, false, true);
    }

    /**
     * Retrieve the content of the tokens from the specified start position in the token stack
     * to the specified end position (inclusive).
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile       The file being scanned.
     * @param int                         $start           The position to start from in the token stack.
     * @param int                         $end             The position to end at in the token stack (inclusive).
     * @param bool                        $origContent     Whether the original content or the tab replaced
     *                                                     content should be used.
     *                                                     Defaults to `false` (= tabs replaced with spaces).
     * @param bool                        $stripComments   Whether comments should be stripped from the contents.
     *                                                     Defaults to `false`.
     * @param bool                        $stripWhitespace Whether whitespace should be stripped from the contents.
     *                                                     Defaults to `false`.
     * @param bool                        $compact         Whether all consecutive whitespace tokens should be
     *                                                     replaced with a single space. Defaults to `false`.
     *
     * @return string The token contents.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $start parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the $start token does not exist in the $phpcsFile.
     */
    protected static function getString(
        File $phpcsFile,
        $start,
        $end,
        $origContent = false,
        $stripComments = false,
        $stripWhitespace = false,
        $compact = false
    ) {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($start) === false) {
            throw TypeError::create(2, '$start', 'integer', $start);
        }

        if (isset($tokens[$start]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$start', $start);
        }

        if (\is_int($end) === false || $end < $start) {
            return '';
        }

        $str = '';
        if ($end >= $phpcsFile->numTokens) {
            $end = ($phpcsFile->numTokens - 1);
        }

        $lastAdded = null;
        for ($i = $start; $i <= $end; $i++) {
            if ($stripComments === true && isset(Tokens::$commentTokens[$tokens[$i]['code']])) {
                continue;
            }

            if ($stripWhitespace === true && $tokens[$i]['code'] === \T_WHITESPACE) {
                continue;
            }

            if ($compact === true && $tokens[$i]['code'] === \T_WHITESPACE) {
                if (isset($lastAdded) === false || $tokens[$lastAdded]['code'] !== \T_WHITESPACE) {
                    $str      .= ' ';
                    $lastAdded = $i;
                }
                continue;
            }

            // If tabs are being converted to spaces by the tokenizer, the
            // original content should be used instead of the converted content.
            if ($origContent === true && isset($tokens[$i]['orig_content']) === true) {
                $str .= $tokens[$i]['orig_content'];
            } else {
                $str .= $tokens[$i]['content'];
            }

            $lastAdded = $i;
        }

        return $str;
    }
}
