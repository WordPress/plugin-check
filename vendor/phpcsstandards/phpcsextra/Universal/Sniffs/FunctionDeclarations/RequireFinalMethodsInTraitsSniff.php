<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2023 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\FunctionDeclarations;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\ObjectDeclarations;
use PHPCSUtils\Utils\Scopes;

/**
 * Require non-abstract, non-private methods in traits to be declared as "final".
 *
 * @since 1.1.0
 */
final class RequireFinalMethodsInTraitsSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const METRIC_NAME = 'Non-private method in trait is abstract or final ?';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.1.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_FUNCTION];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$stackPtr]['parenthesis_opener']) === false) {
            // Parse error/live coding.
            return;
        }

        $scopePtr = Scopes::validDirectScope($phpcsFile, $stackPtr, \T_TRAIT);
        if ($scopePtr === false) {
            // Not a trait method.
            return;
        }

        $methodProps = FunctionDeclarations::getProperties($phpcsFile, $stackPtr);
        if ($methodProps['scope'] === 'private') {
            // Private methods can't be final.
            return;
        }

        if ($methodProps['is_final'] === true) {
            // Already final, nothing to do.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'final');
            return;
        }

        if ($methodProps['is_abstract'] === true) {
            // Abstract classes can't be final.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'abstract');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'not abstract, not final');

        $methodName = FunctionDeclarations::getName($phpcsFile, $stackPtr);
        $magic      = '';
        $code       = 'NonFinalMethodFound';
        if (FunctionDeclarations::isMagicMethodName($methodName) === true) {
            // Use separate error code for magic methods.
            $magic = 'magic ';
            $code  = 'NonFinalMagicMethodFound';
        }

        $data = [
            $methodProps['scope'],
            $magic,
            $methodName,
            ObjectDeclarations::getName($phpcsFile, $scopePtr),
        ];

        $fix = $phpcsFile->addFixableError(
            'The non-abstract, %s %smethod "%s()" in trait %s should be declared as final.',
            $stackPtr,
            $code,
            $data
        );

        if ($fix === true) {
            $phpcsFile->fixer->addContentBefore($stackPtr, 'final ');
        }
    }
}
