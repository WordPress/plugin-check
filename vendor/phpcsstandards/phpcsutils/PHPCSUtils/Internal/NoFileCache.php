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

/**
 * Cache for function results which are not directly related to a $phpcsFile.
 *
 * Allows to cache the return value of utility functions which don't use PHPCS File objects.
 *
 * Caching the results can significantly speed things up, though it can also eat memory,
 * so use with care.
 *
 * Typical usage:
 * ```php
 * function doSomething()
 * {
 *    if (NoFileCache::isCached(__METHOD__, $paramHash) === true) {
 *        return NoFileCache::get(__METHOD__, $paramHash);
 *    }
 *
 *    // Do something.
 *
 *    NoFileCache::set(__METHOD__, $paramHash, $returnValue);
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
final class NoFileCache
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
     * @var array<string, array<int|string, mixed>> Format: $cache[$key][$id] = mixed $value;
     */
    private static $cache = [];

    /**
     * Check whether a result has been cached for a certain utility function.
     *
     * @since 1.0.0
     *
     * @param string     $key The key to identify a particular set of results.
     *                        It is recommended to pass `__METHOD__` to this parameter.
     * @param int|string $id  Unique identifier for these results.
     *                        It is recommended for this to be a serialization of arguments passed
     *                        to the function or an md5 hash of an input.
     *
     * @return bool
     */
    public static function isCached($key, $id)
    {
        return self::$enabled === true && isset(self::$cache[$key]) && \array_key_exists($id, self::$cache[$key]);
    }

    /**
     * Retrieve a previously cached result for a certain utility function.
     *
     * @since 1.0.0
     *
     * @param string     $key The key to identify a particular set of results.
     *                        It is recommended to pass `__METHOD__` to this parameter.
     * @param int|string $id  Unique identifier for these results.
     *                        It is recommended for this to be a serialization of arguments passed
     *                        to the function or an md5 hash of an input.
     *
     * @return mixed
     */
    public static function get($key, $id)
    {
        if (self::$enabled === true && isset(self::$cache[$key]) && \array_key_exists($id, self::$cache[$key])) {
            return self::$cache[$key][$id];
        }

        return null;
    }

    /**
     * Retrieve all previously cached results for a certain utility function.
     *
     * @since 1.0.0
     *
     * @param string $key The key to identify a particular set of results.
     *                    It is recommended to pass `__METHOD__` to this parameter.
     *
     * @return array<int|string, mixed>
     */
    public static function getForKey($key)
    {
        if (self::$enabled === true && \array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        return [];
    }

    /**
     * Cache the result for a certain utility function.
     *
     * @since 1.0.0
     *
     * @param string     $key   The key to identify a particular set of results.
     *                          It is recommended to pass `__METHOD__` to this parameter.
     * @param int|string $id    Unique identifier for these results.
     *                          It is recommended for this to be a serialization of arguments passed
     *                          to the function or an md5 hash of an input.
     * @param mixed      $value An arbitrary value to write to the cache.
     *
     * @return mixed
     */
    public static function set($key, $id, $value)
    {
        if (self::$enabled === false) {
            return;
        }

        self::$cache[$key][$id] = $value;
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
