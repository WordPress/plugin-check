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

/**
 * Utility functions for working with identifier names.
 *
 * Identifier names in PHP are:
 * - {@link https://www.php.net/language.namespaces.definition namespace} names;
 * - {@link https://www.php.net/language.oop5.basic class},
 *   {@link https://www.php.net/language.oop5.traits trait},
 *   {@link https://www.php.net/language.oop5.interfaces interface} and
 *   {@link https://www.php.net/language.types.enumerations enum} names;
 * - {@link https://www.php.net/functions.user-defined function and method} names;
 * - {@link https://www.php.net/language.variables.basics variable} names;
 * - {@link https://www.php.net/language.constants constant} names.
 *
 * @since 1.0.0
 */
final class NamingConventions
{

    /**
     * Regular expression to check if a given identifier name is valid for use in PHP.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const PHP_LABEL_REGEX = '`^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$`';

    /**
     * Uppercase A-Z.
     *
     * @since      1.0.0
     * @deprecated 1.0.10
     *
     * @var string
     */
    const AZ_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Lowercase a-z.
     *
     * @since      1.0.0
     * @deprecated 1.0.10
     *
     * @var string
     */
    const AZ_LOWER = 'abcdefghijklmnopqrstuvwxyz';

    /**
     * Verify whether an arbitrary text string is valid as an identifier name in PHP.
     *
     * @since 1.0.0
     *
     * @param string $name The name to verify.
     *                     > Note: for variable names, the leading dollar sign - `$` - needs to be
     *                     removed prior to passing the name to this method.
     *
     * @return bool
     */
    public static function isValidIdentifierName($name)
    {
        if (\is_string($name) === false || $name === '' || \strpos($name, ' ') !== false) {
            return false;
        }

        return (\preg_match(self::PHP_LABEL_REGEX, $name) === 1);
    }

    /**
     * Check if two arbitrary identifier names will be seen as the same in PHP.
     *
     * This method should not be used for variable or constant names, but *should* be used
     * when comparing namespace, class/trait/interface and function names.
     *
     * Variable and constant names in PHP are case-sensitive, except for constants explicitely
     * declared case-insensitive using the third parameter for
     * {@link https://www.php.net/function.define `define()`}.
     *
     * All other names are case-insensitive for the most part, but as it's PHP, not completely.
     * Basically ASCII chars used are case-insensitive, but anything from 0x80 up is case-sensitive.
     *
     * This method takes this case-(in)sensitivity into account when comparing identifier names.
     *
     * Note: this method does not check whether the passed names would be valid for identifiers!
     * The {@see \PHPCSUtils\Utils\NamingConventions::isValidIdentifierName()} method should be used
     * to verify that, if necessary.
     *
     * @since 1.0.0
     *
     * @param string $nameA The first identifier name.
     * @param string $nameB The second identifier name.
     *
     * @return bool `TRUE` if these names would be considered the same in PHP; `FALSE` otherwise.
     */
    public static function isEqual($nameA, $nameB)
    {
        // Simple quick check first.
        if ($nameA === $nameB) {
            return true;
        }

        // Comparing via strcasecmp will only compare ASCII letters case-insensitively.
        return (\strcasecmp($nameA, $nameB) === 0);
    }
}
