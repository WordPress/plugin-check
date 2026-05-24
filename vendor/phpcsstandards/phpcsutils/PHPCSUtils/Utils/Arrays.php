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
use PHPCSUtils\Exceptions\LogicException;
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\TypeError;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Internal\IsShortArrayOrListWithCache;
use PHPCSUtils\Tokens\Collections;

/**
 * Utility functions for use when examining arrays.
 *
 * @since 1.0.0
 */
final class Arrays
{

    /**
     * The tokens to target to find the double arrow in an array item.
     *
     * @since 1.0.0
     *
     * @var array<int|string, int|string>
     */
    private static $doubleArrowTargets = [
        \T_DOUBLE_ARROW     => \T_DOUBLE_ARROW,

        // Nested arrays.
        \T_ARRAY            => \T_ARRAY,
        \T_OPEN_SHORT_ARRAY => \T_OPEN_SHORT_ARRAY,

        // Inline function, control structures and other things to skip over.
        \T_LIST             => \T_LIST,
        \T_FN               => \T_FN,
        \T_MATCH            => \T_MATCH,
        \T_ATTRIBUTE        => \T_ATTRIBUTE,
    ];

    /**
     * Determine whether a T_OPEN/CLOSE_SHORT_ARRAY token is a short array construct
     * and not a short list.
     *
     * This method also accepts `T_OPEN/CLOSE_SQUARE_BRACKET` tokens to allow it to be
     * PHPCS cross-version compatible as the short array tokenizing has been plagued by
     * a number of bugs over time.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the short array bracket token.
     *
     * @return bool `TRUE` if the token passed is the open/close bracket of a short array.
     *              `FALSE` if the token is a short list bracket, a plain square bracket
     *              or not one of the accepted tokens.
     */
    public static function isShortArray(File $phpcsFile, $stackPtr)
    {
        return IsShortArrayOrListWithCache::isShortArray($phpcsFile, $stackPtr);
    }

    /**
     * Find the array opener and closer based on a T_ARRAY or T_OPEN_SHORT_ARRAY token.
     *
     * This method also accepts `T_OPEN_SQUARE_BRACKET` tokens to allow it to be
     * PHPCS cross-version compatible as the short array tokenizing has been plagued by
     * a number of bugs over time, which affects the short array determination.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the `T_ARRAY` or `T_OPEN_SHORT_ARRAY`
     *                                                  token in the stack.
     * @param true|null                   $isShortArray Short-circuit the short array check for `T_OPEN_SHORT_ARRAY`
     *                                                  tokens if it isn't necessary.
     *                                                  Efficiency tweak for when this has already been established,
     *                                                  i.e. when encountering a nested array while walking the
     *                                                  tokens in an array.
     *                                                  Use with care.
     *
     * @return array<string, int>|false An array with the token pointers; or `FALSE` if this is not a
     *                                  (short) array token or if the opener/closer could not be determined.
     *                                  The format of the array return value is:
     *                                  ```php
     *                                  array(
     *                                    'opener' => integer, // Stack pointer to the array open bracket.
     *                                    'closer' => integer, // Stack pointer to the array close bracket.
     *                                  )
     *                                  ```
     */
    public static function getOpenClose(File $phpcsFile, $stackPtr, $isShortArray = null)
    {
        $tokens = $phpcsFile->getTokens();

        // Is this one of the tokens this function handles ?
        if (isset($tokens[$stackPtr]) === false
            || isset(Collections::arrayOpenTokensBC()[$tokens[$stackPtr]['code']]) === false
        ) {
            return false;
        }

        switch ($tokens[$stackPtr]['code']) {
            case \T_ARRAY:
                if (isset($tokens[$stackPtr]['parenthesis_opener'])) {
                    $opener = $tokens[$stackPtr]['parenthesis_opener'];

                    if (isset($tokens[$opener]['parenthesis_closer'])) {
                        $closer = $tokens[$opener]['parenthesis_closer'];
                    }
                }
                break;

            case \T_OPEN_SHORT_ARRAY:
            case \T_OPEN_SQUARE_BRACKET:
                if ($isShortArray === true || self::isShortArray($phpcsFile, $stackPtr) === true) {
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
     * Get the stack pointer position of the double arrow within an array item.
     *
     * Expects to be passed the array item start and end tokens as retrieved via
     * {@see \PHPCSUtils\Utils\PassedParameters::getParameters()}.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being examined.
     * @param int                         $start     Stack pointer to the start of the array item.
     * @param int                         $end       Stack pointer to the last token in the array item.
     *
     * @return int|false Stack pointer to the double arrow if this array item has a key; or `FALSE` otherwise.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $start or $end parameters are not integers.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the tokens passed do not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\LogicException      If $end pointer is before the $start pointer.
     */
    public static function getDoubleArrowPtr(File $phpcsFile, $start, $end)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($start) === false) {
            throw TypeError::create(2, '$start', 'integer', $start);
        }

        if (\is_int($end) === false) {
            throw TypeError::create(3, '$end', 'integer', $end);
        }

        if (isset($tokens[$start]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$start', $start);
        }

        if (isset($tokens[$end]) === false) {
            throw OutOfBoundsStackPtr::create(3, '$end', $end);
        }

        if ($start > $end) {
            throw LogicException::create(
                \sprintf('The $start token must be before the $end token. Received: $start %d, $end %d', $start, $end)
            );
        }

        $cacheId = "$start-$end";
        if (Cache::isCached($phpcsFile, __METHOD__, $cacheId) === true) {
            return Cache::get($phpcsFile, __METHOD__, $cacheId);
        }

        $targets  = self::$doubleArrowTargets;
        $targets += Collections::closedScopes();

        $doubleArrow = ($start - 1);
        $returnValue = false;
        ++$end;
        do {
            $doubleArrow = $phpcsFile->findNext(
                $targets,
                ($doubleArrow + 1),
                $end
            );

            if ($doubleArrow === false) {
                break;
            }

            if ($tokens[$doubleArrow]['code'] === \T_DOUBLE_ARROW) {
                $returnValue = $doubleArrow;
                break;
            }

            // Skip over closed scopes which may contain foreach structures or generators.
            if ((isset(Collections::closedScopes()[$tokens[$doubleArrow]['code']]) === true
                || $tokens[$doubleArrow]['code'] === \T_FN
                || $tokens[$doubleArrow]['code'] === \T_MATCH)
                && isset($tokens[$doubleArrow]['scope_closer']) === true
            ) {
                $doubleArrow = $tokens[$doubleArrow]['scope_closer'];
                continue;
            }

            // Skip over attributes which may contain arrays as a passed parameters.
            if ($tokens[$doubleArrow]['code'] === \T_ATTRIBUTE
                && isset($tokens[$doubleArrow]['attribute_closer'])
            ) {
                $doubleArrow = $tokens[$doubleArrow]['attribute_closer'];
                continue;
            }

            // Skip over potentially keyed long lists.
            if ($tokens[$doubleArrow]['code'] === \T_LIST
                && isset($tokens[$doubleArrow]['parenthesis_closer'])
            ) {
                $doubleArrow = $tokens[$doubleArrow]['parenthesis_closer'];
                continue;
            }

            // Start of nested long/short array.
            break;
        } while ($doubleArrow < $end);

        Cache::set($phpcsFile, __METHOD__, $cacheId, $returnValue);
        return $returnValue;
    }
}
