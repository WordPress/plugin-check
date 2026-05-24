<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2024 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Exceptions;

use PHPCSUtils\Exceptions\RuntimeException;

/**
 * Exception for an invalid argument type passed.
 *
 * {@internal This exception should probably extend the PHP native `InvalidArgumentException`, or
 * the PHP 7.0+ `TypeError`, but that would inhibit the use of this exception, as replacing existing
 * exceptions with this (better) one would then be a breaking change.}
 *
 * @since 1.1.0
 */
final class TypeError extends RuntimeException
{

    /**
     * Create a new TypeError exception with a standardized text.
     *
     * @param int    $position The argument position in the function signature. 1-based.
     * @param string $name     The argument name in the function signature.
     * @param string $expected The argument type expected as a string.
     * @param mixed  $received The actual argument received.
     *
     * @return \PHPCSUtils\Exceptions\TypeError
     */
    public static function create($position, $name, $expected, $received)
    {
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return new self(
            \sprintf(
                '%s::%s(): Argument #%d (%s) must be of type %s, %s given.',
                $stack[1]['class'],
                $stack[1]['function'],
                $position,
                $name,
                $expected,
                \gettype($received)
            )
        );
    }
}
