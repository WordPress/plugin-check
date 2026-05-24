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

use OutOfBoundsException;

/**
 * Exception thrown when a delimiter comment can not be found in a test case file.
 *
 * @since 1.0.0
 */
final class TestMarkerNotFound extends OutOfBoundsException
{

    /**
     * Create a new "test marker not found" exception with a standardized text.
     *
     * @since 1.0.0
     *
     * @param string $marker The delimiter comment.
     * @param string $file   The file in which the delimiter was not found.
     *
     * @return \PHPCSUtils\Exceptions\TestMarkerNotFound
     */
    public static function create($marker, $file)
    {
        return new self(
            \sprintf(
                'Failed to find the test marker: %s in test case file %s',
                $marker,
                $file
            )
        );
    }
}
