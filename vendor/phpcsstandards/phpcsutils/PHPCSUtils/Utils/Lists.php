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
use PHPCSUtils\Exceptions\UnexpectedTokenType;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Internal\IsShortArrayOrListWithCache;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\GetTokensAsString;

/**
 * Utility functions to retrieve information when working with lists.
 *
 * @since 1.0.0
 */
final class Lists
{

    /**
     * Default values for individual list items.
     *
     * Used by the `getAssignments()` method.
     *
     * @since 1.0.0
     *
     * @var array<string, mixed>
     */
    private static $listItemDefaults = [
        'raw'                  => '',
        'assignment'           => '',
        'is_empty'             => false,
        'is_nested_list'       => false,
        'variable'             => false,
        'assignment_token'     => false,
        'assignment_end_token' => false,
        'assign_by_reference'  => false,
        'reference_token'      => false,
    ];

    /**
     * Determine whether a T_OPEN/CLOSE_SHORT_ARRAY token is a short list() construct.
     *
     * This method also accepts `T_OPEN/CLOSE_SQUARE_BRACKET` tokens to allow it to be
     * PHPCS cross-version compatible as the short array tokenizing has been plagued by
     * a number of bugs over time, which affects the short list determination.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the short array bracket token.
     *
     * @return bool `TRUE` if the token passed is the open/close bracket of a short list.
     *              `FALSE` if the token is a short array bracket or plain square bracket
     *              or not one of the accepted tokens.
     */
    public static function isShortList(File $phpcsFile, $stackPtr)
    {
        return IsShortArrayOrListWithCache::isShortList($phpcsFile, $stackPtr);
    }

    /**
     * Find the list opener and closer based on a T_LIST or T_OPEN_SHORT_ARRAY token.
     *
     * This method also accepts `T_OPEN_SQUARE_BRACKET` tokens to allow it to be
     * PHPCS cross-version compatible as the short array tokenizing has been plagued by
     * a number of bugs over time, which affects the short list determination.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile   The file being scanned.
     * @param int                         $stackPtr    The position of the T_LIST or T_OPEN_SHORT_ARRAY
     *                                                 token in the stack.
     * @param true|null                   $isShortList Short-circuit the short list check for T_OPEN_SHORT_ARRAY
     *                                                 tokens if it isn't necessary.
     *                                                 Efficiency tweak for when this has already been established,
     *                                                 i.e. when encountering a nested list while walking the
     *                                                 tokens in a list.
     *                                                 Use with care.
     *
     * @return array<string, int>|false An array with the token pointers; or `FALSE` if this is not a (short) list
     *                                  token or if the opener/closer could not be determined.
     *                                 The format of the array return value is:
     *                                 ```php
     *                                 array(
     *                                   'opener' => integer, // Stack pointer to the list open bracket.
     *                                   'closer' => integer, // Stack pointer to the list close bracket.
     *                                 )
     *                                 ```
     */
    public static function getOpenClose(File $phpcsFile, $stackPtr, $isShortList = null)
    {
        $tokens = $phpcsFile->getTokens();

        // Is this one of the tokens this function handles ?
        if (isset($tokens[$stackPtr]) === false
            || isset(Collections::listOpenTokensBC()[$tokens[$stackPtr]['code']]) === false
        ) {
            return false;
        }

        switch ($tokens[ $stackPtr ]['code']) {
            case \T_LIST:
                if (isset($tokens[$stackPtr]['parenthesis_opener'])) {
                    $opener = $tokens[$stackPtr]['parenthesis_opener'];

                    if (isset($tokens[$opener]['parenthesis_closer'])) {
                        $closer = $tokens[$opener]['parenthesis_closer'];
                    }
                }
                break;

            case \T_OPEN_SHORT_ARRAY:
            case \T_OPEN_SQUARE_BRACKET:
                if ($isShortList === true || self::isShortList($phpcsFile, $stackPtr) === true) {
                    $opener = $stackPtr;
                    $closer = $tokens[$stackPtr]['bracket_closer'];
                }
                break;
        }

        if (isset($opener, $closer)) {
            return [
                'opener' => $opener,
                'closer' => $closer,
            ];
        }

        return false;
    }

    /**
     * Retrieves information on the assignments made in the specified (long/short) list.
     *
     * This method also accepts `T_OPEN_SQUARE_BRACKET` tokens to allow it to be
     * PHPCS cross-version compatible as the short array tokenizing has been plagued by
     * a number of bugs over time, which affects the short list determination.
     *
     * The returned array will contain the following basic information for each assignment:
     *
     * ```php
     * 0 => array(
     *   'raw'                  => string,       // The full content of the variable definition,
     *                                           // including whitespace and comments.
     *                                           // This may be an empty string when a list
     *                                           // item is being skipped.
     *   'assignment'           => string,       // The content of the assignment part,
     *                                           // cleaned of comments.
     *                                           // This may be an empty string for an empty
     *                                           // list item; it could also be a nested list
     *                                           // represented as a string.
     *   'is_empty'             => bool,         // Whether this is an empty list item, i.e.
     *                                           // the second item in `list($a, , $b)`.
     *   'is_nested_list'       => bool,         // Whether this is a nested list.
     *   'variable'             => string|false, // The base variable being assigned to; or
     *                                           // FALSE in case of a nested list or
     *                                           // a variable variable.
     *                                           // I.e. `$a` in `list($a['key'])`.
     *   'assignment_token'     => int|false,    // The start pointer for the assignment.
     *                                           // For a nested list, this will be the pointer
     *                                           // to the `list` keyword or the open square
     *                                           // bracket in case of a short list.
     *   'assignment_end_token' => int|false,    // The end pointer for the assignment.
     *   'assign_by_reference'  => bool,         // Is the variable assigned by reference?
     *   'reference_token'      => int|false,    // The stack pointer to the reference operator;
     *                                           // or FALSE when not a reference assignment.
     * )
     * ```
     *
     * Assignments with keys will have the following additional array indexes set:
     * ```php
     *   'key'                 => string, // The content of the key, cleaned of comments.
     *   'key_token'           => int,    // The stack pointer to the start of the key.
     *   'key_end_token'       => int,    // The stack pointer to the end of the key.
     *   'double_arrow_token'  => int,    // The stack pointer to the double arrow.
     * ```
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the function token
     *                                               to acquire the parameters for.
     *
     * @return array<int, array<string, mixed>>
     *               An array with information on each assignment made, including skipped assignments (empty),
     *               or an empty array if no assignments are made at all (fatal error in PHP >= 7.0).
     *
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a T_LIST, T_OPEN_SHORT_ARRAY or
     *                                                    T_OPEN_SQUARE_BRACKET token.
     */
    public static function getAssignments(File $phpcsFile, $stackPtr)
    {
        $openClose = self::getOpenClose($phpcsFile, $stackPtr);
        if ($openClose === false) {
            // The `getOpenClose()` method does the $stackPtr validation.
            $received = $stackPtr;
            $tokens   = $phpcsFile->getTokens();
            if (\is_int($stackPtr) && isset($tokens[$stackPtr])) {
                $received = $tokens[$stackPtr]['type'];
            } elseif (\is_int($stackPtr) === false) {
                $received = \gettype($stackPtr);
            }
            throw UnexpectedTokenType::create(2, '$stackPtr', 'long/short list', $received);
        }

        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        $opener = $openClose['opener'];
        $closer = $openClose['closer'];

        $tokens = $phpcsFile->getTokens();

        $vars         = [];
        $start        = null;
        $lastNonEmpty = null;
        $reference    = null;
        $list         = null;
        $lastComma    = $opener;
        $keys         = [];

        for ($i = ($opener + 1); $i <= $closer; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']])) {
                continue;
            }

            switch ($tokens[$i]['code']) {
                case \T_DOUBLE_ARROW:
                    $keys['key']                = GetTokensAsString::compact($phpcsFile, $start, $lastNonEmpty, true);
                    $keys['key_token']          = $start;
                    $keys['key_end_token']      = $lastNonEmpty;
                    $keys['double_arrow_token'] = $i;

                    // Partial reset.
                    $start        = null;
                    $lastNonEmpty = null;
                    $list         = null; // Prevent confusion when short array was used as the key.
                    $reference    = null;
                    break;

                case \T_COMMA:
                case $tokens[$closer]['code']:
                    // Check if this is the end of the list or only a token with the same type as the list closer.
                    if ($tokens[$i]['code'] === $tokens[$closer]['code']) {
                        if ($i !== $closer) {
                            /*
                             * Shouldn't be possible anymore now nested brackets are being skipped over,
                             * but keep it just in case.
                             */
                            // @codeCoverageIgnoreStart
                            $lastNonEmpty = $i;
                            break;
                            // @codeCoverageIgnoreEnd
                        } elseif ($start === null && $lastComma === $opener) {
                            // This is an empty list.
                            break 2;
                        }
                    }

                    // Ok, so this is actually the end of the list item.
                    $current        = self::$listItemDefaults;
                    $current['raw'] = \trim(GetTokensAsString::normal($phpcsFile, ($lastComma + 1), ($i - 1)));

                    if ($start === null) {
                        $current['is_empty'] = true;
                    } else {
                        $current['assignment']     = \trim(
                            GetTokensAsString::compact($phpcsFile, $start, $lastNonEmpty, true)
                        );
                        $current['is_nested_list'] = isset($list);

                        $current['variable'] = false;
                        if (isset($list) === false && $tokens[$start]['code'] === \T_VARIABLE) {
                            $current['variable'] = $tokens[$start]['content'];
                        }
                        $current['assignment_token']     = $start;
                        $current['assignment_end_token'] = $lastNonEmpty;

                        if (isset($reference)) {
                            $current['assign_by_reference'] = true;
                            $current['reference_token']     = $reference;
                        }
                    }

                    if (empty($keys) === false) {
                        $current += $keys;
                    }

                    $vars[] = $current;

                    // Reset.
                    $start        = null;
                    $lastNonEmpty = null;
                    $reference    = null;
                    $list         = null;
                    $lastComma    = $i;
                    $keys         = [];

                    break;

                case \T_LIST:
                case \T_OPEN_SHORT_ARRAY:
                    if ($start === null) {
                        $start = $i;
                    }

                    /*
                     * As the top level list has an open/close, we know we don't have a parse error and
                     * any nested (short) arrays/lists will be tokenized correctly, so no need for extra checks here.
                     */
                    $nestedOpenClose = self::getOpenClose($phpcsFile, $i, true);
                    $list            = $i;
                    $i               = $nestedOpenClose['closer'];

                    $lastNonEmpty = $i;
                    break;

                case \T_BITWISE_AND:
                    $reference    = $i;
                    $lastNonEmpty = $i;
                    break;

                default:
                    if ($start === null) {
                        $start = $i;
                    }

                    // Skip over everything within all types of brackets which may be used in keys.
                    if (isset($tokens[$i]['bracket_opener'], $tokens[$i]['bracket_closer'])
                        && $i === $tokens[$i]['bracket_opener']
                    ) {
                        $i = $tokens[$i]['bracket_closer'];
                    } elseif ($tokens[$i]['code'] === \T_OPEN_PARENTHESIS
                        && isset($tokens[$i]['parenthesis_closer'])
                    ) {
                        $i = $tokens[$i]['parenthesis_closer'];
                    } elseif (isset($tokens[$i]['scope_condition'], $tokens[$i]['scope_closer'])
                        && $tokens[$i]['scope_condition'] === $i
                    ) {
                        $i = $tokens[$i]['scope_closer'];
                    } elseif ($tokens[$i]['code'] === \T_ATTRIBUTE
                        && isset($tokens[$i]['attribute_closer'])
                    ) {
                        $i = $tokens[$i]['attribute_closer'];
                    }

                    $lastNonEmpty = $i;
                    break;
            }
        }

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $vars);
        return $vars;
    }
}
