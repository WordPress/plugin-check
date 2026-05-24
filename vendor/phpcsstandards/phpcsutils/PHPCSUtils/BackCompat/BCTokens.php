<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\BackCompat;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Exceptions\InvalidTokenArray;
use PHPCSUtils\Tokens\Collections;

/**
 * Token arrays related utility methods.
 *
 * PHPCS provides a number of static token arrays in the {@see \PHP_CodeSniffer\Util\Tokens}
 * class.
 * Some of these token arrays will not be available in older PHPCS versions.
 * Some will not contain the same set of tokens across PHPCS versions.
 *
 * This class is a compatibility layer to allow for retrieving these token arrays
 * with a consistent token content across PHPCS versions.
 * The one caveat is that the token constants do need to be available.
 *
 * Recommended usage:
 * Only use the methods in this class when needed. I.e. when your sniff unit tests indicate
 * a PHPCS cross-version compatibility issue related to inconsistent token arrays.
 *
 * All PHPCS token arrays are supported, though only a limited number of them are different
 * across PHPCS versions.
 *
 * The names of the PHPCS native token arrays translate one-on-one to the methods in this class:
 * - `PHP_CodeSniffer\Util\Tokens::$emptyTokens` => `PHPCSUtils\BackCompat\BCTokens::emptyTokens()`
 * - `PHP_CodeSniffer\Util\Tokens::$operators`   => `PHPCSUtils\BackCompat\BCTokens::operators()`
 * - ... etc
 *
 * The order of the tokens in the arrays may differ between the PHPCS native token arrays and
 * the token arrays returned by this class.
 *
 * @since 1.0.0
 *
 * @method static array<int|string, int|string> arithmeticTokens()         Tokens that represent arithmetic operators.
 * @method static array<int|string, int|string> booleanOperators()         Tokens that perform boolean operations.
 * @method static array<int|string, int|string> bracketTokens()            Tokens that represent brackets and parenthesis.
 * @method static array<int|string, int|string> castTokens()               Tokens that represent type casting.
 * @method static array<int|string, int|string> commentTokens()            Tokens that are comments.
 * @method static array<int|string, int|string> comparisonTokens()         Tokens that represent comparison operator.
 * @method static array<int|string, int|string> contextSensitiveKeywords() Tokens representing context sensitive keywords
 *                                                                         in PHP.
 * @method static array<int|string, int|string> emptyTokens()              Tokens that don't represent code.
 * @method static array<int|string, int|string> equalityTokens()           Tokens that represent equality comparisons.
 * @method static array<int|string, int|string> heredocTokens()            Tokens that make up a heredoc string.
 * @method static array<int|string, int|string> includeTokens()            Tokens that include files.
 * @method static array<int|string, int|string> magicConstants()           Tokens representing PHP magic constants.
 * @method static array<int|string, int|string> methodPrefixes()           Tokens that can prefix a method name.
 * @method static array<int|string, int|string> ooScopeTokens()            Tokens that open class and object scopes.
 * @method static array<int|string, int|string> operators()                Tokens that perform operations.
 * @method static array<int|string, int|string> phpcsCommentTokens()       Tokens that are comments containing PHPCS
 *                                                                         instructions.
 * @method static array<int|string, int|string> scopeModifiers()           Tokens that represent scope modifiers.
 * @method static array<int|string, int|string> stringTokens()             Tokens that represent strings.
 *                                                                         Note that `T_STRING`s are NOT represented in this
 *                                                                         list as this list is about _text_ strings.
 * @method static array<int|string, int|string> textStringTokens()         Tokens that represent text strings.
 */
final class BCTokens
{

    /**
     * Handle calls to (undeclared) methods for token arrays which haven't received any
     * changes since PHPCS 3.13.5.
     *
     * @since 1.0.0
     *
     * @param string       $name The name of the method which has been called.
     * @param array<mixed> $args Any arguments passed to the method.
     *                           Unused as none of the methods take arguments.
     *
     * @return array<int|string, int|string> Token array
     *
     * @throws \PHPCSUtils\Exceptions\InvalidTokenArray When an invalid token array is requested.
     */
    public static function __callStatic($name, $args)
    {
        if (isset(Tokens::${$name})) {
            return Tokens::${$name};
        }

        // Unknown token array requested.
        throw InvalidTokenArray::create($name);
    }

    /**
     * Tokens that represent assignments.
     *
     * Retrieve the PHPCS assignments tokens array in a cross-version compatible manner.
     *
     * Changelog for the PHPCS native array:
     * - PHPCS 4.0.0: The JS specific `T_ZSR_EQUAL` token is no longer available and has been removed from the array.
     *
     * @see \PHP_CodeSniffer\Util\Tokens::$assignmentTokens Original array.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string> Token array.
     */
    public static function assignmentTokens()
    {
        $tokens = Tokens::$assignmentTokens;

        if (\defined('T_ZSR_EQUAL') && isset($tokens[\T_ZSR_EQUAL])) {
            unset($tokens[\T_ZSR_EQUAL]);
        }

        return $tokens;
    }

    /**
     * Tokens that open code blocks.
     *
     * Retrieve the PHPCS block opener tokens array in a cross-version compatible manner.
     *
     * Changelog for the PHPCS native array:
     * - PHPCS 4.0.0: The JS specific `T_OBJECT` token is no longer available and has been removed from the array.
     *
     * @see \PHP_CodeSniffer\Util\Tokens::$blockOpeners Original array.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string> Token array.
     */
    public static function blockOpeners()
    {
        $tokens = Tokens::$blockOpeners;

        if (\defined('T_OBJECT') && isset($tokens[\T_OBJECT])) {
            unset($tokens[\T_OBJECT]);
        }

        return $tokens;
    }

    /**
     * Tokens that represent the names of called functions.
     *
     * Retrieve the PHPCS function name tokens array in a cross-version compatible manner.
     *
     * Changelog for the PHPCS native array:
     * - Introduced in PHPCS 2.3.3.
     * - PHPCS 4.0.0: `T_NAME_QUALIFIED`, `T_NAME_FULLY_QUALIFIED`, `T_NAME_RELATIVE` and `T_ANON_CLASS` added to the array.
     *
     * @see \PHP_CodeSniffer\Util\Tokens::$functionNameTokens Original array.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string> Token array.
     */
    public static function functionNameTokens()
    {
        $tokens                = Tokens::$functionNameTokens;
        $tokens               += Collections::nameTokens();
        $tokens[\T_ANON_CLASS] = \T_ANON_CLASS;

        return $tokens;
    }

    /**
     * Tokens used for "names", be it namespace, OO, function or constant names.
     *
     * Retrieve the PHPCS name tokens array in a cross-version compatible manner.
     *
     * Changelog for the PHPCS native array:
     * - Introduced in PHPCS 4.0.0.
     *
     * @see \PHP_CodeSniffer\Util\Tokens::NAME_TOKENS Original array.
     *
     * @since 1.1.0
     *
     * @return array<int|string, int|string> Token array.
     */
    public static function nameTokens()
    {
        return Collections::nameTokens();
    }

    /**
     * Token types that open parentheses.
     *
     * Retrieve the PHPCS parenthesis openers tokens array in a cross-version compatible manner.
     *
     * Changelog for the PHPCS native array:
     * - Introduced in PHPCS 0.0.5.
     * - PHPCS 4.0.0: `T_USE` (for closures), `T_ISSET`, `T_UNSET`, `T_EMPTY`, `T_EVAL` and `T_EXIT` added to the array.
     *
     * **Important**: While `T_USE`, `T_ISSET`, `T_UNSET`, `T_EMPTY`, `T_EVAL` and `T_EXIT` will be included
     * in the return value for this method, the associated parentheses will not have the `'parenthesis_owner'` index
     * set until PHPCS 4.0.0.
     * Use the {@see \PHPCSUtils\Utils\Parentheses::getOwner()} or {@see \PHPCSUtils\Utils\Parentheses::hasOwner()} methods
     * if you need to check for whether any of these tokens are a parentheses owner.
     *
     * @see \PHP_CodeSniffer\Util\Tokens::$parenthesisOpeners Original array.
     * @see \PHPCSUtils\Utils\Parentheses                     Class holding utility methods for
     *                                                        working with the `'parenthesis_...'`
     *                                                        index keys in a token array.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string> Token array.
     */
    public static function parenthesisOpeners()
    {
        $tokens           = Tokens::$parenthesisOpeners;
        $tokens[\T_USE]   = \T_USE;
        $tokens[\T_ISSET] = \T_ISSET;
        $tokens[\T_UNSET] = \T_UNSET;
        $tokens[\T_EMPTY] = \T_EMPTY;
        $tokens[\T_EVAL]  = \T_EVAL;
        $tokens[\T_EXIT]  = \T_EXIT;

        return $tokens;
    }

    /**
     * Tokens that are allowed to open scopes.
     *
     * Retrieve the PHPCS scope opener tokens array in a cross-version compatible manner.
     *
     * Changelog for the PHPCS native array:
     * - PHPCS 4.0.0: The JS specific `T_PROPERTY` and `T_OBJECT` tokens are no longer available
     *   and have been removed from the array.
     *
     * @see \PHP_CodeSniffer\Util\Tokens::$scopeOpeners Original array.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string> Token array.
     */
    public static function scopeOpeners()
    {
        $tokens = Tokens::$scopeOpeners;

        if (\defined('T_PROPERTY') && isset($tokens[\T_PROPERTY])) {
            unset($tokens[\T_PROPERTY]);
        }

        if (\defined('T_OBJECT') && isset($tokens[\T_OBJECT])) {
            unset($tokens[\T_OBJECT]);
        }

        return $tokens;
    }
}
