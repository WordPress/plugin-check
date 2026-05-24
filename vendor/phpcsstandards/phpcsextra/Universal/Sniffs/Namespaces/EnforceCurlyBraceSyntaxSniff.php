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
 * Enforce the use of namespace declarations using the curly brace syntax.
 *
 * @since 1.0.0
 */
final class EnforceCurlyBraceSyntaxSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Namespace declaration using curly brace syntax';

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
        if (Namespaces::isDeclaration($phpcsFile, $stackPtr) === false) {
            // Namespace operator, not a declaration; or live coding/parse error.
            return;
        }

        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_condition']) === true
            && $tokens[$stackPtr]['scope_condition'] === $stackPtr
        ) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'yes');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'no');

        $phpcsFile->addError(
            'Namespace declarations without curly braces are not allowed.',
            $stackPtr,
            'Forbidden'
        );
    }
}
