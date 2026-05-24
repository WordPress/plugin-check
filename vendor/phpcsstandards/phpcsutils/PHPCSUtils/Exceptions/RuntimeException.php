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

use PHP_CodeSniffer\Exceptions\RuntimeException as PHPCSRuntimeException;

/**
 * Exception for reporting a runtime error.
 *
 * @phpcs:disable Universal.Classes.RequireFinalClass -- Deliberately not final.
 *
 * @since 1.1.0
 */
class RuntimeException extends PHPCSRuntimeException
{
}
