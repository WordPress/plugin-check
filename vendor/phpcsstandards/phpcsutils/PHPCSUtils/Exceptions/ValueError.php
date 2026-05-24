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
 * Exception for an invalid argument value passed.
 *
 * This exception should be used when the argument uses the correct type, but doesn't comply with
 * predefined restrictions, like an empty string being passed, when only a non-empty string is accepted
 * or a negative integer being passed when a positive integer is expected.
 *
 * {@internal This exception should probably extend the PHP native `InvalidArgumentException`, or the
 * PHP 8.0+ `ValueError`, but that would inhibit the use of this exception, as replacing existing
 * exceptions with this (better) one would then be a breaking change.}
 *
 * @since 1.1.0
 */
final class ValueError extends RuntimeException
{

    /**
     * Create a new ValueError exception with a standardized start of the text.
     *
     * @param int    $position The argument position in the function signature. 1-based.
     * @param string $name     The argument name in the function signature.
     * @param string $message  Arbitrary message text.
     *
     * @return \PHPCSUtils\Exceptions\ValueError
     */
    public static function create($position, $name, $message)
    {
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return new self(
            \sprintf(
                '%s::%s(): The value of argument #%d (%s) %s.',
                $stack[1]['class'],
                $stack[1]['function'],
                $position,
                $name,
                $message
            )
        );
    }
}
