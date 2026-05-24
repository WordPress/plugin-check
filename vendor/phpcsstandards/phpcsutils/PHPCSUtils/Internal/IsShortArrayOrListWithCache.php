<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Internal;

use PHP_CodeSniffer\Files\File;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Internal\IsShortArrayOrList;
use PHPCSUtils\Internal\StableCollections;

/**
 * Determination of short array vs short list vs square brackets.
 *
 * Uses caching of previous results to mitigate performance issues.
 *
 * ---------------------------------------------------------------------------------------------
 * This class is only intended for internal use by PHPCSUtils and is not part of the public API.
 * This also means that it has no promise of backward compatibility.
 *
 * End-users should use the {@see \PHPCSUtils\Utils\Arrays::isShortArray()}
 * or the {@see \PHPCSUtils\Utils\Lists::isShortList()} method instead.
 * ---------------------------------------------------------------------------------------------
 *
 * @internal
 *
 * @since 1.0.0
 */
final class IsShortArrayOrListWithCache
{

    /**
     * Key used for caching the return value of the short array/short list determination.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const CACHE_KEY = __CLASS__;

    /**
     * The PHPCS file in which the current stackPtr was found.
     *
     * @since 1.0.0
     *
     * @var \PHP_CodeSniffer\Files\File
     */
    private $phpcsFile;

    /**
     * The token stack from the current file.
     *
     * @since 1.0.0
     *
     * @var array<int, array<string, mixed>>
     */
    private $tokens;

    /**
     * The current stack pointer.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $stackPtr;

    /**
     * Stack pointer to the open bracket.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $opener;

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
        return (self::getType($phpcsFile, $stackPtr) === IsShortArrayOrList::SHORT_ARRAY);
    }

    /**
     * Determine whether a T_OPEN/CLOSE_SHORT_ARRAY token is a short list construct
     * in contrast to a short array.
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
        return (self::getType($phpcsFile, $stackPtr) === IsShortArrayOrList::SHORT_LIST);
    }

    /**
     * Determine whether a T_OPEN/CLOSE_SHORT_ARRAY token is a short array or short list construct.
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
     * @return string|false The type of construct this bracket was determined to be.
     *                      Either 'short array', 'short list' or 'square brackets'.
     *                      Or FALSE if this was not a bracket token.
     */
    public static function getType(File $phpcsFile, $stackPtr)
    {
        return (new self($phpcsFile, $stackPtr))->process();
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the short array bracket token.
     *
     * @return void
     */
    private function __construct(File $phpcsFile, $stackPtr)
    {
        $this->phpcsFile = $phpcsFile;
        $this->tokens    = $phpcsFile->getTokens();
        $this->stackPtr  = $stackPtr;
    }

    /**
     * Determine whether a T_[OPEN|CLOSE}_[SHORT_ARRAY|SQUARE_BRACKET] token is a short array
     * or short list construct using previously cached results whenever possible.
     *
     * @since 1.0.0
     *
     * @return string|false The type of construct this bracket was determined to be.
     *                      Either 'short array', 'short list' or 'square brackets'.
     *                      Or FALSE is this was not a bracket token.
     */
    private function process()
    {
        if ($this->isValidStackPtr() === false) {
            return false;
        }

        $this->opener = $this->getOpener();

        /*
         * Check the cache in case we've seen this token before.
         */
        $type = $this->getFromCache();
        if ($type !== false) {
            return $type;
        }

        /*
         * If we've not seen the token before, try and solve it and cache the results.
         *
         * Make sure to safeguard against unopened/unclosed square brackets (parse error),
         * which should always be regarded as real square brackets.
         */
        $type = IsShortArrayOrList::SQUARE_BRACKETS;
        if (isset($this->tokens[$this->stackPtr]['bracket_opener'], $this->tokens[$this->stackPtr]['bracket_closer'])) {
            $solver = new IsShortArrayOrList($this->phpcsFile, $this->opener);
            $type   = $solver->solve();
        }

        $this->updateCache($type);

        return $type;
    }

    /**
     * Verify the passed token could potentially be a short array or short list token.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function isValidStackPtr()
    {
        return (isset($this->tokens[$this->stackPtr]) === true
            && isset(StableCollections::$shortArrayListTokensBC[$this->tokens[$this->stackPtr]['code']]) === true);
    }

    /**
     * Get the stack pointer to the short array/list opener.
     *
     * @since 1.0.0
     *
     * @return int
     */
    private function getOpener()
    {
        $opener = $this->stackPtr;
        if (isset($this->tokens[$this->stackPtr]['bracket_opener'])) {
            $opener = $this->tokens[$this->stackPtr]['bracket_opener'];
        }

        return $opener;
    }

    /**
     * Retrieve the bracket "type" of a token from the cache.
     *
     * @since 1.0.0
     *
     * @return string|false The previously determined type (which could be an empty string)
     *                      or FALSE if no cache entry was found for this token.
     */
    private function getFromCache()
    {
        if (Cache::isCached($this->phpcsFile, self::CACHE_KEY, $this->opener) === true) {
            return Cache::get($this->phpcsFile, self::CACHE_KEY, $this->opener);
        }

        return false;
    }

    /**
     * Update the cache with information about a particular bracket token.
     *
     * @since 1.0.0
     *
     * @param string $type The type this bracket has been determined to be.
     *                     Either 'short array', 'short list' or 'square brackets'.
     *
     * @return void
     */
    private function updateCache($type)
    {
        Cache::set($this->phpcsFile, self::CACHE_KEY, $this->opener, $type);
    }
}
