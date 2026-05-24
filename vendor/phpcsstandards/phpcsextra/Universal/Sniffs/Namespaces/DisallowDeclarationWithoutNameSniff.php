<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\Namespaces;

/**
 * Forbids the use of namespace declarations without a namespace name.
 *
 * @since 1.0.0
 */
final class DisallowDeclarationWithoutNameSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Namespace declaration declares a name';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_NAMESPACE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $name = Namespaces::getDeclaredName($phpcsFile, $stackPtr);
        if ($name === false) {
            // Use of the namespace keyword as an operator or live coding/parse error.
            return;
        }

        if ($name !== '') {
            // Named namespace.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'yes');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'no');

        // Namespace declaration without namespace name (= global namespace).
        $phpcsFile->addError(
            'Namespace declarations without a namespace name are not allowed.',
            $stackPtr,
            'Forbidden'
        );
    }
}
