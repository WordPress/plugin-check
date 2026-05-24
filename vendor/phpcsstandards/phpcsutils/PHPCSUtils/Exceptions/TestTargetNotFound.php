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
 * Exception thrown when a test target token can not be found in a test case file.
 *
 * @since 1.0.0
 */
final class TestTargetNotFound extends OutOfBoundsException
{

    /**
     * Create a new "test target token not found" exception with a standardized text.
     *
     * @since 1.0.0
     *
     * @param string      $marker  The delimiter comment.
     * @param string|null $content The (optional) target token content.
     * @param string      $file    The file in which the target token was not found.
     *
     * @return \PHPCSUtils\Exceptions\TestTargetNotFound
     */
    public static function create($marker, $content, $file)
    {
        $contentPhrase = '';
        if (\is_string($content)) {
            $contentPhrase = ' with token content: ' . $content;
        }

        return new self(
            \sprintf(
                'Failed to find test target token for comment string: %s%s in test case file: %s',
                $marker,
                $contentPhrase,
                $file
            )
        );
    }
}
