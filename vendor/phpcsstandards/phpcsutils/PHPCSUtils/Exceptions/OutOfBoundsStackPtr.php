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
 * Exception for when a stack pointer which doesn't exist in the current file is passed.
 *
 * {@internal This exception should probably extend the PHP native `OutOfBoundsException`, but
 * that would inhibit the use of this exception, as replacing existing exceptions with this
 * (better) one would then be a breaking change.}
 *
 * @since 1.1.0
 */
final class OutOfBoundsStackPtr extends RuntimeException
{

    /**
     * Create a new OutOfBoundsStackPtr exception with a standardized text.
     *
     * @param int    $position The argument position in the function signature. 1-based.
     * @param string $name     The argument name in the function signature.
     * @param mixed  $received The received stack pointer position.
     *
     * @return \PHPCSUtils\Exceptions\OutOfBoundsStackPtr
     */
    public static function create($position, $name, $received)
    {
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return new self(
            \sprintf(
                '%s::%s(): Argument #%d (%s) must be a stack pointer which exists in the $phpcsFile object, %s given.',
                $stack[1]['class'],
                $stack[1]['function'],
                $position,
                $name,
                \is_scalar($received) ? \var_export($received, true) : \gettype($received)
            )
        );
    }
}
