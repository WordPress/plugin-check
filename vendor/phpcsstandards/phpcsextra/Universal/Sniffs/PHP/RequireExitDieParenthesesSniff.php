<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Require that `exit`/`die` always be called with parentheses, even if no argument is given.
 *
 * @since 1.5.0
 */
final class RequireExitDieParenthesesSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.5.0
     *
     * @var string
     */
    const METRIC_NAME = 'Exit/die with parentheses';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.5.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_EXIT];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.5.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens       = $phpcsFile->getTokens();
        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($nextNonEmpty === false) {
            // Live coding. Do not flag (yet).
            return;
        }

        if ($tokens[$nextNonEmpty]['code'] === \T_OPEN_PARENTHESIS) {
            // Parentheses found.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'yes');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'no');

        $fix = $phpcsFile->addFixableError(
            'Parentheses required when calling %s, even if no argument is given.',
            $stackPtr,
            'Missing',
            [\strtolower(\ltrim($tokens[$stackPtr]['content'], '\\'))]
        );

        if ($fix === true) {
            $phpcsFile->fixer->addContent($stackPtr, '()');
        }
    }
}
