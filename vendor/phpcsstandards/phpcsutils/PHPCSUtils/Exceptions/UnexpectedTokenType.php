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
 * Exception for when a stack pointer is passed which is not of the expected token type.
 *
 * {@internal This exception should probably extend the PHP native `InvalidArgumentException`, but
 * that would inhibit the use of this exception, as replacing existing exceptions with this
 * (better) one would then be a breaking change.}
 *
 * @since 1.1.0
 */
final class UnexpectedTokenType extends RuntimeException
{

    /**
     * Create a new UnexpectedTokenType exception with a standardized text.
     *
     * @param int    $position      The argument position in the function signature. 1-based.
     * @param string $name          The argument name in the function signature.
     * @param string $acceptedTypes Phrase listing the accepted token type(s).
     * @param string $receivedType  The received token type.
     *
     * @return \PHPCSUtils\Exceptions\UnexpectedTokenType
     */
    public static function create($position, $name, $acceptedTypes, $receivedType)
    {
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return new self(
            \sprintf(
                '%s::%s(): Argument #%d (%s) must be of type %s; %s given.',
                $stack[1]['class'],
                $stack[1]['function'],
                $position,
                $name,
                $acceptedTypes,
                $receivedType
            )
        );
    }
}
