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

use PHPCSUtils\BackCompat\Helper;

/**
 * Utility functions for checking the orthography of arbitrary text strings.
 *
 * > An orthography is a set of conventions for writing a language. It includes norms of spelling,
 * > hyphenation, capitalization, word breaks, emphasis, and punctuation.
 * > Source: https://en.wikipedia.org/wiki/Orthography
 *
 * @since 1.0.0
 */
final class Orthography
{

    /**
     * Characters which are considered terminal points for a sentence.
     *
     * @link https://www.thepunctuationguide.com/terminal-points.html Punctuation guide on terminal points.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const TERMINAL_POINTS = '.?!';

    /**
     * Check if the first character of an arbitrary text string is a capital letter.
     *
     * Letter characters which do not have a concept of lower/uppercase will
     * be accepted as correctly capitalized.
     *
     * @since 1.0.0
     *
     * @param string $textString The text string to examine.
     *                           This can be the contents of a text string token,
     *                           but also, for instance, a comment text.
     *                           Potential text delimiter quotes should be stripped
     *                           off a text string before passing it to this method.
     *                           Also see: {@see \PHPCSUtils\Utils\TextStrings::stripQuotes()}.
     *
     * @return bool `TRUE` when the first character is a capital letter or a letter
     *              which doesn't have a concept of capitalization.
     *              `FALSE` otherwise, including for non-letter characters.
     */
    public static function isFirstCharCapitalized($textString)
    {
        $textString = \ltrim($textString);
        return (\preg_match('`^[\p{Lu}\p{Lt}\p{Lo}]`u', $textString) > 0);
    }

    /**
     * Check if the first character of an arbitrary text string is a lowercase letter.
     *
     * @since 1.0.0
     *
     * @param string $textString The text string to examine.
     *                           This can be the contents of a text string token,
     *                           but also, for instance, a comment text.
     *                           Potential text delimiter quotes should be stripped
     *                           off a text string before passing it to this method.
     *                           Also see: {@see \PHPCSUtils\Utils\TextStrings::stripQuotes()}.
     *
     * @return bool `TRUE` when the first character is a lowercase letter.
     *              `FALSE` otherwise, including for letters which don't have a concept of
     *              capitalization and for non-letter characters.
     */
    public static function isFirstCharLowercase($textString)
    {
        $textString = \ltrim($textString);
        return (\preg_match('`^\p{Ll}`u', $textString) > 0);
    }

    /**
     * Check if the last character of an arbitrary text string is a valid punctuation character.
     *
     * @since 1.0.0
     *
     * @param string $textString   The text string to examine.
     *                             This can be the contents of a text string token,
     *                             but also, for instance, a comment text.
     *                             Potential text delimiter quotes should be stripped
     *                             off a text string before passing it to this method.
     *                             Also see: {@see \PHPCSUtils\Utils\TextStrings::stripQuotes()}.
     * @param string $allowedChars Characters which are considered valid punctuation
     *                             to end the text string.
     *                             Defaults to `'.?!'`, i.e. a full stop, question mark
     *                             or exclamation mark.
     *
     * @return bool
     */
    public static function isLastCharPunctuation($textString, $allowedChars = self::TERMINAL_POINTS)
    {
        $encoding   = Helper::getEncoding();
        $textString = \rtrim($textString);

        if (\function_exists('iconv_substr') === true) {
            $lastChar = \iconv_substr($textString, -1, 1, $encoding);
        } else {
            $lastChar = \substr($textString, -1);
        }

        if (\function_exists('iconv_strpos') === true) {
            return (\iconv_strpos($allowedChars, $lastChar, 0, $encoding) !== false);
        } else {
            return (\strpos($allowedChars, $lastChar) !== false);
        }
    }
}
