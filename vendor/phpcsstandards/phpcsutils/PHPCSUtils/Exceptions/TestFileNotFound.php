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

use BadMethodCallException;

/**
 * Exception thrown when the UtilityMethodTestCase::getTargetToken() method is run without a
 * tokenized test case file being available.
 *
 * @since 1.0.0
 */
final class TestFileNotFound extends BadMethodCallException
{

    /**
     * Create a new "test file not found" exception with a standardized text.
     *
     * @since 1.0.0
     *
     * @param string          $message  The Exception message to throw.
     * @param int             $code     The Exception code.
     * @param \Throwable|null $previous The previous exception used for the exception chaining.
     *
     * @return void
     */
    public function __construct($message = '', $code = 0, $previous = null)
    {
        if ($message === '') {
            $message = \sprintf(
                'Failed to find a tokenized test case file.%sMake sure the UtilityMethodTestCase::setUpTestFile()'
                . ' method has run before calling UtilityMethodTestCase::getTargetToken()',
                \PHP_EOL
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
