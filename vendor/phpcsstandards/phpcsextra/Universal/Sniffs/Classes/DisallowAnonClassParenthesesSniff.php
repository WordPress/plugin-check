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

/**
 * Forbid for an anonymous class declaration/instantiation to have parentheses, except when
 * parameters are being passed.
 *
 * @since 1.0.0
 */
final class DisallowAnonClassParenthesesSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Anon class declaration with parenthesis';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_ANON_CLASS];
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
        $tokens       = $phpcsFile->getTokens();
        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);

        // Note: no need to check for `false` as PHPCS won't retokenize `class` to `T_ANON_CLASS` in that case.
        if ($tokens[$nextNonEmpty]['code'] !== \T_OPEN_PARENTHESIS) {
            // No parentheses found.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'no');
            return;
        }

        if (isset($tokens[$nextNonEmpty]['parenthesis_closer']) === false) {
            /*
             * Incomplete set of parentheses. Ignore.
             * Shouldn't be possible as PHPCS won't retokenize `class` to `T_ANON_CLASS` in that case.
             */
            // @codeCoverageIgnoreStart
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'yes');
            return;
            // @codeCoverageIgnoreEnd
        }

        $opener    = $nextNonEmpty;
        $closer    = $tokens[$opener]['parenthesis_closer'];
        $hasParams = $phpcsFile->findNext(Tokens::$emptyTokens, ($opener + 1), $closer, true);
        if ($hasParams !== false) {
            // There is something between the parentheses. Ignore.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'yes, with parameter(s)');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'yes');

        $fix = $phpcsFile->addFixableError(
            'Parenthesis not allowed when creating a new anonymous class without passing parameters',
            $stackPtr,
            'Found'
        );

        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();

            for ($i = $opener; $i <= $closer; $i++) {
                if (isset(Tokens::$commentTokens[$tokens[$i]['code']]) === true) {
                    continue;
                }

                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->endChangeset();
        }
    }
}
