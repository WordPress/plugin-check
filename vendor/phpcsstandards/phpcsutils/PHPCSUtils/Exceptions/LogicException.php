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
 * Exception for reporting a logic error.
 *
 * {@internal This exception should probably extend the PHP native `LogicException`, but
 * that would inhibit the use of this exception, as replacing existing exceptions with this
 * (better) one would then be a breaking change.}
 *
 * @since 1.1.0
 */
final class LogicException extends RuntimeException
{

    /**
     * Create a new LogicException with a standardized start of the text.
     *
     * @param string $message Arbitrary message text.
     *
     * @return \PHPCSUtils\Exceptions\LogicException
     */
    public static function create($message)
    {
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return new self(
            \sprintf(
                '%s::%s(): %s',
                $stack[1]['class'],
                $stack[1]['function'],
                $message
            )
        );
    }
}
