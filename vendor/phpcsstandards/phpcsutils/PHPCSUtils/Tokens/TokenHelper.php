<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Tokens;

/**
 * Helpers for working with tokens.
 *
 * @since 1.0.0
 */
final class TokenHelper
{

    /**
     * Check whether a PHP native token exists (for real).
     *
     * Under normal circumstances, checking whether a token exists (either defined by PHP or by PHPCS)
     * is as straight-forward as running `defined('T_TOKEN_NAME')`.
     *
     * Unfortunately, this doesn't work in all circumstances, most notably, if an external standard
     * also uses PHP-Parser or when code coverage is run on a standard using PHPUnit >= 9.3 (which uses PHP-Parser),
     * this logic breaks because PHP-Parser also polyfills tokens.
     * This method takes potentially polyfilled tokens from PHP-Parser into account and will regard the token
     * as undefined if it was declared by PHP-Parser.
     *
     * Note: this method only _needs_ to be used for PHP native tokens, not for PHPCS specific tokens.
     * Also, realistically, it only needs to be used for tokens introduced in PHP in recent versions (PHP 7.4 and up).
     * Having said that, the method _will_ also work correctly when a name of a PHPCS native token is passed or
     * of an older PHP native token.
     *
     * {@internal PHP native tokens have a positive integer value. PHPCS polyfilled tokens are strings.
     * PHP-Parser polyfilled tokens will always have a negative integer value < 0, which is how
     * these are filtered out.}
     *
     * @link https://github.com/sebastianbergmann/php-code-coverage/issues/798       PHP-Code-Coverage#798
     * @link https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/Lexer.php PHP-Parser Lexer code
     *
     * @since 1.0.0
     *
     * @param string $name The token name.
     *
     * @return bool
     */
    public static function tokenExists($name)
    {
        return (\defined($name) && (\is_int(\constant($name)) === false || \constant($name) > 0));
    }
}
