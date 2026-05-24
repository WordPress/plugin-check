<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Exceptions;

use PHPCSUtils\Exceptions\RuntimeException;

/**
 * Exception thrown when an non-existent token array is requested.
 *
 * @since 1.0.0
 */
final class InvalidTokenArray extends RuntimeException
{

    /**
     * Create a new invalid token array exception with a standardized text.
     *
     * @since 1.0.0
     *
     * @param string $name The name of the token array requested.
     *
     * @return \PHPCSUtils\Exceptions\InvalidTokenArray
     */
    public static function create($name)
    {
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return new self(
            \sprintf(
                'Call to undefined method %s::%s()',
                $stack[1]['class'],
                $name
            )
        );
    }
}
