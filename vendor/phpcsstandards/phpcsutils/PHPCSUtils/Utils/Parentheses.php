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

/**
 * Utility functions for use when examining parenthesis tokens and arbitrary tokens wrapped
 * in parentheses.
 *
 * In contrast to PHPCS natively (< 4.0), `isset()`, `unset()`, `empty()`, `exit()`, `die()`, `eval()`
 * and closure `use()` will be considered parentheses owners by the functions in this class.
 *
 * @since 1.0.0
 */
final class Parentheses
{

    /**
     * Extra tokens which should be considered parentheses owners.
     *
     * - `T_ISSET`, `T_UNSET`, `T_EMPTY`, `T_EXIT` and `T_EVAL` are not PHPCS native parentheses
     *    owners until PHPCS 4.0.0, but are considered such for the purposes of this class.
     *    Also see {@link https://github.com/squizlabs/PHP_CodeSniffer/issues/3118 PHPCS#3118}.
     * - `T_USE` when used for a closure use statement also became a parentheses owner in PHPCS 4.0.0.
     *
     * @since 1.0.0
     *
     * @var array<int|string, int|string>
     */
    private static $extraParenthesesOwners = [
        \T_ISSET => \T_ISSET,
        \T_UNSET => \T_UNSET,
        \T_EMPTY => \T_EMPTY,
        \T_EXIT  => \T_EXIT,
        \T_EVAL  => \T_EVAL,
        \T_USE   => \T_USE,
    ];

    /**
     * Get the stack pointer to the parentheses owner of an open/close parenthesis.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The position of `T_OPEN/CLOSE_PARENTHESIS` token.
     *
     * @return int|false Integer stack pointer to the parentheses owner; or `FALSE` if the
     *                   parenthesis does not have a (direct) owner or if the token passed
     *                   was not a parenthesis.
     */
    public static function getOwner(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['parenthesis_owner'])) {
            return $tokens[$stackPtr]['parenthesis_owner'];
        }

        /*
         * As the 'parenthesis_owner' index is only set on parentheses, we didn't need to do any
         * input validation before, but now we do.
         */
        if (isset($tokens[$stackPtr]) === false
            || ($tokens[$stackPtr]['code'] !== \T_OPEN_PARENTHESIS
            && $tokens[$stackPtr]['code'] !== \T_CLOSE_PARENTHESIS)
        ) {
            return false;
        }

        if ($tokens[$stackPtr]['code'] === \T_CLOSE_PARENTHESIS) {
            $stackPtr = $tokens[$stackPtr]['parenthesis_opener'];
        }

        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($prevNonEmpty !== false
            && isset(self::$extraParenthesesOwners[$tokens[$prevNonEmpty]['code']]) === true
        ) {
            return $prevNonEmpty;
        }

        return false;
    }

    /**
     * Check whether the parenthesis owner of an open/close parenthesis is within a limited
     * set of valid owners.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position of `T_OPEN/CLOSE_PARENTHESIS` token.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return bool `TRUE` if the owner is within the list of `$validOwners`; `FALSE` if not and
     *              if the parenthesis does not have a (direct) owner.
     */
    public static function isOwnerIn(File $phpcsFile, $stackPtr, $validOwners)
    {
        $owner = self::getOwner($phpcsFile, $stackPtr);
        if ($owner === false) {
            return false;
        }

        $tokens      = $phpcsFile->getTokens();
        $validOwners = (array) $validOwners;

        return \in_array($tokens[$owner]['code'], $validOwners, true);
    }

    /**
     * Check whether the passed token is nested within parentheses owned by one of the valid owners.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position of the token we are checking.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return bool
     */
    public static function hasOwner(File $phpcsFile, $stackPtr, $validOwners)
    {
        return (self::nestedParensWalker($phpcsFile, $stackPtr, $validOwners) !== false);
    }

    /**
     * Retrieve the stack pointer to the parentheses opener of the first (outer) set of parentheses
     * an arbitrary token is wrapped in.
     *
     * If the optional `$validOwners` parameter is passed, the stack pointer to the opener to
     * the first set of parentheses, which has an owner which is in the list of valid owners,
     * will be returned. This may be a nested set of parentheses.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position of the token we are checking.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return int|false Integer stack pointer to the parentheses opener; or `FALSE` if the token
     *                   does not have parentheses owned by any of the valid owners or if
     *                   the token is not nested in parentheses at all.
     */
    public static function getFirstOpener(File $phpcsFile, $stackPtr, $validOwners = [])
    {
        return self::nestedParensWalker($phpcsFile, $stackPtr, $validOwners, false);
    }

    /**
     * Retrieve the stack pointer to the parentheses closer of the first (outer) set of parentheses
     * an arbitrary token is wrapped in.
     *
     * If the optional `$validOwners` parameter is passed, the stack pointer to the closer to
     * the first set of parentheses, which has an owner which is in the list of valid owners,
     * will be returned. This may be a nested set of parentheses.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position of the token we are checking.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return int|false Integer stack pointer to the parentheses closer; or `FALSE` if the token
     *                   does not have parentheses owned by any of the valid owners or if
     *                   the token is not nested in parentheses at all.
     */
    public static function getFirstCloser(File $phpcsFile, $stackPtr, $validOwners = [])
    {
        $opener = self::getFirstOpener($phpcsFile, $stackPtr, $validOwners);
        $tokens = $phpcsFile->getTokens();
        if ($opener !== false && isset($tokens[$opener]['parenthesis_closer']) === true) {
            return $tokens[$opener]['parenthesis_closer'];
        }

        return false;
    }

    /**
     * Retrieve the stack pointer to the parentheses owner of the first (outer) set of parentheses
     * an arbitrary token is wrapped in.
     *
     * If the optional `$validOwners` parameter is passed, the stack pointer to the owner of
     * the first set of parentheses, which has an owner which is in the list of valid owners,
     * will be returned. This may be a nested set of parentheses.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position of the token we are checking.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return int|false Integer stack pointer to the parentheses owner; or `FALSE` if the token
     *                   does not have parentheses owned by any of the valid owners or if
     *                   the token is not nested in parentheses at all.
     */
    public static function getFirstOwner(File $phpcsFile, $stackPtr, $validOwners = [])
    {
        $opener = self::getFirstOpener($phpcsFile, $stackPtr, $validOwners);
        if ($opener !== false) {
            return self::getOwner($phpcsFile, $opener);
        }

        return false;
    }

    /**
     * Retrieve the stack pointer to the parentheses opener of the last (inner) set of parentheses
     * an arbitrary token is wrapped in.
     *
     * If the optional `$validOwners` parameter is passed, the stack pointer to the opener to
     * the last set of parentheses, which has an owner which is in the list of valid owners,
     * will be returned. This may be a set of parentheses higher up.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position of the token we are checking.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return int|false Integer stack pointer to the parentheses opener; or `FALSE` if the token
     *                   does not have parentheses owned by any of the valid owners or if
     *                   the token is not nested in parentheses at all.
     */
    public static function getLastOpener(File $phpcsFile, $stackPtr, $validOwners = [])
    {
        return self::nestedParensWalker($phpcsFile, $stackPtr, $validOwners, true);
    }

    /**
     * Retrieve the stack pointer to the parentheses closer of the last (inner) set of parentheses
     * an arbitrary token is wrapped in.
     *
     * If the optional `$validOwners` parameter is passed, the stack pointer to the closer to
     * the last set of parentheses, which has an owner which is in the list of valid owners,
     * will be returned. This may be a set of parentheses higher up.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position of the token we are checking.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return int|false Integer stack pointer to the parentheses closer; or `FALSE` if the token
     *                   does not have parentheses owned by any of the valid owners or if
     *                   the token is not nested in parentheses at all.
     */
    public static function getLastCloser(File $phpcsFile, $stackPtr, $validOwners = [])
    {
        $opener = self::getLastOpener($phpcsFile, $stackPtr, $validOwners);
        $tokens = $phpcsFile->getTokens();
        if ($opener !== false && isset($tokens[$opener]['parenthesis_closer']) === true) {
            return $tokens[$opener]['parenthesis_closer'];
        }

        return false;
    }

    /**
     * Retrieve the stack pointer to the parentheses owner of the last (inner) set of parentheses
     * an arbitrary token is wrapped in.
     *
     * If the optional `$validOwners` parameter is passed, the stack pointer to the owner of
     * the last set of parentheses, which has an owner which is in the list of valid owners,
     * will be returned. This may be a set of parentheses higher up.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position of the token we are checking.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return int|false Integer stack pointer to the parentheses owner; or `FALSE` if the token
     *                   does not have parentheses owned by any of the valid owners or if
     *                   the token is not nested in parentheses at all.
     */
    public static function getLastOwner(File $phpcsFile, $stackPtr, $validOwners = [])
    {
        $opener = self::getLastOpener($phpcsFile, $stackPtr, $validOwners);
        if ($opener !== false) {
            return self::getOwner($phpcsFile, $opener);
        }

        return false;
    }

    /**
     * Check whether the owner of the outermost wrapping set of parentheses of an arbitrary token
     * is within a limited set of acceptable token types.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position in the stack of the
     *                                                  token to verify.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return int|false Integer stack pointer to the valid parentheses owner; or `FALSE` if
     *                   the token was not wrapped in parentheses or if the outermost set
     *                   of parentheses in which the token is wrapped does not have an owner
     *                   within the set of owners considered valid.
     */
    public static function firstOwnerIn(File $phpcsFile, $stackPtr, $validOwners)
    {
        $opener = self::getFirstOpener($phpcsFile, $stackPtr);

        if ($opener !== false && self::isOwnerIn($phpcsFile, $opener, $validOwners) === true) {
            return self::getOwner($phpcsFile, $opener);
        }

        return false;
    }

    /**
     * Check whether the owner of the innermost wrapping set of parentheses of an arbitrary token
     * is within a limited set of acceptable token types.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position in the stack of the
     *                                                  token to verify.
     * @param int|string|array<int|string> $validOwners Array of token constants for the owners
     *                                                  which should be considered valid.
     *
     * @return int|false Integer stack pointer to the valid parentheses owner; or `FALSE` if
     *                   the token was not wrapped in parentheses or if the innermost set
     *                   of parentheses in which the token is wrapped does not have an owner
     *                   within the set of owners considered valid.
     */
    public static function lastOwnerIn(File $phpcsFile, $stackPtr, $validOwners)
    {
        $opener = self::getLastOpener($phpcsFile, $stackPtr);

        if ($opener !== false && self::isOwnerIn($phpcsFile, $opener, $validOwners) === true) {
            return self::getOwner($phpcsFile, $opener);
        }

        return false;
    }

    /**
     * Helper method. Retrieve the position of a parentheses opener for an arbitrary passed token.
     *
     * If no `$validOwners` are specified, the opener to the first set of parentheses surrounding
     * the token - or if `$reverse = true`, the last set of parentheses - will be returned.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile   The file where this token was found.
     * @param int                          $stackPtr    The position of the token we are checking.
     * @param int|string|array<int|string> $validOwners Optional. Array of token constants for the owners
     *                                                  which should be considered valid.
     * @param bool                         $reverse     Optional. Whether to search for the first/outermost
     *                                                  (`false`) or the last/innermost (`true`) set of
     *                                                  parentheses with the specified owner(s).
     *
     * @return int|false Integer stack pointer to the parentheses opener; or `FALSE` if the token
     *                   does not have parentheses owned by any of the valid owners or if
     *                   the token is not nested in parentheses at all.
     */
    private static function nestedParensWalker(File $phpcsFile, $stackPtr, $validOwners = [], $reverse = false)
    {
        $tokens = $phpcsFile->getTokens();

        // Check for the existence of the token.
        if (isset($tokens[$stackPtr]) === false) {
            return false;
        }

        // Make sure the token is nested in parenthesis.
        if (empty($tokens[$stackPtr]['nested_parenthesis']) === true) {
            return false;
        }

        $validOwners = (array) $validOwners;
        $parentheses = $tokens[$stackPtr]['nested_parenthesis'];

        if (empty($validOwners) === true) {
            // No owners specified, just return the first/last parentheses opener.
            if ($reverse === true) {
                \end($parentheses);
            } else {
                \reset($parentheses);
            }

            return \key($parentheses);
        }

        if ($reverse === true) {
            $parentheses = \array_reverse($parentheses, true);
        }

        foreach ($parentheses as $opener => $closer) {
            if (self::isOwnerIn($phpcsFile, $opener, $validOwners) === true) {
                // We found a token with a valid owner.
                return $opener;
            }
        }

        return false;
    }
}
