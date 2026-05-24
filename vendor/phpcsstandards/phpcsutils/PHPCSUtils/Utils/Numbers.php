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
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\TypeError;
use PHPCSUtils\Exceptions\UnexpectedTokenType;

/**
 * Utility functions for working with integer/float tokens.
 *
 * PHP 7.4 introduced numeric literal separators. PHPCS backfills this since PHPCS 3.5.3/4.
 * PHP 8.1 introduced an explicit octal notation. This is backfilled in PHPCS since PHPCS 3.7.0.
 *
 * While there are currently no unsupported numeric syntaxes, the methods in this class
 * can still be useful for external standards which need to examine the
 * contents of `T_LNUMBER` or `T_DNUMBER` tokens.
 *
 * @link https://www.php.net/migration74.new-features.php#migration74.new-features.core.numeric-literal-separator
 *       PHP Manual on numeric literal separators.
 * @link https://www.php.net/manual/en/migration81.new-features.php#migration81.new-features.core.octal-literal-prefix
 *       PHP Manual on the introduction of the integer octal literal prefix.
 *
 * @since 1.0.0
 */
final class Numbers
{

    /**
     * Regex to determine whether the contents of an arbitrary string represents a decimal integer.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const REGEX_DECIMAL_INT = '`^(?:0|[1-9](?:[0-9_]*[0-9])?)$`D';

    /**
     * Regex to determine whether the contents of an arbitrary string represents an octal integer.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const REGEX_OCTAL_INT = '`^0[o]?[0-7](?:[0-7_]*[0-7])?$`iD';

    /**
     * Regex to determine whether the contents of an arbitrary string represents a binary integer.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const REGEX_BINARY_INT = '`^0b[0-1](?:[0-1_]*[0-1])?$`iD';

    /**
     * Regex to determine whether the contents of an arbitrary string represents a hexidecimal integer.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const REGEX_HEX_INT = '`^0x[0-9A-F](?:[0-9A-F_]*[0-9A-F])?$`iD';

    /**
     * Regex to determine whether the contents of an arbitrary string represents a float.
     *
     * @link https://www.php.net/language.types.float PHP Manual on floats
     *
     * @since 1.0.0
     *
     * @var string
     */
    const REGEX_FLOAT = '`
        ^(?:
            (?:
                (?:
                    (?P<LNUM>[0-9](?:[0-9_]*[0-9])?)
                |
                    (?P<DNUM>((?:[0-9](?:[0-9_]*[0-9])?)*\.(?P>LNUM)|(?P>LNUM)\.(?:[0-9](?:[0-9_]*[0-9])?)*))
                )
                [e][+-]?(?P>LNUM)
            )
            |
            (?P>DNUM)
            |
            (?:0|[1-9](?:[0-9_]*[0-9])?)
        )$
        `ixD';

    /**
     * Retrieve information about a number token.
     *
     * Helper function to deal with numeric literals, potentially with underscore separators
     * and/or explicit octal notation.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of a T_LNUMBER or T_DNUMBER token.
     *
     * @return array<string, string|int> An array with information about the number.
     *               The format of the array return value is:
     *               ```php
     *               array(
     *                 'orig_content' => string, // The original content of the token(s);
     *                 'content'      => string, // The content, underscore(s) removed;
     *                 'code'         => int,    // The token code of the number, either
     *                                           // T_LNUMBER or T_DNUMBER.
     *                 'type'         => string, // The token type, either 'T_LNUMBER'
     *                                           // or 'T_DNUMBER'.
     *                 'decimal'      => string, // The decimal value of the number;
     *                 'last_token'   => int,    // The stackPtr to the last token which was
     *                                           // part of the number.
     *                                           // At this time, this will be always be the original
     *                                           // stackPtr. This may change in the future if
     *                                           // new numeric syntaxes would be added to PHP.
     *               )
     *               ```
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_LNUMBER` or `T_DNUMBER` token.
     */
    public static function getCompleteNumber(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_LNUMBER && $tokens[$stackPtr]['code'] !== \T_DNUMBER) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_LNUMBER or T_DNUMBER', $tokens[$stackPtr]['type']);
        }

        $content = $tokens[$stackPtr]['content'];
        return [
            'orig_content' => $content,
            'content'      => \str_replace('_', '', $content),
            'code'         => $tokens[$stackPtr]['code'],
            'type'         => $tokens[$stackPtr]['type'],
            'decimal'      => self::getDecimalValue($content),
            'last_token'   => $stackPtr,
        ];
    }

    /**
     * Get the decimal number value of a numeric string.
     *
     * Takes PHP 7.4 numeric literal separators and explicit octal literals in numbers into account.
     *
     * @since 1.0.0
     *
     * @param string $textString Arbitrary text string.
     *                           This text string should be the (combined) token content of
     *                           one or more tokens which together represent a number in PHP.
     *
     * @return string|false Decimal number as a string or `FALSE` if the passed parameter
     *                      was not a numeric string.
     *                      > Note: floating point numbers with exponent will not be expanded,
     *                      but returned as-is.
     */
    public static function getDecimalValue($textString)
    {
        if (\is_string($textString) === false || $textString === '') {
            return false;
        }

        /*
         * Remove potential PHP 7.4 numeric literal separators.
         *
         * {@internal While the is..() functions also do this, this is still needed
         * here to allow the hexdec(), bindec() functions to work correctly and for
         * the decimal/float to return a cross-version compatible decimal value.}
         */
        $textString = \str_replace('_', '', $textString);

        if (self::isDecimalInt($textString) === true) {
            return $textString;
        }

        if (self::isHexidecimalInt($textString) === true) {
            return (string) \hexdec($textString);
        }

        if (self::isBinaryInt($textString) === true) {
            return (string) \bindec($textString);
        }

        if (self::isOctalInt($textString) === true) {
            return (string) \octdec($textString);
        }

        if (self::isFloat($textString) === true) {
            return $textString;
        }

        return false;
    }

    /**
     * Verify whether the contents of an arbitrary string represents a decimal integer.
     *
     * Takes PHP 7.4 numeric literal separators in numbers into account in the regex.
     *
     * @since 1.0.0
     *
     * @param string $textString Arbitrary string.
     *
     * @return bool
     */
    public static function isDecimalInt($textString)
    {
        if (\is_string($textString) === false || $textString === '') {
            return false;
        }

        return (\preg_match(self::REGEX_DECIMAL_INT, $textString) === 1);
    }

    /**
     * Verify whether the contents of an arbitrary string represents a hexidecimal integer.
     *
     * Takes PHP 7.4 numeric literal separators in numbers into account in the regex.
     *
     * @since 1.0.0
     *
     * @param string $textString Arbitrary string.
     *
     * @return bool
     */
    public static function isHexidecimalInt($textString)
    {
        if (\is_string($textString) === false || $textString === '') {
            return false;
        }

        return (\preg_match(self::REGEX_HEX_INT, $textString) === 1);
    }

    /**
     * Verify whether the contents of an arbitrary string represents a binary integer.
     *
     * Takes PHP 7.4 numeric literal separators in numbers into account in the regex.
     *
     * @since 1.0.0
     *
     * @param string $textString Arbitrary string.
     *
     * @return bool
     */
    public static function isBinaryInt($textString)
    {
        if (\is_string($textString) === false || $textString === '') {
            return false;
        }

        return (\preg_match(self::REGEX_BINARY_INT, $textString) === 1);
    }

    /**
     * Verify whether the contents of an arbitrary string represents an octal integer.
     *
     * Takes PHP 7.4 numeric literal separators and explicit octal literals in numbers into account in the regex.
     *
     * @since 1.0.0
     *
     * @param string $textString Arbitrary string.
     *
     * @return bool
     */
    public static function isOctalInt($textString)
    {
        if (\is_string($textString) === false || $textString === '') {
            return false;
        }

        return (\preg_match(self::REGEX_OCTAL_INT, $textString) === 1);
    }

    /**
     * Verify whether the contents of an arbitrary string represents a floating point number.
     *
     * Takes PHP 7.4 numeric literal separators in numbers into account in the regex.
     *
     * @since 1.0.0
     *
     * @param string $textString Arbitrary string.
     *
     * @return bool
     */
    public static function isFloat($textString)
    {
        if (\is_string($textString) === false || $textString === '') {
            return false;
        }

        return (\preg_match(self::REGEX_FLOAT, $textString) === 1);
    }
}
