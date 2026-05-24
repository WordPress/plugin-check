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
 * Exception for when a (conditionally) required parameter is not passed.
 *
 * {@internal This exception should probably extend the PHP native `ArgumentCountError`, but
 * that would inhibit the use of this exception, as replacing existing exceptions with this
 * (better) one would then be a breaking change.}
 *
 * @since 1.1.0
 */
final class MissingArgumentError extends RuntimeException
{

    /**
     * Create a new MissingArgumentError exception with a standardized start of the text.
     *
     * @param int    $position The argument position in the function signature. 1-based.
     * @param string $name     The argument name in the function signature.
     * @param string $message  Arbitrary message text, which should indicate under what
     *                         conditions the parameter is required.
     *
     * @return \PHPCSUtils\Exceptions\MissingArgumentError
     */
    public static function create($position, $name, $message)
    {
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return new self(
            \sprintf(
                '%s::%s(): Argument #%d (%s) is required %s.',
                $stack[1]['class'],
                $stack[1]['function'],
                $position,
                $name,
                $message
            )
        );
    }
}
