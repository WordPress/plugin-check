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

/**
 * Results cache.
 *
 * Allows to cache the return value of utility functions which do a lot of token walking.
 * Those type of utilities can significantly slow down file scanning, especially
 * with large files and when multiple sniffs use the same utility function.
 *
 * Caching the results can significantly speed things up, though it can also eat memory,
 * so use with care.
 *
 * Typical usage:
 * ```php
 * function doSomething()
 * {
 *    if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
 *        return Cache::get($phpcsFile, __METHOD__, $stackPtr);
 *    }
 *
 *    // Do something.
 *
 *    Cache::set($phpcsFile, __METHOD__, $stackPtr, $returnValue);
 *    return $returnValue;
 * }
 * ```
 *
 * ---------------------------------------------------------------------------------------------
 * This class is only intended for internal use by PHPCSUtils and is not part of the public API.
 * This also means that it has no promise of backward compatibility.
 * ---------------------------------------------------------------------------------------------
 *
 * @internal
 *
 * @since 1.0.0
 */
final class Cache
{

    /**
     * Whether caching is enabled or not.
     *
     * Note: this switch is ONLY intended for use within test suites and should never
     * be touched in any other circumstances!
     *
     * Don't forget to always turn the cache back on in a `tear_down()` method!
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public static $enabled = true;

    /**
     * Results cache.
     *
     * @since 1.0.0
     *
     * @var array<int, array<string, array<string, array<string|int, mixed>>>>
     *            Format: $cache[$loop][$fileName][$key][$id] = mixed $value;
     */
    private static $cache = [];

    /**
     * Check whether a result has been cached for a certain utility function.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param string                      $key       The key to identify a particular set of results.
     *                                               It is recommended to pass __METHOD__ to this parameter.
     * @param int|string                  $id        Unique identifier for these results.
     *                                               Generally speaking this will be the $stackPtr passed
     *                                               to the utility function, but it can also something else,
     *                                               like a serialization of args passed to a function or an
     *                                               md5 hash of an input.
     *
     * @return bool
     */
    public static function isCached(File $phpcsFile, $key, $id)
    {
        if (self::$enabled === false) {
            return false;
        }

        $fileName = $phpcsFile->getFilename();
        $loop     = $phpcsFile->fixer->enabled === true ? $phpcsFile->fixer->loops : 0;

        return isset(self::$cache[$loop][$fileName][$key])
            && \array_key_exists($id, self::$cache[$loop][$fileName][$key]);
    }

    /**
     * Retrieve a previously cached result for a certain utility function.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param string                      $key       The key to identify a particular set of results.
     *                                               It is recommended to pass __METHOD__ to this parameter.
     * @param int|string                  $id        Unique identifier for these results.
     *                                               Generally speaking this will be the $stackPtr passed
     *                                               to the utility function, but it can also something else,
     *                                               like a serialization of args passed to a function or an
     *                                               md5 hash of an input.
     *
     * @return mixed
     */
    public static function get(File $phpcsFile, $key, $id)
    {
        if (self::$enabled === false) {
            return null;
        }

        $fileName = $phpcsFile->getFilename();
        $loop     = $phpcsFile->fixer->enabled === true ? $phpcsFile->fixer->loops : 0;

        if (isset(self::$cache[$loop][$fileName][$key])
            && \array_key_exists($id, self::$cache[$loop][$fileName][$key])
        ) {
            return self::$cache[$loop][$fileName][$key][$id];
        }

        return null;
    }

    /**
     * Retrieve all previously cached results for a certain utility function and a certain file.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param string                      $key       The key to identify a particular set of results.
     *                                               It is recommended to pass __METHOD__ to this parameter.
     *
     * @return array<string|int, mixed>
     */
    public static function getForFile(File $phpcsFile, $key)
    {
        if (self::$enabled === false) {
            return [];
        }

        $fileName = $phpcsFile->getFilename();
        $loop     = $phpcsFile->fixer->enabled === true ? $phpcsFile->fixer->loops : 0;

        if (isset(self::$cache[$loop][$fileName])
            && \array_key_exists($key, self::$cache[$loop][$fileName])
        ) {
            return self::$cache[$loop][$fileName][$key];
        }

        return [];
    }

    /**
     * Cache the result for a certain utility function.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param string                      $key       The key to identify a particular set of results.
     *                                               It is recommended to pass __METHOD__ to this parameter.
     * @param int|string                  $id        Unique identifier for these results.
     *                                               Generally speaking this will be the $stackPtr passed
     *                                               to the utility function, but it can also something else,
     *                                               like a serialization of args passed to a function or an
     *                                               md5 hash of an input.
     * @param mixed                       $value     An arbitrary value to write to the cache.
     *
     * @return mixed
     */
    public static function set(File $phpcsFile, $key, $id, $value)
    {
        if (self::$enabled === false) {
            return;
        }

        $fileName = $phpcsFile->getFilename();
        $loop     = $phpcsFile->fixer->enabled === true ? $phpcsFile->fixer->loops : 0;

        /*
         * If this is a phpcbf run and we've reached the next loop, clear the cache
         * of all previous loops to free up memory.
         */
        if (isset(self::$cache[$loop]) === false
            && empty(self::$cache) === false
        ) {
            self::clear();
        }

        self::$cache[$loop][$fileName][$key][$id] = $value;
    }

    /**
     * Clear the cache.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function clear()
    {
        self::$cache = [];
    }
}
