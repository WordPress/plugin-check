<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\GetTokensAsString;
use PHPCSUtils\Utils\ObjectDeclarations;

/**
 * Require classes being declared as "final".
 *
 * @since 1.0.0
 */
final class RequireFinalClassSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Class is abstract or final ?';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_CLASS];
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
        $classProp = ObjectDeclarations::getClassProperties($phpcsFile, $stackPtr);
        if ($classProp['is_final'] === true) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'final');
            return;
        }

        if ($classProp['is_abstract'] === true) {
            // Abstract classes can't be final.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'abstract');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'not abstract, not final');

        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($nextNonEmpty === false) {
            // Live coding or parse error.
            return;
        }

        $snippetEnd  = $nextNonEmpty;
        $classCloser = '';

        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$stackPtr]['scope_opener']) === true) {
            $snippetEnd  = $tokens[$stackPtr]['scope_opener'];
            $classCloser = '}';
        }

        $snippet = GetTokensAsString::compact($phpcsFile, $stackPtr, $snippetEnd, true);
        $fix     = $phpcsFile->addFixableError(
            'A non-abstract class should be declared as final. Found: %s%s',
            $stackPtr,
            'NonFinalClassFound',
            [$snippet, $classCloser]
        );

        if ($fix === true) {
            $phpcsFile->fixer->addContentBefore($stackPtr, 'final ');
        }
    }
}
